# ProyectoConde
# Servicio 2 - Procesamiento de Pedidos (Tadeo)

**Cómo ejecutar**
1. Instalar dependencias:
   pip install -r Trequirements.txt
2. Ejecutar:
   python Tapp.py

**Endpoints**
- POST /pedidos   -> crear pedido
- GET /pedidos/<id>
- PUT /pedidos/<id>
- DELETE /pedidos/<id>
- GET /pedidos/usuario/<usuario>

Ejemplo POST (body JSON):
{
  "usuario":"tadeo",
  "productos":["Revistas","Musica"]
}
