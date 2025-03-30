jQuery(document).ready(function($) {
    // Vista previa de la imagen
    $('#wppp_custom_image').on('change', function() {
        var input = this;
        
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('#wppp_image_preview').html('<img src="' + e.target.result + '" style="max-width: 100%; height: auto;">');
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    });
    
    // Modificar el formulario para gestionar la subida de archivos
    $('form.cart').attr('enctype', 'multipart/form-data');
    
    // Validación al enviar el formulario
    $('form.cart').on('submit', function(e) {
        var requireImage = wppp_vars.require_image;
        var requireMessage = wppp_vars.require_message;
        var formValid = true;
        var errorMessages = [];
        
        // Validar mensaje si es requerido
        if (requireMessage == '1' || requireMessage === true) {
            if (!$('#wppp_custom_message').val().trim()) {
                formValid = false;
                errorMessages.push('Por favor, introduce un mensaje personalizado.');
                $('#wppp_custom_message').addClass('wppp-error');
            } else {
                $('#wppp_custom_message').removeClass('wppp-error');
            }
        }
        
        // Validar imagen si es requerida
        if (requireImage == '1' || requireImage === true) {
            if (!$('#wppp_custom_image').val()) {
                formValid = false;
                errorMessages.push('Por favor, sube una imagen personalizada.');
                $('#wppp_custom_image').addClass('wppp-error');
            } else {
                $('#wppp_custom_image').removeClass('wppp-error');
            }
        }
        
        // Mostrar errores si hay alguno
        if (!formValid) {
            e.preventDefault();
            
            // Eliminar mensajes de error anteriores
            $('.wppp-error-message').remove();
            
            // Añadir nuevo mensaje de error
            var errorHTML = '<div class="wppp-error-message">';
            $.each(errorMessages, function(index, message) {
                errorHTML += '<p>' + message + '</p>';
            });
            errorHTML += '</div>';
            
            $('.wp-product-personalizer-fields').prepend(errorHTML);
            
            // Scroll hasta el mensaje de error
            $('html, body').animate({
                scrollTop: $('.wp-product-personalizer-fields').offset().top - 100
            }, 500);
        }
    });
});