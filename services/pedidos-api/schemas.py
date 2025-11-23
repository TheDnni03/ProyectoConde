# schemas.py
from typing import Optional
from pydantic import BaseModel, Field


# ===============================
#     BASE DEL PEDIDO
# ===============================
class OrderBase(BaseModel):
    product_id: str = Field(..., description="ID del producto")
    product_name: str = Field(..., description="Nombre del producto")
    price: float = Field(..., description="Precio del producto")
    address: str = Field(..., description="Dirección de entrega")
    details: Optional[str] = Field(None, description="Detalles adicionales del pedido")
    
    # 👇 IMPORTANTE: nuevo campo
    user_id: Optional[str] = Field(
        None, description="ID del usuario dueño del pedido"
    )


# ===============================
#     CREACIÓN DE PEDIDO
# ===============================
class OrderCreate(OrderBase):
    """
    Esquema para crear un pedido.
    El user_id lo sobrescribimos en el backend usando el token JWT,
    así que se ignora si lo mandan.
    """
    pass


# ===============================
#     ACTUALIZACIÓN
# ===============================
class OrderUpdate(BaseModel):
    product_id: Optional[str] = None
    product_name: Optional[str] = None
    price: Optional[float] = None
    address: Optional[str] = None
    details: Optional[str] = None


# ===============================
#     RESPUESTA DESDE FIREBASE
# ===============================
class OrderDB(OrderBase):
    id: str = Field(..., description="ID generado en Firebase")
