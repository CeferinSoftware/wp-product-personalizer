/**
 * JavaScript para el frontend del plugin de personalización de productos
 */
(function($) {
    'use strict';
    
    // Cuando el DOM esté listo
    $(document).ready(function() {
        
        // Mostrar vista previa de la imagen cuando se selecciona un archivo
        $('#wppp_custom_image').on('change', function() {
            var input = this;
            var previewDiv = $('#wppp_image_preview');
            
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    previewDiv.html('<img src="' + e.target.result + '" class="wppp-preview-image" />');
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewDiv.html('');
            }
        });
        
        // Validar el formulario antes de añadir al carrito
        $('form.cart').on('submit', function(e) {
            var requireImage = wppp_vars.require_image == '1';
            var requireMessage = wppp_vars.require_message == '1';
            var imageField = $('#wppp_custom_image');
            var messageField = $('#wppp_custom_message');
            var isValid = true;
            
            // Comprobar si el campo de imagen es requerido y está vacío
            if (requireImage && (!imageField[0].files || !imageField[0].files[0])) {
                alert('Por favor, sube una imagen personalizada.');
                isValid = false;
            }
            
            // Comprobar si el campo de mensaje es requerido y está vacío
            if (requireMessage && messageField.val().trim() === '') {
                alert('Por favor, introduce un mensaje personalizado.');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
                return false;
            }
        });
        
        // Asegurarse de que el formulario puede manejar archivos
        $('form.cart').attr('enctype', 'multipart/form-data');
    });
    
})(jQuery);