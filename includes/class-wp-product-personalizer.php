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
        
        // Compatibilidad con almacenamiento de pedidos de alto rendimiento
        if ($this->is_hpos_enabled()) {
            // Usar los nuevos hooks para HPOS
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_personalization_to_order_items_hpos'), 10, 4);
            add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_personalization_hpos'), 10, 1);
        } else {
            // Usar los hooks tradicionales
            add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_personalization_to_order_items'), 10, 4);
            add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_order_personalization'), 10, 1);
        }
        
        // Hooks para procesar la subida de imágenes
        add_action('wp_ajax_wppp_upload_image', array($this, 'handle_image_upload'));
        add_action('wp_ajax_nopriv_wppp_upload_image', array($this, 'handle_image_upload'));
        
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
        
        echo '<div class="wp-product-personalizer-fields">';
        
        // Campo de imagen
        echo '<div class="wp-product-personalizer-image-field">';
        echo '<label for="wppp_custom_image">' . esc_html($image_label) . ($require_image ? ' <span class="required">*</span>' : '') . '</label>';
        echo '<input type="file" id="wppp_custom_image" name="wppp_custom_image" accept="image/*" ' . ($require_image ? 'required' : '') . '>';
        echo '<div id="wppp_image_preview"></div>';
        echo '<input type="hidden" name="wppp_custom_image_data" id="wppp_custom_image_data" value="">';
        echo '</div>';
        
        // Campo de mensaje
        echo '<div class="wp-product-personalizer-message-field">';
        echo '<label for="wppp_custom_message">' . esc_html($message_label) . ($require_message ? ' <span class="required">*</span>' : '') . '</label>';
        echo '<textarea id="wppp_custom_message" name="wppp_custom_message" rows="3" ' . ($require_message ? 'required' : '') . '></textarea>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Manejar la subida de imágenes vía AJAX
     */
    public function handle_image_upload() {
        // Verificar nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wppp_upload_image')) {
            wp_send_json_error('Verificación de seguridad fallida');
            die();
        }
        
        if (!isset($_FILES['file']) || empty($_FILES['file']['name'])) {
            wp_send_json_error('No se ha subido ningún archivo');
            die();
        }
        
        // Configurar directorio de carga
        $upload_dir = wp_upload_dir();
        $personalized_dir = $upload_dir['basedir'] . '/personalized-products';
        
        // Asegurarse de que el directorio existe
        if (!file_exists($personalized_dir)) {
            wp_mkdir_p($personalized_dir);
        }
        
        // Procesar la imagen
        $file = $_FILES['file'];
        $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'personalized-' . time() . '-' . wp_rand(1000, 9999) . '.' . $file_extension;
        $file_path = $personalized_dir . '/' . $filename;
        
        // Mover archivo
        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            $file_url = $upload_dir['baseurl'] . '/personalized-products/' . $filename;
            wp_send_json_success(array(
                'path' => $file_path,
                'url' => $file_url,
                'name' => $filename
            ));
        } else {
            wp_send_json_error('Error al guardar la imagen');
        }
        
        die();
    }
    
    /**
     * Añadir datos personalizados al carrito
     */
    public function add_personalization_to_cart($cart_item_data, $product_id, $variation_id) {
        if (!$this->should_display_fields()) {
            return $cart_item_data;
        }
        
        // Procesar el mensaje personalizado
        if (isset($_POST['wppp_custom_message']) && !empty($_POST['wppp_custom_message'])) {
            $cart_item_data['wppp_custom_message'] = sanitize_textarea_field($_POST['wppp_custom_message']);
        }
        
        // Procesar la imagen personalizada (usando los datos subidos por AJAX)
        if (isset($_POST['wppp_custom_image_data']) && !empty($_POST['wppp_custom_image_data'])) {
            $image_data = json_decode(stripslashes($_POST['wppp_custom_image_data']), true);
            if (is_array($image_data) && isset($image_data['url']) && isset($image_data['path']) && isset($image_data['name'])) {
                $cart_item_data['wppp_custom_image'] = $image_data;
            }
        }
        
        // Si añadimos datos personalizados, hacer que el item sea único en el carrito
        if (!empty($cart_item_data['wppp_custom_message']) || !empty($cart_item_data['wppp_custom_image'])) {
            $cart_item_data['unique_key'] = md5(microtime() . rand());
        }
        
        return $cart_item_data;
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
     * Añadir datos de personalización a los items del pedido (sistema tradicional)
     */
    public function add_personalization_to_order_items($item, $cart_item_key, $values, $order) {
        // Guardar el mensaje personalizado
        if (isset($values['wppp_custom_message']) && !empty($values['wppp_custom_message'])) {
            $item->add_meta_data(__('Mensaje personalizado', 'wp-product-personalizer'), $values['wppp_custom_message']);
        }
        
        // Guardar la imagen personalizada
        if (isset($values['wppp_custom_image']) && !empty($values['wppp_custom_image']['url'])) {
            $item->add_meta_data(__('Imagen personalizada', 'wp-product-personalizer'), $values['wppp_custom_image']['url']);
            $item->add_meta_data('_wppp_custom_image_path', $values['wppp_custom_image']['path'], true);
            $item->add_meta_data('_wppp_custom_image_name', $values['wppp_custom_image']['name'], true);
        }
    }
    
    /**
     * Añadir datos de personalización a los items del pedido (compatible con HPOS)
     */
    public function add_personalization_to_order_items_hpos($item, $cart_item_key, $values, $order) {
        // Para HPOS, el método es similar ya que WooCommerce maneja la abstracción
        if (isset($values['wppp_custom_message']) && !empty($values['wppp_custom_message'])) {
            $item->add_meta_data(__('Mensaje personalizado', 'wp-product-personalizer'), $values['wppp_custom_message']);
        }
        
        if (isset($values['wppp_custom_image']) && !empty($values['wppp_custom_image']['url'])) {
            $item->add_meta_data(__('Imagen personalizada', 'wp-product-personalizer'), $values['wppp_custom_image']['url']);
            $item->add_meta_data('_wppp_custom_image_path', $values['wppp_custom_image']['path'], true);
            $item->add_meta_data('_wppp_custom_image_name', $values['wppp_custom_image']['name'], true);
        }
    }
    
    /**
     * Mostrar información de personalización en la página de administración del pedido (sistema tradicional)
     */
    public function display_order_personalization($order) {
        $items = $order->get_items();
        
        foreach ($items as $item_id => $item) {
            $message = $item->get_meta('Mensaje personalizado');
            $image_url = $item->get_meta('Imagen personalizada');
            
            if (!empty($message) || !empty($image_url)) {
                echo '<div class="order-personalization">';
                echo '<h3>' . __('Personalización para', 'wp-product-personalizer') . ' ' . $item->get_name() . '</h3>';
                
                if (!empty($message)) {
                    echo '<p><strong>' . __('Mensaje personalizado', 'wp-product-personalizer') . ':</strong> ' . esc_html($message) . '</p>';
                }
                
                if (!empty($image_url)) {
                    echo '<p><strong>' . __('Imagen personalizada', 'wp-product-personalizer') . ':</strong></p>';
                    echo '<p><a href="' . esc_url($image_url) . '" target="_blank">';
                    echo '<img src="' . esc_url($image_url) . '" style="max-width: 150px; max-height: 150px;">';
                    echo '</a></p>';
                }
                
                echo '</div>';
            }
        }
    }
    
    /**
     * Mostrar información de personalización en la página de administración del pedido (compatible con HPOS)
     */
    public function display_order_personalization_hpos($order) {
        // Asegurarse de que tenemos un objeto de pedido compatible con HPOS
        if (!($order instanceof \WC_Order)) {
            return;
        }
        
        // Obtener los ítems del pedido usando el sistema HPOS
        $items = $order->get_items();
        
        foreach ($items as $item_id => $item) {
            $message = $item->get_meta('Mensaje personalizado');
            $image_url = $item->get_meta('Imagen personalizada');
            
            if (!empty($message) || !empty($image_url)) {
                echo '<div class="order-personalization">';
                echo '<h3>' . __('Personalización para', 'wp-product-personalizer') . ' ' . $item->get_name() . '</h3>';
                
                if (!empty($message)) {
                    echo '<p><strong>' . __('Mensaje personalizado', 'wp-product-personalizer') . ':</strong> ' . esc_html($message) . '</p>';
                }
                
                if (!empty($image_url)) {
                    echo '<p><strong>' . __('Imagen personalizada', 'wp-product-personalizer') . ':</strong></p>';
                    echo '<p><a href="' . esc_url($image_url) . '" target="_blank">';
                    echo '<img src="' . esc_url($image_url) . '" style="max-width: 150px; max-height: 150px;">';
                    echo '</a></p>';
                }
                
                echo '</div>';
            }
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