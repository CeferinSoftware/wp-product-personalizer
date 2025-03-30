/**
 * JavaScript para el panel de administración
 */
(function($) {
    'use strict';
    
    $(document).ready(function() {
        // Mostrar/ocultar el campo de IDs de productos según la opción global
        $('input[name="wppp_enable_all_products"]').change(function() {
            if ($(this).is(':checked')) {
                $('#product_ids_row').hide();
            } else {
                $('#product_ids_row').show();
            }
        });
    });
    
})(jQuery);