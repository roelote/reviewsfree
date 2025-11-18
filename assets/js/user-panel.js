/**
 * JavaScript para Panel de Usuario - Comentarios Free Plugin
 */

// Lista de países - DEBE estar FUERA del scope para ser accesible globalmente
const countriesData = {
        "": {"name": "", "flag": ""},
        "AF": {"name": "Afganistán", "flag": "🇦🇫"},
        "AL": {"name": "Albania", "flag": "🇦🇱"},
        "DE": {"name": "Alemania", "flag": "🇩🇪"},
        "AD": {"name": "Andorra", "flag": "🇦🇩"},
        "AR": {"name": "Argentina", "flag": "🇦🇷"},
        "AU": {"name": "Australia", "flag": "🇦🇺"},
        "AT": {"name": "Austria", "flag": "🇦🇹"},
        "BE": {"name": "Bélgica", "flag": "🇧🇪"},
        "BO": {"name": "Bolivia", "flag": "🇧🇴"},
        "BR": {"name": "Brasil", "flag": "🇧🇷"},
        "CA": {"name": "Canadá", "flag": "🇨🇦"},
        "CL": {"name": "Chile", "flag": "🇨🇱"},
        "CN": {"name": "China", "flag": "🇨🇳"},
        "CO": {"name": "Colombia", "flag": "🇨🇴"},
        "CR": {"name": "Costa Rica", "flag": "🇨🇷"},
        "CU": {"name": "Cuba", "flag": "🇨🇺"},
        "DK": {"name": "Dinamarca", "flag": "🇩🇰"},
        "EC": {"name": "Ecuador", "flag": "🇪🇨"},
        "EG": {"name": "Egipto", "flag": "🇪🇬"},
        "SV": {"name": "El Salvador", "flag": "🇸🇻"},
        "AE": {"name": "Emiratos Árabes Unidos", "flag": "🇦🇪"},
        "ES": {"name": "España", "flag": "🇪🇸"},
        "US": {"name": "Estados Unidos", "flag": "🇺🇸"},
        "PH": {"name": "Filipinas", "flag": "🇵🇭"},
        "FI": {"name": "Finlandia", "flag": "🇫🇮"},
        "FR": {"name": "Francia", "flag": "🇫🇷"},
        "GR": {"name": "Grecia", "flag": "🇬🇷"},
        "GT": {"name": "Guatemala", "flag": "🇬🇹"},
        "HN": {"name": "Honduras", "flag": "🇭🇳"},
        "HK": {"name": "Hong Kong", "flag": "🇭🇰"},
        "HU": {"name": "Hungría", "flag": "🇭🇺"},
        "IN": {"name": "India", "flag": "🇮🇳"},
        "ID": {"name": "Indonesia", "flag": "🇮🇩"},
        "IE": {"name": "Irlanda", "flag": "🇮🇪"},
        "IS": {"name": "Islandia", "flag": "🇮🇸"},
        "IL": {"name": "Israel", "flag": "🇮🇱"},
        "IT": {"name": "Italia", "flag": "🇮🇹"},
        "JM": {"name": "Jamaica", "flag": "🇯🇲"},
        "JP": {"name": "Japón", "flag": "🇯🇵"},
        "MX": {"name": "México", "flag": "🇲🇽"},
        "NI": {"name": "Nicaragua", "flag": "🇳🇮"},
        "NO": {"name": "Noruega", "flag": "🇳🇴"},
        "NZ": {"name": "Nueva Zelanda", "flag": "🇳🇿"},
        "NL": {"name": "Países Bajos", "flag": "🇳🇱"},
        "PA": {"name": "Panamá", "flag": "🇵🇦"},
        "PY": {"name": "Paraguay", "flag": "🇵🇾"},
        "PE": {"name": "Perú", "flag": "🇵🇪"},
        "PL": {"name": "Polonia", "flag": "🇵🇱"},
        "PT": {"name": "Portugal", "flag": "🇵🇹"},
        "PR": {"name": "Puerto Rico", "flag": "🇵🇷"},
        "GB": {"name": "Reino Unido", "flag": "🇬🇧"},
        "CZ": {"name": "República Checa", "flag": "🇨🇿"},
        "DO": {"name": "República Dominicana", "flag": "🇩🇴"},
        "RU": {"name": "Rusia", "flag": "🇷🇺"},
        "SE": {"name": "Suecia", "flag": "🇸🇪"},
        "CH": {"name": "Suiza", "flag": "🇨🇭"},
        "TH": {"name": "Tailandia", "flag": "🇹🇭"},
        "TW": {"name": "Taiwán", "flag": "🇹🇼"},
        "TR": {"name": "Turquía", "flag": "🇹🇷"},
        "UA": {"name": "Ucrania", "flag": "🇺🇦"},
        "UY": {"name": "Uruguay", "flag": "🇺🇾"},
        "VE": {"name": "Venezuela", "flag": "🇻🇪"},
        "VN": {"name": "Vietnam", "flag": "🇻🇳"},
        "ZA": {"name": "Sudáfrica", "flag": "🇿🇦"}
};

// Inicializar plugin cuando jQuery esté listo
(function($) {
    'use strict';

    $(document).ready(function() {
        initializeUserPanel();
    });

    /**
     * Inicializar panel de usuario
     */
    function initializeUserPanel() {
        initializeEditCommentFunctionality();
        initializeDeleteCommentFunctionality();
        initializeFilters();
        initializeModals();
        initializeCharacterCounter();
        initializeCountryAutocomplete(); // Necesario para autocompletado de países en edición
        initializeFileUpload(); // Inicializar preview de archivos
    }
    
    /**
     * Inicializar funcionalidad de subida de archivos con preview
     */
    function initializeFileUpload() {
        // Validar archivos seleccionados
        $(document).on('change', '#edit-images', function(e) {
            const files = e.target.files;
            
            if (files.length > 0) {
                Array.from(files).forEach((file, index) => {
                    const sizeKB = (file.size / 1024).toFixed(2);
                    const sizeText = sizeKB > 1024 ? (sizeKB / 1024).toFixed(2) + ' MB' : sizeKB + ' KB';
                });
            }
        });
        
        // Eliminar imagen existente
        $(document).on('click', '.cf-remove-image', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const imageId = $(this).data('image-id');
            const imageElement = $(this).closest('.cf-current-image');
            
            deleteExistingImage(imageId, imageElement);
        });
    }

    /**
     * Inicializar funcionalidad de editar comentarios
     */
    function initializeEditCommentFunctionality() {
        // Botón editar comentario - múltiples selectores para compatibilidad
        $(document).on('click', '.btn-edit-comment, .cf-user-edit-btn, .cf-admin-edit-comment', function(e) {
            e.preventDefault();
            
            const commentId = $(this).data('comment-id');
            loadCommentForEdit(commentId);
        });

        // Guardar cambios de edición - múltiples selectores para compatibilidad
        $(document).on('click', '#save-comment-edit, .cf-btn-save', function(e) {
            e.preventDefault();
            saveCommentEdit();
        });

        // Rating en modal de edición
        $(document).on('click', '#edit-rating-input .star', function() {
            const rating = $(this).data('rating');
            $('#edit-rating').val(rating);
            
            $('#edit-rating-input .star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
        });
        
        // Cerrar modales - múltiples selectores para compatibilidad
        $(document).on('click', '.cf-close-modal, .cf-btn-cancel, .modal-close', function(e) {
            e.preventDefault();
            $('#cf-user-edit-modal, #edit-comment-modal').fadeOut();
        });
        
        // Cerrar modal al hacer clic en overlay
        $(document).on('click', '.cf-modal-overlay', function() {
            $('#cf-user-edit-modal, #edit-comment-modal').fadeOut();
        });
        
        // Botón eliminar comentario
        $(document).on('click', '.cf-delete-comment', function(e) {
            e.preventDefault();
            
            const commentId = $(this).data('comment-id');
            
            // Usar SweetAlert2 para confirmación elegante
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: '¿Eliminar comentario?',
                    text: 'Esta acción no se puede deshacer',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc3545',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        deleteComment(commentId);
                    }
                });
            } else {
                // Fallback a confirm nativo
                if (confirm('¿Estás seguro de que deseas eliminar este comentario permanentemente?')) {
                    deleteComment(commentId);
                }
            }
        });
    }

    /**
     * Cargar comentario para editar
     */
    function loadCommentForEdit(commentId) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            beforeSend: function() {
                // Mostrar loading
                showLoadingSpinner();
            },
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    // Pasar tanto el comentario como las imágenes
                    populateEditForm(response.data.comment, response.data.images || []);
                    
                    // Intentar abrir el modal correcto según el contexto
                    if ($('#cf-user-edit-modal').length) {
                        $('#cf-user-edit-modal').fadeIn();
                    } else if ($('#edit-comment-modal').length) {
                        $('#edit-comment-modal').fadeIn();
                    }
                } else {
                    showAlert('error', 'Error', response.data.message || 'No se pudo cargar el comentario para editar');
                }
            },
            error: function() {
                hideLoadingSpinner();
                showAlert('error', 'Error', 'Error de conexión. Por favor, inténtalo de nuevo.');
            }
        });
    }

    /**
     * Llenar formulario de edición con datos del comentario
     */
    function populateEditForm(comment, images) {
        // Función auxiliar para establecer valor en múltiples selectores
        function setFieldValue(selectors, value) {
            const selectorsArray = Array.isArray(selectors) ? selectors : [selectors];
            selectorsArray.forEach(selector => {
                if ($(selector).length) {
                    $(selector).val(value);
                }
            });
        }
        
        // ID del comentario - múltiples IDs posibles
        setFieldValue(['#edit-comment-id', '#cf-user-comment-id'], comment.id);
        
        // Rating - múltiples IDs posibles
        setFieldValue(['#edit-rating', '#cf-user-rating'], comment.rating);
        
        // Actualizar estrellas visuales si existen
        $('#edit-rating-input .star').each(function(index) {
            if (index < comment.rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
        
        // Título - múltiples IDs posibles
        setFieldValue(['#edit-title', '#cf-user-title'], comment.title || '');
        
        // Contenido - múltiples IDs posibles
        setFieldValue(['#edit-content', '#cf-user-content'], comment.content || '');
        
        // País - convertir código a nombre y establecer
        const countryCode = comment.country || '';
        if (countryCode && countriesData[countryCode]) {
            const countryName = countriesData[countryCode].name;
            const countryInput = $('#edit-country');
            countryInput.val(countryName);
            countryInput.data('country-code', countryCode);
        } else {
            $('#edit-country').val('');
            $('#edit-country').data('country-code', '');
        }
        
        // Idioma - múltiples IDs posibles
        setFieldValue(['#edit-language', '#cf-user-language'], comment.language || 'es');
        
        // Travel Companion - múltiples IDs posibles
        if (comment.travel_companion) {
            // Normalizar valores antiguos a los nuevos
            var travelCompanion = comment.travel_companion;
            var travelCompanionMap = {
                'pareja': 'en_pareja',
                'familia': 'en_familia',
                'amigos': 'con_amigos'
            };
            if (travelCompanionMap[travelCompanion]) {
                travelCompanion = travelCompanionMap[travelCompanion];
            }
            setFieldValue(['#edit-travel-companion', '#cf-user-travel-companion'], travelCompanion);
        } else {
            $('#edit-travel-companion').val('solo'); // Default solo
        }
        
        // Mostrar imágenes existentes directamente (sin AJAX adicional)
        displayUserCurrentImages(images || []);
    }
    
    /**
     * Mostrar imágenes actuales del usuario en el modal de edición
     */
    function displayUserCurrentImages(images) {
        const container = $('#current-images-container');
        
        if (!container.length) {
            return;
        }
        
        container.empty();
        
        if (images && images.length > 0) {
            images.forEach(function(image, index) {
                const imageHtml = `
                    <div class="cf-current-image" data-image-id="${image.id}">
                        <img src="${image.file_url}" alt="${image.original_name}">
                        <button type="button" class="cf-remove-image" data-image-id="${image.id}" title="Eliminar esta imagen">×</button>
                    </div>
                `;
                container.append(imageHtml);
            });
        } else {
            container.html('<p class="cf-no-images">📷 No hay imágenes en este comentario</p>');
        }
    }
    
    /**
     * Eliminar imagen existente
     */
    function deleteExistingImage(imageId, imageElement) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Eliminar imagen?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    performImageDeletion(imageId, imageElement);
                }
            });
        } else {
            if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
                performImageDeletion(imageId, imageElement);
            }
        }
    }
    
    function performImageDeletion(imageId, imageElement) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete_image',
                image_id: imageId,
                nonce: comentarios_ajax.nonce
            },
            beforeSend: function() {
                imageElement.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    imageElement.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si no quedan más imágenes, mostrar mensaje
                        if ($('.cf-current-image').length === 0) {
                            $('#current-images-container').html('<p class="cf-no-images">📷 No hay imágenes en este comentario</p>');
                        }
                    });
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Eliminada!',
                            text: 'La imagen ha sido eliminada',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    imageElement.css('opacity', '1');
                    showAlert('error', 'Error', response.data.message || 'No se pudo eliminar la imagen');
                }
            },
            error: function() {
                imageElement.css('opacity', '1');
                showAlert('error', 'Error', 'Error de conexión al eliminar la imagen');
            }
        });
    }

    /**
     * Guardar edición del comentario
     */
    function saveCommentEdit() {
        // Función auxiliar para obtener valor de múltiples selectores
        function getFieldValue(selectors, defaultValue = '') {
            const selectorsArray = Array.isArray(selectors) ? selectors : [selectors];
            for (let selector of selectorsArray) {
                if ($(selector).length && $(selector).val()) {
                    return $(selector).val();
                }
            }
            return defaultValue;
        }
        
        // Crear FormData para soportar archivos
        const formData = new FormData();
        formData.append('action', 'comentarios_edit');
        formData.append('comment_id', getFieldValue(['#edit-comment-id', '#cf-user-comment-id']));
        formData.append('rating', getFieldValue(['#edit-rating', '#cf-user-rating']));
        formData.append('title', getFieldValue(['#edit-title', '#cf-user-title']).trim());
        formData.append('content', getFieldValue(['#edit-content', '#cf-user-content']).trim());
        
        // Obtener código del país desde el data-country-code del input de autocomplete
        const countryInput = $('#edit-country');
        const countryCode = countryInput.data('country-code') || '';
        formData.append('country', countryCode);
        
        formData.append('language', getFieldValue(['#edit-language', '#cf-user-language'], 'es'));
        formData.append('travel_companion', getFieldValue(['#edit-travel-companion', '#cf-user-travel-companion']));
        formData.append('comentarios_nonce', comentarios_ajax.nonce);
        
        // Agregar nuevas imágenes si existen
        const fileInput = $('#edit-images')[0];
        if (fileInput && fileInput.files.length > 0) {
            for (let i = 0; i < fileInput.files.length; i++) {
                formData.append('images[]', fileInput.files[i]);
            }
        }

        // Validar campos
        const rating = getFieldValue(['#edit-rating', '#cf-user-rating']);
        if (!rating || rating < 1 || rating > 5) {
            showAlert('warning', 'Campo requerido', 'Por favor selecciona una calificación');
            return;
        }

        const title = getFieldValue(['#edit-title', '#cf-user-title']).trim();
        if (!title) {
            showAlert('warning', 'Campo requerido', 'Por favor ingresa un título');
            $('#edit-title, #cf-user-title').focus();
            return;
        }

        const content = getFieldValue(['#edit-content', '#cf-user-content']).trim();
        if (!content) {
            showAlert('warning', 'Campo requerido', 'Por favor ingresa tu comentario');
            $('#edit-content, #cf-user-content').focus();
            return;
        }

        const language = getFieldValue(['#edit-language', '#cf-user-language']);
        if (!language) {
            showAlert('warning', 'Campo requerido', 'Por favor selecciona un idioma');
            $('#edit-language, #cf-user-language').focus();
            return;
        }

        const travelCompanion = getFieldValue(['#edit-travel-companion', '#cf-user-travel-companion']);
        if (!travelCompanion) {
            showAlert('warning', 'Campo requerido', 'Por favor selecciona con quién viajaste');
            $('#edit-travel-companion, #cf-user-travel-companion').focus();
            return;
        }

        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                // Deshabilitar todos los posibles botones de guardar
                $('#save-comment-edit, .cf-btn-save').prop('disabled', true).text('Guardando...');
            },
            success: function(response) {
                // Rehabilitar todos los posibles botones de guardar
                $('#save-comment-edit, .cf-btn-save').prop('disabled', false).text('Guardar Cambios');
                
                if (response.success) {
                    // Cerrar el modal primero
                    if ($('#cf-user-edit-modal').is(':visible')) {
                        $('#cf-user-edit-modal').fadeOut();
                    } else if ($('#edit-comment-modal').is(':visible')) {
                        $('#edit-comment-modal').fadeOut();
                    }
                    
                    // Mostrar confirmación elegante
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Actualizado!',
                            text: 'Tu comentario ha sido guardado correctamente',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Ver cambios',
                            timer: 2500,
                            timerProgressBar: true,
                            showClass: {
                                popup: 'animate__animated animate__fadeInDown'
                            },
                            hideClass: {
                                popup: 'animate__animated animate__fadeOutUp'
                            }
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        showAlert('success', '¡Éxito!', 'Tu comentario ha sido actualizado correctamente');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showAlert('error', 'Error', response.data.message || 'No se pudo guardar el comentario');
                }
            },
            error: function() {
                $('#save-comment-edit').prop('disabled', false).text('Guardar Cambios');
                showAlert('error', 'Error', 'Error de conexión. Por favor, inténtalo de nuevo.');
            }
        });
    }

    /**
     * Inicializar funcionalidad de eliminar comentarios
     */
    function initializeDeleteCommentFunctionality() {
        $(document).on('click', '.btn-delete-comment', function(e) {
            e.preventDefault();
            
            const commentId = $(this).data('comment-id');
            
            if (confirm('¿Estás seguro de que deseas eliminar este comentario? Esta acción no se puede deshacer.')) {
                deleteComment(commentId);
            }
        });
    }

    /**
     * Eliminar comentario
     */
    function deleteComment(commentId) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            beforeSend: function() {
                showLoadingSpinner();
            },
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    showAlert('success', 'Eliminado', 'Tu comentario ha sido eliminado');
                    
                    // Remover el elemento de la página
                    $('[data-comment-id="' + commentId + '"]').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showAlert('error', 'Error', response.data.message || 'No se pudo eliminar el comentario');
                }
            },
            error: function() {
                hideLoadingSpinner();
                showAlert('error', 'Error', 'Error de conexión. Por favor, inténtalo de nuevo.');
            }
        });
    }

    /**
     * Inicializar filtros del panel de usuario
     */
    function initializeFilters() {
        $('#user-status-filter').on('change', function() {
            const selectedStatus = $(this).val();
            
            $('.user-comment-item').each(function() {
                const commentStatus = $(this).data('status');
                
                if (selectedStatus === '' || commentStatus === selectedStatus) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });
    }

    /**
     * Inicializar modales
     */
    function initializeModals() {
        // Cerrar modal
        $(document).on('click', '.modal-close, .comentarios-modal', function(e) {
            if (e.target === this) {
                $(this).closest('.comentarios-modal').fadeOut();
            }
        });

        // Prevenir cierre al hacer clic dentro del modal
        $(document).on('click', '.modal-content', function(e) {
            e.stopPropagation();
        });
    }

    /**
     * Mostrar spinner de carga
     */
    function showLoadingSpinner() {
        if ($('#loading-spinner').length === 0) {
            $('body').append('<div id="loading-spinner" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 99999; display: flex; align-items: center; justify-content: center;"><div style="background: white; padding: 20px; border-radius: 8px; text-align: center;"><div style="border: 3px solid #f3f3f3; border-top: 3px solid #007cba; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 10px;"></div><p>Cargando...</p></div></div>');
        }
        
        // Agregar CSS de animación si no existe
        if ($('#spinner-css').length === 0) {
            $('head').append('<style id="spinner-css">@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }</style>');
        }
    }

    /**
     * Ocultar spinner de carga
     */
    function hideLoadingSpinner() {
        $('#loading-spinner').remove();
    }

    /**
     * Eliminar comentario
     */
    function deleteComment(commentId) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            beforeSend: function() {
                showLoadingSpinner();
            },
            success: function(response) {
                hideLoadingSpinner();
                
                if (response.success) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: '¡Eliminado!',
                            text: 'El comentario ha sido eliminado correctamente',
                            icon: 'success',
                            confirmButtonColor: '#28a745',
                            confirmButtonText: 'Continuar',
                            timer: 2000,
                            timerProgressBar: true
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        showAlert('success', '¡Éxito!', 'Comentario eliminado correctamente');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showAlert('error', 'Error', response.data.message || 'No se pudo eliminar el comentario');
                }
            },
            error: function() {
                hideLoadingSpinner();
                showAlert('error', 'Error', 'Error de conexión. Por favor, inténtalo de nuevo.');
            }
        });
    }

    /**
     * Mostrar alerta con SweetAlert2
     */
    function showAlert(type, title, message) {
        // Usar SweetAlert2 si está disponible
        if (typeof Swal !== 'undefined') {
            const config = {
                title: title,
                text: message,
                confirmButtonColor: '#007cba',
                confirmButtonText: 'Entendido',
                allowOutsideClick: false,
                allowEscapeKey: true
            };

            switch(type) {
                case 'success':
                    config.icon = 'success';
                    config.confirmButtonColor = '#28a745';
                    config.timer = 3000;
                    config.timerProgressBar = true;
                    break;
                case 'error':
                    config.icon = 'error';
                    config.confirmButtonColor = '#dc3545';
                    break;
                case 'warning':
                    config.icon = 'warning';
                    config.confirmButtonColor = '#ffc107';
                    break;
                default:
                    config.icon = 'info';
            }

            Swal.fire(config);
        } else {
            // Fallback a alert nativo si SweetAlert2 no está disponible
            alert(title + '\n\n' + message);
        }
    }

    /**
     * Inicializar contador de caracteres
     */
    function initializeCharacterCounter() {
        // Contador para el textarea de contenido del usuario
        $(document).on('input keyup', '#cf-user-content', function() {
            const content = $(this).val();
            const count = content.length;
            const maxLength = $(this).attr('maxlength') || 2000;
            
            $('#cf-user-content-count').text(count);
            
            // Cambiar color según proximidad al límite
            if (count > maxLength * 0.9) {
                $('#cf-user-content-count').css('color', '#dc3545'); // Rojo
            } else if (count > maxLength * 0.7) {
                $('#cf-user-content-count').css('color', '#ffc107'); // Amarillo
            } else {
                $('#cf-user-content-count').css('color', '#6c757d'); // Gris
            }
        });
        
        // También para otros campos con límite de caracteres
        $(document).on('input keyup', '#cf-user-title', function() {
            const content = $(this).val();
            const count = content.length;
            const maxLength = $(this).attr('maxlength') || 200;
            
            // Si existe un contador para el título, actualizarlo
            if ($('#cf-user-title-count').length) {
                $('#cf-user-title-count').text(count);
                
                if (count > maxLength * 0.9) {
                    $('#cf-user-title-count').css('color', '#dc3545');
                } else if (count > maxLength * 0.7) {
                    $('#cf-user-title-count').css('color', '#ffc107');
                } else {
                    $('#cf-user-title-count').css('color', '#6c757d');
                }
            }
        });
    }

    /**
     * Inicializar autocompletado de países
     */
    function initializeCountryAutocomplete() {
        // La lista countriesData ya está definida globalmente en el scope del plugin
        let selectedCountryCode = "";
        
        // Buscar el input y dropdown del país - soportar múltiples IDs
        let input = $('#edit-country');
        let dropdown = $('#edit-country-dropdown');
        
        // Si no existe, buscar el del panel de usuario
        if (input.length === 0) {
            input = $('#cf-user-country');
            dropdown = $('#cf-user-country-dropdown');
        }
        
        if (input.length === 0 || dropdown.length === 0) {
            return;
        }
        
        // Eliminar eventos previos para evitar duplicados
        input.off('input focus keydown');
        $(document).off('click.countryAutocomplete');
        
        // Manejar input en el campo
        input.on('input focus', function() {
            const query = $(this).val().toLowerCase().trim();
            showCountryDropdown(input, dropdown, query);
        });
        
        // Ocultar dropdown al hacer clic fuera (con namespace para poder eliminarlo)
        $(document).on('click.countryAutocomplete', function(e) {
            if (!input.is(e.target) && !dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
                dropdown.hide();
            }
        });
        
        // Manejar teclas
        input.on('keydown', function(e) {
            const highlighted = dropdown.find('.cf-country-option.cf-highlighted');
            const options = dropdown.find('.cf-country-option');
            
            if (e.keyCode === 40) { // Flecha abajo
                e.preventDefault();
                if (highlighted.length === 0) {
                    options.first().addClass('cf-highlighted');
                } else {
                    highlighted.removeClass('cf-highlighted');
                    const next = highlighted.next('.cf-country-option');
                    if (next.length > 0) {
                        next.addClass('cf-highlighted');
                    } else {
                        options.first().addClass('cf-highlighted');
                    }
                }
            } else if (e.keyCode === 38) { // Flecha arriba
                e.preventDefault();
                if (highlighted.length === 0) {
                    options.last().addClass('cf-highlighted');
                } else {
                    highlighted.removeClass('cf-highlighted');
                    const prev = highlighted.prev('.cf-country-option');
                    if (prev.length > 0) {
                        prev.addClass('cf-highlighted');
                    } else {
                        options.last().addClass('cf-highlighted');
                    }
                }
            } else if (e.keyCode === 13) { // Enter
                e.preventDefault();
                if (highlighted.length > 0) {
                    selectCountry(input, highlighted.data('code'), highlighted.find('.cf-country-name-option').text());
                    dropdown.hide();
                }
            } else if (e.keyCode === 27) { // Escape
                dropdown.hide();
            }
        });
        
        function showCountryDropdown(input, dropdown, query) {
            let html = "";
            let matchCount = 0;
            
            Object.keys(countriesData).forEach(function(code) {
                if (code === "") return; // Saltar la opción vacía
                
                const country = countriesData[code];
                const name = country.name.toLowerCase();
                
                if (query === "" || name.includes(query)) {
                    html += '<div class="cf-country-option" data-code="' + code + '">' +
                            '<span class="cf-country-flag-option">' + country.flag + '</span>' +
                            '<span class="cf-country-name-option">' + country.name + '</span>' +
                            '</div>';
                    matchCount++;
                }
            });
            
            if (matchCount === 0) {
                html = '<div class="cf-country-option" style="color: #999; cursor: default;">' +
                       '<span class="cf-country-name-option">No se encontraron países</span>' +
                       '</div>';
            }
            
            dropdown.html(html).show();
            
            // Agregar eventos de click
            dropdown.find('.cf-country-option[data-code]').on('click', function() {
                const code = $(this).data('code');
                const name = $(this).find('.cf-country-name-option').text();
                selectCountry(input, code, name);
                dropdown.hide();
            });
            
            // Agregar eventos de hover
            dropdown.find('.cf-country-option').on('mouseenter', function() {
                dropdown.find('.cf-country-option').removeClass('cf-highlighted');
                $(this).addClass('cf-highlighted');
            });
        }
        
        function selectCountry(inputElement, code, name) {
            inputElement.val(name);
            inputElement.data('country-code', code);
            selectedCountryCode = code;
        }
        
        // Limpiar al cerrar modal
        $(document).on('click', '.modal-close', function() {
            dropdown.hide();
            selectedCountryCode = "";
        });
    }

})(jQuery);