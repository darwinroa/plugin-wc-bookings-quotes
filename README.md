# WooCommerce Bookings Quotes - Sistema de Cotizaciones y Reservas

Este plugin extiende la funcionalidad de **WooCommerce Bookings** para permitir un flujo de trabajo basado en **Solicitud de Cotización -> Negociación -> Pago**, ideal para servicios de catering, eventos o reservas complejas donde el precio o los detalles pueden variar antes de la confirmación final.

## 🚀 Funcionalidades Principales

### 🔹 Para el Cliente (Frontend)
* **Modo Cotización:** Reemplaza la compra directa por una solicitud de presupuesto.
* **Formulario Modal:** Un pop-up intuitivo captura datos esenciales (Nombre, Email, Teléfono, Notas) sin recargar la página.
* **Validación Inteligente:** El botón de cotizar solo se activa cuando WooCommerce Bookings ha validado la disponibilidad y calculado el precio base.
* **Experiencia de Usuario:** Pre-llenado automático de datos si el usuario está logueado.

### 🔹 Gestión Administrativa (Backend)
* **CPT Personalizado:** Gestión centralizada de solicitudes en el panel "Cotizaciones".
* **Edición de Negociación:** Permite al administrador modificar el **Precio Final** y la **Cantidad de Personas** antes de aprobar, sobrescribiendo el cálculo original.
* **Notas de Acuerdo:** Campo interno para registrar acuerdos finales con el cliente (ej: "Se incluyó servicio extra").
* **Notificaciones Admin:** Contador visual (burbuja roja) en el menú para nuevas solicitudes pendientes.

### ⚡ Automatización y Flujo de Venta
* **Conversión Automática:** Al cambiar el estado a **"Aprobada"**, el plugin automáticamente:
    1.  Genera un **Pedido (WC Order)** con el precio pactado.
    2.  Crea una **Reserva (WC Booking)** real vinculada al pedido y al usuario.
    3.  **Bloquea la disponibilidad** en el calendario (estado `unpaid`) para evitar overbooking mientras el cliente paga.
    4.  Inyecta los detalles (Fecha, Personas, Notas) en los ítems de la orden para que aparezcan en el recibo.

### 📧 Sistema de Notificaciones
Correos transaccionales automáticos para cada etapa:
1.  **Nueva Solicitud (Admin):** Aviso inmediato con todos los detalles del evento.
2.  **Confirmación de Recibido (Cliente):** Mensaje automático de tranquilidad.
3.  **Actualización de Estado:** Notifica al cliente si la cotización está "En Revisión", "Rechazada" o "Aprobada".
4.  **Botón de Pago:** El correo de aprobación incluye un botón directo al Checkout de WooCommerce para pagar la orden generada.

## 🛠️ Requisitos Técnicos
* WordPress
* WooCommerce
* WooCommerce Bookings
* Un tema compatible con WooCommerce

## ⚙️ Cómo Funciona

1.  El cliente selecciona fecha y personas en el producto y hace clic en "Solicitar Cotización".
2.  Llena el formulario modal con sus datos y requerimientos especiales.
3.  El administrador recibe la solicitud, revisa las notas y, si es necesario, ajusta el precio en el panel de administración.
4.  El administrador escribe el "Acuerdo Final" y cambia el estado a **Aprobada**.
5.  El sistema crea la Orden y la Reserva, y envía un correo al cliente con el enlace de pago.
6.  El cliente paga y la reserva queda confirmada automáticamente.

## 📝 Instalación

1.  Sube la carpeta del plugin al directorio `/wp-content/plugins/`.
2.  Activa el plugin desde el menú 'Plugins' de WordPress.
3.  Asegúrate de tener configurado un producto como "Bookable Product".

---
**Desarrollado para optimizar la gestión de eventos y catering en WooCommerce.**