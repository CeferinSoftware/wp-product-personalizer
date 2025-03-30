<?php
/**
 * Plugin Name: WP Product Personalizer
 * Plugin URI: https://github.com/CeferinSoftware/wp-product-personalizer
 * Description: Permite a los clientes subir imágenes personalizadas y añadir mensajes a los productos.
 * Version: 1.0.0
 * Author: CeferinSoftware
 * Author URI: https://github.com/CeferinSoftware
 * Text Domain: wp-product-personalizer
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * WC requires at least: 3.0
 * WC tested up to: 8.0
 */

// Si este archivo es llamado directamente, abortar.
if (!defined('WPINC')) {
    die;
}

// Definir constantes del plugin
define('WPPP_VERSION', '1.0.0');
define('WPPP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPPP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPPP_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Cargar dependencias
require_once WPPP_PLUGIN_DIR . 'includes/class-wp-product-personalizer.php';

// Iniciar el plugin
function run_wp_product_personalizer() {
    $plugin = new WP_Product_Personalizer();
    $plugin->run();
}

// Asegurarse de que WooCommerce está activo
register_activation_hook(__FILE__, 'wppp_activation_check');
function wppp_activation_check() {
    if (!class_exists('WooCommerce')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('Este plugin requiere que WooCommerce esté instalado y activado.');
    }
}

// Crear directorio de uploads si no existe
register_activation_hook(__FILE__, 'wppp_create_upload_dir');
function wppp_create_upload_dir() {
    $upload_dir = wp_upload_dir();
    $personalized_dir = $upload_dir['basedir'] . '/personalized-products';
    
    if (!file_exists($personalized_dir)) {
        wp_mkdir_p($personalized_dir);
    }
}

// Iniciar el plugin cuando todos los plugins estén cargados
add_action('plugins_loaded', 'run_wp_product_personalizer');