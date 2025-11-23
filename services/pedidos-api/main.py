# main.py
from typing import List

from fastapi import FastAPI, Depends, HTTPException, status
from fastapi.middleware.cors import CORSMiddleware

from firebase_config import get_orders_ref
from schemas import OrderCreate, OrderUpdate, OrderDB
from auth import verify_token

app = FastAPI(
    title="Pedidos API",
    description="CRUD de pedidos almacenados en Firebase Realtime DB con protección JWT",
    version="1.0.0",
)

# CORS (ajusta origins según tu frontend)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # en producción mejor poner dominios específicos
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# 🔐 Helper para obtener el ID de usuario desde el token
def get_current_user_id(current_user: dict) -> str:
    """
    Lee el ID de usuario desde el payload del token.
    Soporta tanto 'user_id' como 'sub' (como lo genera tu API en PHP).
    """
    user_id = current_user.get("user_id") or current_user.get("sub")

    if not user_id:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="El token no contiene user_id/sub",
        )

    return user_id


# ============== ENDPOINTS ==============

@app.get("/orders", response_model=List[OrderDB])
def list_orders(current_user: dict = Depends(verify_token)):
    """
    Obtener todos los pedidos (admin/debug).
    Protegido por JWT.
    """
    ref = get_orders_ref()
    data = ref.get() or {}

    orders: List[OrderDB] = []
    for order_id, order_data in data.items():
        orders.append(OrderDB(id=order_id, **order_data))

    return orders


@app.get("/orders/my", response_model=List[OrderDB])
def list_my_orders(current_user: dict = Depends(verify_token)):
    """
    Listar pedidos SOLO del usuario autenticado.
    """
    user_id = get_current_user_id(current_user)

    ref = get_orders_ref()
    data = ref.get() or {}

    user_orders: List[OrderDB] = []
    for order_id, order_data in data.items():
        if order_data.get("user_id") == user_id:
            user_orders.append(OrderDB(id=order_id, **order_data))

    if not user_orders:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Pedido no encontrado",
        )

    return user_orders


@app.get("/orders/{order_id}", response_model=OrderDB)
def get_order(order_id: str, current_user: dict = Depends(verify_token)):
    """
    Obtener un pedido por su ID.
    Protegido por JWT.
    """
    ref = get_orders_ref().child(order_id)
    data = ref.get()

    if not data:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Pedido no encontrado",
        )

    return OrderDB(id=order_id, **data)


@app.post("/orders", response_model=OrderDB, status_code=status.HTTP_201_CREATED)
def create_order(order: OrderCreate, current_user: dict = Depends(verify_token)):
    """
    Crear un nuevo pedido.
    Protegido por JWT.
    """
    user_id = get_current_user_id(current_user)

    ref = get_orders_ref()

    order_data = {
        **order.dict(),
        "user_id": user_id,  # 👈 se guarda quién hizo el pedido
    }

    new_ref = ref.push(order_data)
    order_id = new_ref.key

    return OrderDB(id=order_id, **order_data)


@app.put("/orders/{order_id}", response_model=OrderDB)
def update_order(
    order_id: str,
    order: OrderUpdate,
    current_user: dict = Depends(verify_token),
):
    """
    Actualizar un pedido por su ID.
    Protegido por JWT.
    """
    ref = get_orders_ref().child(order_id)
    existing = ref.get()

    if not existing:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Pedido no encontrado",
        )

    # Solo actualizamos los campos enviados (no None)
    update_data = {k: v for k, v in order.dict().items() if v is not None}

    if update_data:
        ref.update(update_data)
        existing.update(update_data)

    return OrderDB(id=order_id, **existing)


@app.delete("/orders/{order_id}", status_code=status.HTTP_204_NO_CONTENT)
def delete_order(order_id: str, current_user: dict = Depends(verify_token)):
    """
    Borrar un pedido por su ID.
    Protegido por JWT.
    """
    ref = get_orders_ref().child(order_id)
    existing = ref.get()

    if not existing:
        raise HTTPException(
            status_code=status.HTTP_404_NOT_FOUND,
            detail="Pedido no encontrado",
        )

    ref.delete()
    return  # 204 No Content
