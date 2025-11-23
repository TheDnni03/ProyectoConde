#!/bin/bash
echo "Iniciando servicio de Pedidos (FastAPI)"

# Ejecutar la API con Uvicorn
uvicorn app.main:app --host 0.0.0.0 --port 5001 --reload
