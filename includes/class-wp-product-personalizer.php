<?php
/**
 * Clase principal que define la funcionalidad del plugin
 */
class WP_Product_Personalizer {

    /**
     * Inicializar el plugin y definir sus hooks
     */
    public function run() {
        // Definir hooks admin
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        
        // Definir hooks públicos - MUY BÁSICO PARA ASEGURAR FUNCIONAMIENTO
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_personalization_fields'), 10);
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_personalization_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_personalization_cart_item_data'), 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_personalization_to_order_items'), 10, 4);
        
        // Hooks para FORZAR que se guarden los datos de personalización
        add_action('woocommerce_checkout_order_processed', array($this, 'save_order_custom_data'), 10, 3);
        
        // Hook para mostrar datos personalizados en los pedidos
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_personalization'), 10, 1);
        
        // Scripts y estilos
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
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
     * VERSIÓN SIMPLIFICADA PARA MÁXIMA COMPATIBILIDAD
     */
    public function display_personalization_fields() {
        if (!$this->should_display_fields()) {
            return;
        }
        
        $image_label = get_option('wppp_image_upload_label', __('Sube tu imagen personalizada', 'wp-product-personalizer'));
        $message_label = get_option('wppp_message_label', __('Añade tu mensaje personalizado', 'wp-product-personalizer'));
        $require_image = get_option('wppp_require_image', false);
        $require_message = get_option('wppp_require_message', false);
        
        ?>
        <div class="wp-product-personalizer-fields">
            <!-- Campo de mensaje -->
            <div class="wp-product-personalizer-message-field">
                <label for="wppp_custom_message"><?php echo esc_html($message_label); ?><?php echo $require_message ? ' <span class="required">*</span>' : ''; ?></label>
                <textarea id="wppp_custom_message" name="wppp_custom_message" rows="3" <?php echo $require_message ? 'required' : ''; ?>></textarea>
            </div>
            
            <!-- Campo de imagen -->
            <div class="wp-product-personalizer-image-field">
                <label for="wppp_custom_image"><?php echo esc_html($image_label); ?><?php echo $require_image ? ' <span class="required">*</span>' : ''; ?></label>
                <input type="file" id="wppp_custom_image" name="wppp_custom_image" accept="image/*" <?php echo $require_image ? 'required' : ''; ?>>
                <div id="wppp_image_preview"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Añadir datos personalizados al carrito
     * VERSIÓN SIMPLIFICADA PARA MÁXIMA COMPATIBILIDAD
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
            if (!function_exists('wp_handle_upload')) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
            }
            
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
            $cart_item_data['unique_key'] = md5(microtime() . rand());
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
                'value' => __('Imagen personalizada adjunta', 'wp-product-personalizer'),
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
            $item->add_meta_data('wppp_custom_message', $values['wppp_custom_message']);
        }
        
        // Guardar imagen personalizada
        if (isset($values['wppp_custom_image']) && !empty($values['wppp_custom_image']['url'])) {
            $item->add_meta_data('wppp_custom_image_url', $values['wppp_custom_image']['url']);
            
            if (isset($values['wppp_custom_image']['file'])) {
                $item->add_meta_data('wppp_custom_image_file', $values['wppp_custom_image']['file']);
            }
            
            if (isset($values['wppp_custom_image']['name'])) {
                $item->add_meta_data('wppp_custom_image_name', $values['wppp_custom_image']['name']);
            }
        }
    }
    
    /**
     * Guardar los datos personalizados directamente en el pedido
     * MÉTODO FORZADO para asegurar que se guarde la información
     */
    public function save_order_custom_data($order_id, $posted_data, $order) {
        // Obtenemos todos los items del carrito
        $cart_items = WC()->cart->get_cart();
        
        foreach ($cart_items as $cart_item_key => $cart_item) {
            $product_name = $cart_item['data']->get_name();
            
            // Guardar mensaje personalizado
            if (!empty($cart_item['wppp_custom_message'])) {
                // Usamos la metakey 'wppp_custom_message_{item_key}' para evitar colisiones
                update_post_meta($order_id, 'wppp_custom_message_' . sanitize_title($product_name), $cart_item['wppp_custom_message']);
            }
            
            // Guardar imagen personalizada
            if (!empty($cart_item['wppp_custom_image']) && !empty($cart_item['wppp_custom_image']['url'])) {
                update_post_meta($order_id, 'wppp_custom_image_url_' . sanitize_title($product_name), $cart_item['wppp_custom_image']['url']);
                
                if (!empty($cart_item['wppp_custom_image']['file'])) {
                    update_post_meta($order_id, 'wppp_custom_image_file_' . sanitize_title($product_name), $cart_item['wppp_custom_image']['file']);
                }
                
                if (!empty($cart_item['wppp_custom_image']['name'])) {
                    update_post_meta($order_id, 'wppp_custom_image_name_' . sanitize_title($product_name), $cart_item['wppp_custom_image']['name']);
                }
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
        
        echo '<div class="wppp-order-personalization" style="margin-top: 20px; margin-bottom: 20px; border: 1px solid #ddd; padding: 15px; background-color: #f8f8f8;">';
        echo '<h2>' . __('Personalizaciones de productos', 'wp-product-personalizer') . '</h2>';
        
        $found_personalization = false;
        $order_id = $order->get_id();
        
        // Iterar a través de los items del pedido
        foreach ($order->get_items() as $item_id => $item) {
            $product_name = $item->get_name();
            $sanitized_name = sanitize_title($product_name);
            
            // Intentar obtener datos de personalización del ítem primero
            $message = $item->get_meta('wppp_custom_message');
            $image_url = $item->get_meta('wppp_custom_image_url');
            
            // Si no hay datos en el ítem, intentar obtenerlos de los metadatos del pedido
            if (empty($message)) {
                $message = get_post_meta($order_id, 'wppp_custom_message_' . $sanitized_name, true);
            }
            
            if (empty($image_url)) {
                $image_url = get_post_meta($order_id, 'wppp_custom_image_url_' . $sanitized_name, true);
            }
            
            // Si hay personalización, mostrarla
            if (!empty($message) || !empty($image_url)) {
                $found_personalization = true;
                
                echo '<div class="wppp-item-personalization" style="margin-bottom: 15px; padding: 15px; border: 1px solid #ddd; background-color: #fff;">';
                echo '<h3>' . sprintf(__('Personalización para: %s', 'wp-product-personalizer'), esc_html($product_name)) . '</h3>';
                
                if (!empty($message)) {
                    echo '<div class="wppp-custom-message" style="margin-bottom: 15px;">';
                    echo '<strong>' . __('Mensaje personalizado:', 'wp-product-personalizer') . '</strong>';
                    echo '<p style="background: #f9f9f9; padding: 10px; border: 1px solid #eee; margin-top: 5px;">' . esc_html($message) . '</p>';
                    echo '</div>';
                }
                
                if (!empty($image_url)) {
                    echo '<div class="wppp-custom-image">';
                    echo '<strong>' . __('Imagen personalizada:', 'wp-product-personalizer') . '</strong><br>';
                    echo '<a href="' . esc_url($image_url) . '" target="_blank">';
                    echo '<img src="' . esc_url($image_url) . '" style="max-width: 200px; max-height: 200px; margin-top: 10px; border: 1px solid #ddd; padding: 5px; background: #fff;">';
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
                'require_message' => get_option('wppp_require_message', false)
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