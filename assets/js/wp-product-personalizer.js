/**
 * JavaScript para el frontend del plugin de personalización de productos
 */
(function($) {
    'use strict';
    
    // Cuando el DOM esté listo
    $(document).ready(function() {
        
        // Manejar la vista previa y carga de la imagen
        $('#wppp_custom_image').on('change', function() {
            var input = this;
            var previewDiv = $('#wppp_image_preview');
            var imageDataField = $('#wppp_custom_image_data');
            
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    // Mostrar vista previa
                    previewDiv.html('<img src="' + e.target.result + '" class="wppp-preview-image" /><p class="wppp-upload-status">Subiendo imagen...</p>');
                    
                    // Subir imagen mediante AJAX
                    var formData = new FormData();
                    formData.append('action', 'wppp_upload_image');
                    formData.append('nonce', wppp_vars.nonce);
                    formData.append('file', input.files[0]);
                    
                    $.ajax({
                        url: wppp_vars.ajax_url,
                        type: 'POST',
                        data: formData,
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (response.success) {
                                // Guardar los datos de la imagen en el campo oculto
                                imageDataField.val(JSON.stringify(response.data));
                                previewDiv.find('.wppp-upload-status').text('Imagen subida correctamente').addClass('success');
                                
                                // Actualizar vista previa con la URL real
                                previewDiv.find('img').attr('src', response.data.url);
                            } else {
                                previewDiv.find('.wppp-upload-status').text('Error al subir la imagen: ' + response.data).addClass('error');
                            }
                        },
                        error: function() {
                            previewDiv.find('.wppp-upload-status').text('Error de conexión al subir la imagen').addClass('error');
                        }
                    });
                }
                
                reader.readAsDataURL(input.files[0]);
            } else {
                previewDiv.html('');
                imageDataField.val('');
            }
        });
        
        // Validar el formulario antes de añadir al carrito
        $('form.cart').on('submit', function(e) {
            var requireImage = wppp_vars.require_image == '1';
            var requireMessage = wppp_vars.require_message == '1';
            var imageDataField = $('#wppp_custom_image_data');
            var messageField = $('#wppp_custom_message');
            var isValid = true;
            
            // Comprobar si el campo de imagen es requerido y está vacío
            if (requireImage && imageDataField.val() === '') {
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
        
        // Convertir el formulario para que acepte archivos
        $('form.cart').attr('enctype', 'multipart/form-data');
    });
    
})(jQuery);