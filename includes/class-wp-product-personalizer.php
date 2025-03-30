<?php
/**
 * Clase principal que define la funcionalidad del plugin
 */
class WP_Product_Personalizer {

    /**
     * Inicializar el plugin y definir sus hooks
     */
    public function run() {
        // Cargar dependencias
        $this->load_dependencies();
        
        // Definir hooks admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Definir hooks públicos
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_personalization_fields'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_personalization_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_personalization_cart_item_data'), 10, 2);
        
        // Hooks para procesar imágenes
        add_action('wp_ajax_wppp_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_nopriv_wppp_upload_image', array($this, 'handle_image_upload'));
        
        // Hooks para añadir datos a pedidos (usando un hook simple y directo que funciona con ambos sistemas)
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_personalization_to_order_items'), 10, 4);
        
        // Hook para mostrar datos personalizados en los pedidos
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_personalization'), 10, 1);
        
        // Hook para emails de pedidos
        add_action('woocommerce_email_order_meta', array($this, 'add_personalization_to_emails'), 10, 3);
        
        // Scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
    }
    
    /**
     * Cargar dependencias del plugin
     */
    private function load_dependencies() {
        // Aquí se pueden cargar clases adicionales si se requieren
    }
    
    /**
     * Comprobar si el almacenamiento de pedidos de alto rendimiento está activado
     */
    private function is_hpos_enabled() {
        if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil')) {
            return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
        }
        return false;
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Personalizador de Productos', 'wp-product-personalizer'),
            __('Personalizador', 'wp-product-personalizer'),
            'manage_options',
            'wp-product-personalizer',
            array($this, 'display_admin_page'),
            'dashicons-format-image',
            56
        );
    }
    
    /**
     * Registrar ajustes del plugin
     */
    public function register_settings() {
        register_setting('wppp_options', 'wppp_enable_all_products', array(
            'type' => 'boolean',
            'default' => false,
        ));
        
        register_setting('wppp_options', 'wppp_product_ids', array(
            'type' => 'string',
            'default' => '',
        ));
        
        register_setting('wppp_options', 'wppp_image_upload_label', array(
            'type' => 'string',
            'default' => __('Sube tu imagen personalizada', 'wp-product-personalizer'),
        ));
        
        register_setting('wppp_options', 'wppp_message_label', array(
            'type' => 'string',
            'default' => __('Añade tu mensaje personalizado', 'wp-product-personalizer'),
        ));
        
        register_setting('wppp_options', 'wppp_require_image', array(
            'type' => 'boolean',
            'default' => false,
        ));
        
        register_setting('wppp_options', 'wppp_require_message', array(
            'type' => 'boolean',
            'default' => false,
        ));
    }
    
    /**
     * Mostrar página de administración
     */
    public function display_admin_page() {
        require_once WPPP_PLUGIN_DIR . 'admin/admin-page.php';
    }
    
    /**
     * Determinar si el producto actual debe tener campos de personalización
     */
    private function should_display_fields() {
        global $product;
        
        if (!$product) {
            return false;
        }
        
        // Si está habilitado para todos los productos
        if (get_option('wppp_enable_all_products', false)) {
            return true;
        }
        
        // Si el producto está en la lista de IDs específicos
        $product_ids = get_option('wppp_product_ids', '');
        if (!empty($product_ids)) {
            $ids_array = array_map('trim', explode(',', $product_ids));
            if (in_array($product->get_id(), $ids_array)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Mostrar campos de personalización en la página de producto
     */
    public function display_personalization_fields() {
        if (!$this->should_display_fields()) {
            return;
        }
        
        $image_label = get_option('wppp_image_upload_label', __('Sube tu imagen personalizada', 'wp-product-personalizer'));
        $message_label = get_option('wppp_message_label', __('Añade tu mensaje personalizado', 'wp-product-personalizer'));
        $require_image = get_option('wppp_require_image', false);
        $require_message = get_option('wppp_require_message', false);
        
        // Empezamos con un formulario visible normal sin AJAX para simplificar
        echo '<div class="wp-product-personalizer-fields">';
        
        // Campo de mensaje
        echo '<div class="wp-product-personalizer-message-field">';
        echo '<label for="wppp_custom_message">' . esc_html($message_label) . ($require_message ? ' <span class="required">*</span>' : '') . '</label>';
        echo '<textarea id="wppp_custom_message" name="wppp_custom_message" rows="3" ' . ($require_message ? 'required' : '') . '></textarea>';
        echo '</div>';
        
        // Campo de imagen - simplificado para evitar problemas con AJAX
        echo '<div class="wp-product-personalizer-image-field">';
        echo '<label for="wppp_custom_image">' . esc_html($image_label) . ($require_image ? ' <span class="required">*</span>' : '') . '</label>';
        echo '<input type="file" id="wppp_custom_image" name="wppp_custom_image" accept="image/*" ' . ($require_image ? 'required' : '') . '>';
        echo '<div id="wppp_image_preview"></div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Manejar la subida de imágenes mediante AJAX
     */
    public function handle_image_upload() {
        check_ajax_referer('wppp_upload_image', 'nonce');
        
        $upload = wp_handle_upload($_FILES['file'], array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error($upload['error']);
        } else {
            wp_send_json_success($upload);
        }
        
        wp_die();
    }
    
    /**
     * Añadir datos personalizados al carrito
     */
    public function add_personalization_to_cart($cart_item_data, $product_id, $variation_id) {
        if (!$this->should_display_fields()) {
            return $cart_item_data;
        }
        
        // Guardar mensaje personalizado
        if (isset($_POST['wppp_custom_message']) && !empty($_POST['wppp_custom_message'])) {
            $cart_item_data['wppp_custom_message'] = sanitize_textarea_field($_POST['wppp_custom_message']);
        }
        
        // Procesar la imagen personalizada
        if (isset($_FILES['wppp_custom_image']) && !empty($_FILES['wppp_custom_image']['name'])) {
            // Usamos el sistema de manejo de archivos de WordPress
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            
            // Configuramos un directorio personalizado
            add_filter('upload_dir', array($this, 'custom_upload_dir'));
            
            // Subir el archivo
            $upload = wp_handle_upload($_FILES['wppp_custom_image'], array('test_form' => false));
            
            // Restaurar directorio original
            remove_filter('upload_dir', array($this, 'custom_upload_dir'));
            
            if (!isset($upload['error']) && isset($upload['url']) && isset($upload['file'])) {
                $cart_item_data['wppp_custom_image'] = array(
                    'url' => $upload['url'],
                    'file' => $upload['file'],
                    'name' => basename($upload['file'])
                );
            }
        }
        
        // Si añadimos datos personalizados, hacer que el item sea único en el carrito
        if (!empty($cart_item_data['wppp_custom_message']) || !empty($cart_item_data['wppp_custom_image'])) {
            $cart_item_data['wppp_unique_key'] = md5(microtime() . rand());
        }
        
        return $cart_item_data;
    }
    
    /**
     * Personalizar el directorio de carga para las imágenes
     */
    public function custom_upload_dir($uploads) {
        $uploads['subdir'] = '/personalized-products';
        $uploads['path'] = $uploads['basedir'] . $uploads['subdir'];
        $uploads['url'] = $uploads['baseurl'] . $uploads['subdir'];
        
        if (!file_exists($uploads['path'])) {
            wp_mkdir_p($uploads['path']);
        }
        
        return $uploads;
    }
    
    /**
     * Mostrar datos de personalización en el carrito
     */
    public function display_personalization_cart_item_data($item_data, $cart_item) {
        if (isset($cart_item['wppp_custom_message']) && !empty($cart_item['wppp_custom_message'])) {
            $item_data[] = array(
                'key' => __('Mensaje personalizado', 'wp-product-personalizer'),
                'value' => wc_clean($cart_item['wppp_custom_message']),
                'display' => '',
            );
        }
        
        if (isset($cart_item['wppp_custom_image']) && !empty($cart_item['wppp_custom_image']['url'])) {
            $item_data[] = array(
                'key' => __('Imagen personalizada', 'wp-product-personalizer'),
                'value' => sprintf('<a href="%s" target="_blank">%s</a>', 
                    esc_url($cart_item['wppp_custom_image']['url']), 
                    __('Ver imagen personalizada', 'wp-product-personalizer')
                ),
                'display' => '',
            );
        }
        
        return $item_data;
    }
    
    /**
     * Añadir datos de personalización a los items del pedido
     */
    public function add_personalization_to_order_items($item, $cart_item_key, $values, $order) {
        // Guardar mensaje personalizado
        if (isset($values['wppp_custom_message']) && !empty($values['wppp_custom_message'])) {
            $item->update_meta_data('wppp_custom_message', $values['wppp_custom_message']);
        }
        
        // Guardar imagen personalizada
        if (isset($values['wppp_custom_image']) && !empty($values['wppp_custom_image']['url'])) {
            $item->update_meta_data('wppp_custom_image_url', $values['wppp_custom_image']['url']);
            if (isset($values['wppp_custom_image']['file'])) {
                $item->update_meta_data('wppp_custom_image_file', $values['wppp_custom_image']['file']);
            }
            if (isset($values['wppp_custom_image']['name'])) {
                $item->update_meta_data('wppp_custom_image_name', $values['wppp_custom_image']['name']);
            }
        }
    }
    
    /**
     * Mostrar información de personalización en la página de administración del pedido
     */
    public function display_order_personalization($order) {
        if (!$order) {
            return;
        }
        
        echo '<div class="wppp-order-personalization">';
        echo '<h3>' . __('Personalizaciones de productos', 'wp-product-personalizer') . '</h3>';
        
        $found_personalization = false;
        
        // Procesar cada ítem del pedido
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $custom_message = $item->get_meta('wppp_custom_message');
            $custom_image_url = $item->get_meta('wppp_custom_image_url');
            
            // Si hay personalización para este ítem
            if (!empty($custom_message) || !empty($custom_image_url)) {
                $found_personalization = true;
                
                echo '<div class="wppp-item-personalization">';
                echo '<h4>' . sprintf(__('Personalización para: %s', 'wp-product-personalizer'), esc_html($product_name)) . '</h4>';
                
                // Mostrar mensaje personalizado
                if (!empty($custom_message)) {
                    echo '<div class="wppp-custom-message">';
                    echo '<strong>' . __('Mensaje personalizado:', 'wp-product-personalizer') . '</strong>';
                    echo '<p>' . esc_html($custom_message) . '</p>';
                    echo '</div>';
                }
                
                // Mostrar imagen personalizada
                if (!empty($custom_image_url)) {
                    echo '<div class="wppp-custom-image">';
                    echo '<strong>' . __('Imagen personalizada:', 'wp-product-personalizer') . '</strong><br>';
                    echo '<a href="' . esc_url($custom_image_url) . '" target="_blank">';
                    echo '<img src="' . esc_url($custom_image_url) . '" style="max-width: 150px; max-height: 150px; margin-top: 10px; border: 1px solid #ddd; padding: 5px;">';
                    echo '</a>';
                    echo '</div>';
                }
                
                echo '</div>';
            }
        }
        
        if (!$found_personalization) {
            echo '<p>' . __('No hay personalizaciones para este pedido.', 'wp-product-personalizer') . '</p>';
        }
        
        echo '</div>';
    }
    
    /**
     * Añadir información de personalización a los emails de pedidos
     */
    public function add_personalization_to_emails($order, $sent_to_admin, $plain_text) {
        if (!$order) {
            return;
        }
        
        $found_personalization = false;
        
        // Empezar tabla HTML para formato normal o texto plano para emails de texto
        if (!$plain_text) {
            echo '<h2>' . __('Personalizaciones de productos', 'wp-product-personalizer') . '</h2>';
            echo '<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; margin-bottom: 20px; border: 1px solid #e5e5e5;">';
            echo '<thead><tr><th>' . __('Producto', 'wp-product-personalizer') . '</th><th>' . __('Personalización', 'wp-product-personalizer') . '</th></tr></thead><tbody>';
        } else {
            echo __('Personalizaciones de productos', 'wp-product-personalizer') . "\n\n";
        }
        
        // Procesar cada ítem del pedido
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $custom_message = $item->get_meta('wppp_custom_message');
            $custom_image_url = $item->get_meta('wppp_custom_image_url');
            
            // Si hay personalización para este ítem
            if (!empty($custom_message) || !empty($custom_image_url)) {
                $found_personalization = true;
                
                if (!$plain_text) {
                    echo '<tr><td>' . esc_html($product_name) . '</td><td>';
                    
                    if (!empty($custom_message)) {
                        echo '<strong>' . __('Mensaje:', 'wp-product-personalizer') . '</strong> ' . esc_html($custom_message) . '<br>';
                    }
                    
                    if (!empty($custom_image_url)) {
                        echo '<strong>' . __('Imagen:', 'wp-product-personalizer') . '</strong> <a href="' . esc_url($custom_image_url) . '" target="_blank">' . __('Ver imagen', 'wp-product-personalizer') . '</a>';
                    }
                    
                    echo '</td></tr>';
                } else {
                    echo esc_html($product_name) . "\n";
                    
                    if (!empty($custom_message)) {
                        echo __('Mensaje:', 'wp-product-personalizer') . ' ' . esc_html($custom_message) . "\n";
                    }
                    
                    if (!empty($custom_image_url)) {
                        echo __('Imagen:', 'wp-product-personalizer') . ' ' . esc_url($custom_image_url) . "\n";
                    }
                    
                    echo "\n";
                }
            }
        }
        
        if (!$found_personalization) {
            if (!$plain_text) {
                echo '<tr><td colspan="2">' . __('No hay personalizaciones para este pedido.', 'wp-product-personalizer') . '</td></tr>';
            } else {
                echo __('No hay personalizaciones para este pedido.', 'wp-product-personalizer') . "\n";
            }
        }
        
        // Cerrar tabla HTML para formato normal
        if (!$plain_text) {
            echo '</tbody></table>';
        } else {
            echo "\n\n";
        }
    }
    
    /**
     * Enqueue scripts públicos
     */
    public function enqueue_scripts() {
        if (is_product() && $this->should_display_fields()) {
            wp_enqueue_style('wp-product-personalizer', WPPP_PLUGIN_URL . 'assets/css/wp-product-personalizer.css', array(), WPPP_VERSION);
            wp_enqueue_script('wp-product-personalizer', WPPP_PLUGIN_URL . 'assets/js/wp-product-personalizer.js', array('jquery'), WPPP_VERSION, true);
            
            // Pasar datos al script
            wp_localize_script('wp-product-personalizer', 'wppp_vars', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'require_image' => get_option('wppp_require_image', false),
                'require_message' => get_option('wppp_require_message', false),
                'nonce' => wp_create_nonce('wppp_upload_image')
            ));
        }
    }
    
    /**
     * Enqueue scripts admin
     */
    public function admin_enqueue_scripts($hook) {
        if ('toplevel_page_wp-product-personalizer' !== $hook) {
            return;
        }
        
        wp_enqueue_style('wp-product-personalizer-admin', WPPP_PLUGIN_URL . 'assets/css/wp-product-personalizer-admin.css', array(), WPPP_VERSION);
        wp_enqueue_script('wp-product-personalizer-admin', WPPP_PLUGIN_URL . 'assets/js/wp-product-personalizer-admin.js', array('jquery'), WPPP_VERSION, true);
    }
}