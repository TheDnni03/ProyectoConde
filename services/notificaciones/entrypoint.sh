#!/bin/sh
set -e

# Opcional: si quieres que Firebase use esta variable
export GOOGLE_APPLICATION_CREDENTIALS="${FIREBASE_CREDENTIALS_FILE}"

# Lanzar la app FastAPI
exec uvicorn main:app --host 0.0.0.0 --port 7001