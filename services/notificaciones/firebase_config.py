import os
import firebase_admin
from firebase_admin import credentials, db

FIREBASE_DB_URL = os.getenv("FIREBASE_DB_URL")


def init_firebase():
    if firebase_admin._apps:
        return

    cred_path = os.getenv(
        "FIREBASE_CREDENTIALS_FILE",
        "/app/config/firebase_credentials.json"  # ruta dentro del contenedor
    )

    if not FIREBASE_DB_URL:
        raise RuntimeError("FIREBASE_DB_URL no está configurada")

    cred = credentials.Certificate(cred_path)
    firebase_admin.initialize_app(cred, {
        "databaseURL": FIREBASE_DB_URL
    })


# Se inicializa al importar
init_firebase()


def get_root_ref():
    # /notifications será la raíz de esta nueva BD
    return db.reference("notifications")


def get_webhooks_ref():
    return get_root_ref().child("webhooks")


def get_events_ref():
    return get_root_ref().child("events")
