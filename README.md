# WP Product Personalizer

Plugin de WordPress que permite a los clientes subir imágenes personalizadas y añadir mensajes a los productos de WooCommerce.

## Descripción

WP Product Personalizer ofrece a tus clientes la posibilidad de personalizar los productos con:

- **Imágenes personalizadas**: Los clientes pueden subir sus propias imágenes.
- **Mensajes personalizados**: Los clientes pueden añadir mensajes personalizados.

Esta información se muestra en cada pedido, facilitando la personalización de los productos según las necesidades del cliente.

## Características

- Activación global para todos los productos o solo para productos específicos.
- Personalización de etiquetas para los campos de imagen y mensaje.
- Opción para hacer que los campos sean obligatorios u opcionales.
- Previsualización de la imagen subida antes de añadir al carrito.
- Visualización de la información personalizada en la página de administración de pedidos.
- **Compatible con el almacenamiento de pedidos de alto rendimiento (HPOS) de WooCommerce**.
- **Carga de imágenes mediante AJAX para mejorar la fiabilidad**.

## Instalación

1. Descarga el plugin y súbelo a la carpeta `/wp-content/plugins/`
2. Activa el plugin a través del menú 'Plugins' en WordPress
3. Asegúrate de tener WooCommerce instalado y activado
4. Configura las opciones del plugin en el menú 'Personalizador' en el panel de administración

## Requisitos

- WordPress 5.0 o superior
- WooCommerce 3.0 o superior
- PHP 7.2 o superior

## Compatibilidad con WooCommerce

Este plugin es compatible con la característica de almacenamiento de pedidos de alto rendimiento (HPOS) de WooCommerce. Puede utilizarse tanto con el sistema tradicional de pedidos como con el nuevo sistema HPOS sin problemas.

## Configuración

1. Ve a Personalizador en el menú de administración.
2. Configura si quieres activar la personalización para todos los productos o solo para productos específicos.
3. Introduce los IDs de los productos específicos si no has activado la opción global.
4. Personaliza las etiquetas para los campos de imagen y mensaje.
5. Configura si los campos son obligatorios u opcionales.
6. Guarda los cambios.

## Uso

- Los campos de personalización aparecerán automáticamente en las páginas de productos configurados, justo antes del botón de "Añadir al carrito".
- Los clientes pueden subir imágenes y añadir mensajes según la configuración.
- Las imágenes se cargan mediante AJAX antes de que el cliente añada el producto al carrito, garantizando que la imagen se procese correctamente.
- La información personalizada se guarda con cada pedido y se muestra en la página de administración de pedidos.

## Versiones

- 1.0.0: Versión inicial.
- 1.0.1: Añadida compatibilidad con almacenamiento de pedidos de alto rendimiento (HPOS) de WooCommerce.
- 1.0.2: Mejorado el sistema de carga de imágenes con AJAX para mayor fiabilidad. Corregidos problemas con el guardado de datos personalizados en pedidos.

## Resolución de problemas

Si los datos personalizados no aparecen en los pedidos, comprueba lo siguiente:

1. Asegúrate de que tu tema es compatible con WooCommerce y que no está sobrescribiendo las plantillas de carrito/checkout.
2. Verifica que los permisos de escritura en la carpeta de uploads estén correctamente configurados.
3. Comprueba los logs de errores de PHP por si hubiera algún problema con el procesamiento AJAX.

## Soporte

Si tienes alguna pregunta o problema con el plugin, por favor contacta con nosotros a través de GitHub.