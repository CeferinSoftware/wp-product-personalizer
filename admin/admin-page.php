<?php
/**
 * Página de administración del plugin
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wppp-admin-container">
        <form method="post" action="options.php">
            <?php settings_fields('wppp_options'); ?>
            <?php do_settings_sections('wppp_options'); ?>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e('Activar en todos los productos', 'wp-product-personalizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wppp_enable_all_products" value="1" <?php checked(get_option('wppp_enable_all_products'), true); ?> />
                            <?php _e('Activar personalización para todos los productos', 'wp-product-personalizer'); ?>
                        </label>
                        <p class="description"><?php _e('Si se activa, permite subir imágenes y mensajes en todos los productos de la tienda.', 'wp-product-personalizer'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top" id="product_ids_row" <?php echo get_option('wppp_enable_all_products') ? 'style="display:none;"' : ''; ?>>
                    <th scope="row"><?php _e('IDs de productos específicos', 'wp-product-personalizer'); ?></th>
                    <td>
                        <input type="text" name="wppp_product_ids" value="<?php echo esc_attr(get_option('wppp_product_ids')); ?>" class="regular-text" />
                        <p class="description"><?php _e('Introduce IDs de productos separados por comas (ej: 123,456,789). Deja en blanco para usar la opción global.', 'wp-product-personalizer'); ?></p>
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Etiqueta para subida de imagen', 'wp-product-personalizer'); ?></th>
                    <td>
                        <input type="text" name="wppp_image_upload_label" value="<?php echo esc_attr(get_option('wppp_image_upload_label', __('Sube tu imagen personalizada', 'wp-product-personalizer'))); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Etiqueta para mensaje', 'wp-product-personalizer'); ?></th>
                    <td>
                        <input type="text" name="wppp_message_label" value="<?php echo esc_attr(get_option('wppp_message_label', __('Añade tu mensaje personalizado', 'wp-product-personalizer'))); ?>" class="regular-text" />
                    </td>
                </tr>
                
                <tr valign="top">
                    <th scope="row"><?php _e('Campos requeridos', 'wp-product-personalizer'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="wppp_require_image" value="1" <?php checked(get_option('wppp_require_image'), true); ?> />
                            <?php _e('Imagen requerida', 'wp-product-personalizer'); ?>
                        </label>
                        <br>
                        <label>
                            <input type="checkbox" name="wppp_require_message" value="1" <?php checked(get_option('wppp_require_message'), true); ?> />
                            <?php _e('Mensaje requerido', 'wp-product-personalizer'); ?>
                        </label>
                    </td>
                </tr>
            </table>
            
            <?php submit_button(); ?>
        </form>
    </div>
    
    <div class="wppp-admin-sidebar">
        <div class="wppp-admin-box">
            <h3><?php _e('Ayuda y Soporte', 'wp-product-personalizer'); ?></h3>
            <p><?php _e('Este plugin permite a tus clientes personalizar los productos con imágenes y mensajes.', 'wp-product-personalizer'); ?></p>
            <p><?php _e('Principales características:', 'wp-product-personalizer'); ?></p>
            <ul>
                <li><?php _e('✓ Subida de imágenes personalizadas', 'wp-product-personalizer'); ?></li>
                <li><?php _e('✓ Mensajes personalizados', 'wp-product-personalizer'); ?></li>
                <li><?php _e('✓ Activación global o por producto', 'wp-product-personalizer'); ?></li>
                <li><?php _e('✓ Configuración de campos requeridos', 'wp-product-personalizer'); ?></li>
                <li><?php _e('✓ Personalización de etiquetas', 'wp-product-personalizer'); ?></li>
            </ul>
            <p><?php _e('La información personalizada se adjunta a cada pedido y es visible en la administración.', 'wp-product-personalizer'); ?></p>
        </div>
    </div>
</div>