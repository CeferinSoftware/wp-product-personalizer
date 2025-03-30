<?php
class WP_Product_Personalizer {
    public function __construct() {
        // Hooks para el frontend
        add_action('woocommerce_before_add_to_cart_button', array($this, 'display_personalization_fields'));
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_personalization_to_cart'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'display_personalization_in_cart'), 10, 2);
        
        // Hooks para mostrar en checkout y pedido
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'add_personalization_to_order_item'), 10, 4);
        add_action('woocommerce_after_order_itemmeta', array($this, 'display_personalization_in_order'), 10, 3);
        
        // Hooks para el admin
        add_action('woocommerce_admin_order_item_headers', array($this, 'add_personalization_column_header'));
        add_action('woocommerce_admin_order_item_values', array($this, 'add_personalization_column_value'), 10, 3);
        
        // Enqueue scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    public function enqueue_scripts() {
        wp_enqueue_script('wppp-script', WPPP_PLUGIN_URL . 'assets/js/wp-product-personalizer.js', array('jquery'), WPPP_VERSION, true);
        wp_localize_script('wppp-script', 'wppp_vars', array(
            'require_image' => get_option('wppp_require_image', '0'),
            'require_message' => get_option('wppp_require_message', '0')
        ));
    }

    public function display_personalization_fields() {
        ?>
        <div class="wp-product-personalizer-fields">
            <h3><?php _e('Personalización del Producto', 'wp-product-personalizer'); ?></h3>
            
            <p class="form-row">
                <label for="wppp_custom_message"><?php _e('Tu mensaje personalizado:', 'wp-product-personalizer'); ?></label>
                <textarea id="wppp_custom_message" name="wppp_custom_message" rows="3" class="input-text"></textarea>
            </p>
            
            <p class="form-row">
                <label for="wppp_custom_image"><?php _e('Tu imagen personalizada:', 'wp-product-personalizer'); ?></label>
                <input type="file" id="wppp_custom_image" name="wppp_custom_image" accept="image/*" />
            </p>
            
            <div id="wppp_image_preview"></div>
        </div>
        <?php
    }

    public function add_personalization_to_cart($cart_item_data, $product_id, $variation_id) {
        if (isset($_FILES['wppp_custom_image']) && !empty($_FILES['wppp_custom_image']['name'])) {
            $upload = wp_handle_upload($_FILES['wppp_custom_image'], array('test_form' => false));
            if (!isset($upload['error'])) {
                $cart_item_data['wppp_custom_image'] = $upload['url'];
                $cart_item_data['wppp_custom_image_path'] = $upload['file'];
            }
        }

        if (isset($_POST['wppp_custom_message'])) {
            $cart_item_data['wppp_custom_message'] = sanitize_textarea_field($_POST['wppp_custom_message']);
        }

        return $cart_item_data;
    }

    public function display_personalization_in_cart($item_data, $cart_item) {
        if (isset($cart_item['wppp_custom_message'])) {
            $item_data[] = array(
                'key' => __('Mensaje Personalizado', 'wp-product-personalizer'),
                'value' => $cart_item['wppp_custom_message']
            );
        }

        if (isset($cart_item['wppp_custom_image'])) {
            $item_data[] = array(
                'key' => __('Imagen Personalizada', 'wp-product-personalizer'),
                'value' => '<img src="' . esc_url($cart_item['wppp_custom_image']) . '" style="max-width: 100px;">'
            );
        }

        return $item_data;
    }

    public function add_personalization_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['wppp_custom_message'])) {
            $item->add_meta_data('_wppp_custom_message', $values['wppp_custom_message']);
        }

        if (isset($values['wppp_custom_image'])) {
            $item->add_meta_data('_wppp_custom_image', $values['wppp_custom_image']);
        }
    }

    public function display_personalization_in_order($item_id, $item, $product) {
        $message = wc_get_order_item_meta($item_id, '_wppp_custom_message', true);
        $image = wc_get_order_item_meta($item_id, '_wppp_custom_image', true);

        if ($message) {
            echo '<p><strong>' . __('Mensaje Personalizado:', 'wp-product-personalizer') . '</strong> ' . esc_html($message) . '</p>';
        }

        if ($image) {
            echo '<p><strong>' . __('Imagen Personalizada:', 'wp-product-personalizer') . '</strong><br>';
            echo '<img src="' . esc_url($image) . '" style="max-width: 150px;"></p>';
        }
    }

    public function add_personalization_column_header($order) {
        echo '<th class="personalization">' . __('Personalización', 'wp-product-personalizer') . '</th>';
    }

    public function add_personalization_column_value($product, $item, $item_id) {
        echo '<td class="personalization">';
        $this->display_personalization_in_order($item_id, $item, $product);
        echo '</td>';
    }

    public function run() {
        // Inicializar el plugin
        if (is_admin()) {
            require_once WPPP_PLUGIN_DIR . 'admin/class-wp-product-personalizer-admin.php';
            $admin = new WP_Product_Personalizer_Admin();
        }
    }
}