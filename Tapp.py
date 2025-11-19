from flask import Flask, jsonify, request
from Tpedidos import (
    crear_pedido,
    obtener_pedido,
    actualizar_estado,
    listar_por_usuario,
    cancelar_pedido
)

app = Flask(__name__)

@app.route('/')
def home():
    return "API de Pedidos funcionando correctamente. Usa Insomnia para interactuar.", 200

# 1️⃣ Crear pedido (POST)
@app.route('/pedidos', methods=['POST'])
def route_crear_pedido():
    data = request.json
    pedido = crear_pedido(data)
    return jsonify(pedido), 201


# 2️⃣ Actualizar estado pedido (PUT)
@app.route('/pedidos/<int:id_pedido>', methods=['PUT'])
def route_actualizar_estado(id_pedido):
    data = request.json
    estado = data.get("estado")
    pedido = actualizar_estado(id_pedido, estado)
    if pedido:
        return jsonify(pedido)
    return jsonify({"error": "Pedido no encontrado"}), 404


# 3️⃣ Obtener pedido por ID (GET)
@app.route('/pedidos/<int:id_pedido>', methods=['GET'])
def route_obtener_pedido(id_pedido):
    pedido = obtener_pedido(id_pedido)
    if pedido:
        return jsonify(pedido)
    return jsonify({"error": "Pedido no encontrado"}), 404


# 4️⃣ Listar pedidos por usuario (GET)
@app.route('/pedidos/usuario/<usuario>', methods=['GET'])
def route_listar_por_usuario(usuario):
    lista = listar_por_usuario(usuario)
    return jsonify(lista)


# 5️⃣ Cancelar pedido (DELETE)
@app.route('/pedidos/<int:id_pedido>', methods=['DELETE'])
def route_cancelar_pedido(id_pedido):
    pedido = cancelar_pedido(id_pedido)
    if pedido:
        return jsonify({"mensaje": "Pedido cancelado", "pedido": pedido})
    return jsonify({"error": "Pedido no encontrado"}), 404


if __name__ == '__main__':
    app.run(debug=True)
