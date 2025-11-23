# firebase_config.py
import os
import firebase_admin
from firebase_admin import credentials, db
from dotenv import load_dotenv

# Cargar variables de entorno desde .env (si lo usas)
load_dotenv()

# Ruta al archivo de credenciales y URL de la Realtime DB
FIREBASE_CREDENTIALS_FILE = os.getenv("FIREBASE_CREDENTIALS_FILE", "firebase_credentials.json")
FIREBASE_DB_URL = os.getenv("FIREBASE_DB_URL")  # p.ej. https://TU-PROYECTO.firebaseio.com

if not firebase_admin._apps:
    cred = credentials.Certificate(FIREBASE_CREDENTIALS_FILE)
    firebase_admin.initialize_app(cred, {
        "databaseURL": FIREBASE_DB_URL
    })

def get_orders_ref():
    """
    Devuelve la referencia al nodo 'orders' en la Realtime Database.
    """
    return db.reference("orders")
