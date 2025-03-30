<?php
/**
 * Plugin Name: WP Product Personalizer
 * Plugin URI: https://github.com/CeferinSoftware/wp-product-personalizer
 * Description: Permite a los clientes subir imágenes y mensajes personalizados para productos WooCommerce.
 * Version: 1.0.6
 * Author: CeferinSoftware
 * Author URI: https://github.com/CeferinSoftware
 * Text Domain: wp-product-personalizer
 * Domain Path: /languages
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('WPPP_VERSION', '1.0.6');
define('WPPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Verificar si WooCommerce está activo
 */
function wppp_check_woocommerce_active() {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wppp_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * Mostrar aviso si WooCommerce no está activo
 */
function wppp_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WP Product Personalizer requiere que WooCommerce esté instalado y activado.', 'wp-product-personalizer'); ?></p>
    </div>
    <?php
}

/**
 * Inicializar el plugin
 */
function wppp_init() {
    // Verificar que WooCommerce está activo
    if (!wppp_check_woocommerce_active()) {
        return;
    }
    
    // Cargar archivos necesarios
    require_once WPPP_PLUGIN_DIR . 'includes/class-wp-product-personalizer.php';
    
    // Crear directorio para las imágenes personalizadas
    $upload_dir = wp_upload_dir();
    $personalized_dir = $upload_dir['basedir'] . '/personalized-products';
    if (!file_exists($personalized_dir)) {
        wp_mkdir_p($personalized_dir);
    }
    
    // Inicializar el plugin
    $plugin = new WP_Product_Personalizer();
    $plugin->run();
}
add_action('plugins_loaded', 'wppp_init');

/**
 * Activación del plugin
 */
function wppp_activate() {
    // Crear directorio para las imágenes personalizadas
    $upload_dir = wp_upload_dir();
    $personalized_dir = $upload_dir['basedir'] . '/personalized-products';
    if (!file_exists($personalized_dir)) {
        wp_mkdir_p($personalized_dir);
    }
}
register_activation_hook(__FILE__, 'wppp_activate');