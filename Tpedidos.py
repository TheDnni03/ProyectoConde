pedidos = {}
contador_id = 1

def crear_pedido(data):
    global contador_id
    pedido_id = contador_id
    contador_id += 1

    pedido = {
        "id": pedido_id,
        "usuario": data.get("usuario"),
        "productos": data.get("productos"),
        "estado": "pendiente"
    }

    pedidos[pedido_id] = pedido
    return pedido


def obtener_pedido(id_pedido):
    return pedidos.get(id_pedido)


def actualizar_estado(id_pedido, estado):
    if id_pedido in pedidos:
        pedidos[id_pedido]["estado"] = estado
        return pedidos[id_pedido]
    return None


def listar_por_usuario(usuario):
    return [p for p in pedidos.values() if p["usuario"] == usuario]


def cancelar_pedido(id_pedido):
    if id_pedido in pedidos:
        pedidos[id_pedido]["estado"] = "cancelado"
        return pedidos[id_pedido]
    return None
