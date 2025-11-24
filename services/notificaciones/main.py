from fastapi import FastAPI, HTTPException, BackgroundTasks, Header, status, Depends
from pydantic import BaseModel, HttpUrl
from typing import List, Optional, Dict, Any, Literal
from datetime import datetime, timezone
import uuid
import os
import httpx

from firebase_config import get_webhooks_ref, get_events_ref

# =========================
# Config
# =========================

INTERNAL_TOKEN = os.getenv("INTERNAL_TOKEN", "super-secreto")  # mismo en los otros servicios
MAX_RETRIES = int(os.getenv("WEBHOOK_MAX_RETRIES", "3"))
TIMEOUT = float(os.getenv("WEBHOOK_TIMEOUT", "5.0"))

app = FastAPI(
    title="Notificaciones / Webhooks API",
    description=(
        "Servicio de notificaciones que registra webhooks externos, "
        "escucha eventos de otros microservicios y reenvía los eventos."
    ),
    version="1.0.0",
)


# =========================
# Modelos
# =========================

class WebhookIn(BaseModel):
    url: HttpUrl
    description: Optional[str] = None
    events: List[Literal["user.registered", "order.created"]]


class WebhookOut(WebhookIn):
    id: str
    active: bool
    created_at: datetime


class UserRegisteredEvent(BaseModel):
    user_id: str
    email: str
    name: str


class OrderCreatedEvent(BaseModel):
    order_id: str
    user_id: str
    product_id: str
    amount: float


class NotificationEvent(BaseModel):
    id: str
    event_type: Literal["user.registered", "order.created"]
    payload: Dict[str, Any]
    timestamp: datetime


# =========================
# Seguridad interna
# =========================

def verify_internal_token(
    x_internal_token: str = Header(..., alias="X-Internal-Token")
):
    if x_internal_token != INTERNAL_TOKEN:
        raise HTTPException(
            status_code=status.HTTP_403_FORBIDDEN,
            detail="Invalid internal token",
        )


# =========================
# Endpoints básicos
# =========================

@app.get("/health")
def health():
    return {"status": "ok"}


# =========================
# Gestión de webhooks externos
# =========================

@app.post("/webhooks/register", response_model=WebhookOut)
def register_webhook(webhook: WebhookIn):
    ref = get_webhooks_ref().push()
    webhook_id = ref.key

    data = {
        "id": webhook_id,
        "url": str(webhook.url),
        "description": webhook.description,
        "events": webhook.events,
        "active": True,
        "created_at": datetime.now(timezone.utc).isoformat()
    }
    ref.set(data)

    return WebhookOut(**data)


@app.get("/webhooks", response_model=List[WebhookOut])
def list_webhooks():
    data = get_webhooks_ref().get() or {}
    return [WebhookOut(**w) for w in data.values()]


@app.post("/webhooks/{webhook_id}/deactivate")
def deactivate_webhook(webhook_id: str):
    ref = get_webhooks_ref().child(webhook_id)
    if not ref.get():
        raise HTTPException(status_code=404, detail="Webhook no encontrado")
    ref.update({"active": False})
    return {"status": "ok", "message": "Webhook desactivado"}


# =========================
# Endpoints que reciben eventos de otros microservicios
# (USUARIOS / PEDIDOS / REPORTES)
# =========================

@app.post("/events/user-registered", dependencies=[Depends(verify_internal_token)])
def event_user_registered(
    event: UserRegisteredEvent,
    background_tasks: BackgroundTasks
):
    event_id = str(uuid.uuid4())
    wrapper = NotificationEvent(
        id=event_id,
        event_type="user.registered",
        payload=event.dict(),
        timestamp=datetime.now(timezone.utc),
    )

    # Guardar en Firebase
    get_events_ref().child(event_id).set(wrapper.dict())

    # Reenvío asíncrono a webhooks externos
    background_tasks.add_task(dispatch_event_to_webhooks, wrapper)

    return {"status": "queued", "event_id": event_id}


@app.post("/events/order-created", dependencies=[Depends(verify_internal_token)])
def event_order_created(
    event: OrderCreatedEvent,
    background_tasks: BackgroundTasks
):
    event_id = str(uuid.uuid4())
    wrapper = NotificationEvent(
        id=event_id,
        event_type="order.created",
        payload=event.dict(),
        timestamp=datetime.now(timezone.utc),
    )

    get_events_ref().child(event_id).set(wrapper.dict())
    background_tasks.add_task(dispatch_event_to_webhooks, wrapper)

    return {"status": "queued", "event_id": event_id}


# =========================
# Lógica de envío con reintentos
# =========================

async def post_with_retries(client: httpx.AsyncClient, url: str, body: dict) -> bool:
    for attempt in range(1, MAX_RETRIES + 1):
        try:
            resp = await client.post(url, json=body, timeout=TIMEOUT)
            if 200 <= resp.status_code < 300:
                return True
        except Exception:
            pass
    return False


def dispatch_event_to_webhooks(event: NotificationEvent):
    """
    Se ejecuta en segundo plano.
    Lee los webhooks activos que están suscritos a ese tipo de evento
    y les envía el payload. Registra resultado en Firebase.
    """
    import asyncio

    webhooks_data = get_webhooks_ref().get() or {}

    # Filtrar webhooks activos que escuchan este tipo de evento
    webhooks = [
        w for w in webhooks_data.values()
        if w.get("active") and event.event_type in (w.get("events") or [])
    ]

    if not webhooks:
        return

    async def _send_all():
        async with httpx.AsyncClient() as client:
            tasks = []
            for w in webhooks:
                url = w["url"]
                body = {
                    "id": event.id,
                    "type": event.event_type,
                    "timestamp": event.timestamp.isoformat(),
                    "data": event.payload,
                }
                tasks.append(post_with_retries(client, url, body))

            results = await asyncio.gather(*tasks, return_exceptions=True)

        # Guardar log de entregas en Firebase
        deliveries = {}
        for w, success in zip(webhooks, results):
            deliveries.setdefault("logs", []).append(
                {
                    "webhook_id": w["id"],
                    "url": w["url"],
                    "success": bool(success),
                    "checked_at": datetime.now(timezone.utc).isoformat(),
                }
            )

        if deliveries:
            get_events_ref().child(event.id).child("deliveries").set(deliveries["logs"])

    asyncio.run(_send_all())
