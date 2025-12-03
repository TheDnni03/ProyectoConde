FROM python:3.11-slim

WORKDIR /app

# Config básica de Python
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

# Ruta que usará Firebase Admin (opcional si tu firebase_config ya usa FIREBASE_CREDENTIALS_FILE)
ENV GOOGLE_APPLICATION_CREDENTIALS=/app/config/firebase_credentials.json

# Instalar dependencias
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiar el código
COPY . .

# Comando para levantar FastAPI
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "7001"]
