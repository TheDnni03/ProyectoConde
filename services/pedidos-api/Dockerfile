FROM python:3.11-slim

WORKDIR /app

# Dependencias del sistema (opcional pero útil)
RUN apt-get update && apt-get install -y \
    curl \
    && rm -rf /var/lib/apt/lists/*

# Copiamos requirements e instalamos dependencias
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# Copiamos TODO el código del servicio
COPY . .

# Exponemos el puerto donde correrá FastAPI
EXPOSE 5001

# Comando de arranque
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "5001"]
