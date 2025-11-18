/**
 * JavaScript para el Dashboard de Administración - Panel de Usuarios Suscriptores
 * Plugin: Comentarios Free
 */

jQuery(document).ready(function($) {
    // Datos de países desde PHP
    var countriesData = comentarios_ajax.countries || {};
    
    // Desactivar handlers de frontend.js si existen
    $(document).off('click', '.btn-edit-comment');
    $(document).off('click', '.modal-close');
    
    // Cerrar modales antiguos si están presentes
    $('#edit-comment-modal').hide();
    $('.comentarios-modal').hide();
    
    // Función para cerrar modales antiguos periódicamente
    function closeOldModals() {
        if ($('#edit-comment-modal:visible').length > 0) {
            $('#edit-comment-modal').hide();
        }
        
        if ($('.comentarios-modal:visible').length > 0) {
            $('.comentarios-modal').hide();
        }
    }
    
    // Ejecutar verificación cada 500ms durante los primeros 5 segundos
    var checkCount = 0;
    var checkInterval = setInterval(function() {
        closeOldModals();
        checkCount++;
        if (checkCount >= 10) { // 10 * 500ms = 5 segundos
            clearInterval(checkInterval);
        }
    }, 500);
    
    // Variables globales
    var currentFilters = {
        status: '',
        rating: ''
    };
    
    // =================================================================
    // FILTROS DE COMENTARIOS
    // =================================================================
    
    // Filtro por estado
    $('#admin-status-filter').on('change', function() {
        currentFilters.status = $(this).val();
        applyFilters();
    });
    
    // Filtro por calificación
    $('#admin-rating-filter').on('change', function() {
        currentFilters.rating = $(this).val();
        applyFilters();
    });
    
    function applyFilters() {
        var $rows = $('.cf-comment-row');
        var visibleCount = 0;
        
        $rows.each(function() {
            var $row = $(this);
            var rowStatus = $row.data('status');
            var rowRating = $row.find('.cf-rating-display').data('rating');
            
            var showRow = true;
            
            // Filtrar por estado
            if (currentFilters.status && rowStatus !== currentFilters.status) {
                showRow = false;
            }
            
            // Filtrar por calificación
            if (currentFilters.rating && rowRating != currentFilters.rating) {
                showRow = false;
            }
            
            if (showRow) {
                $row.show();
                visibleCount++;
            } else {
                $row.hide();
            }
        });
        
        // Mostrar mensaje si no hay resultados
        updateNoResultsMessage(visibleCount);
    }
    
    function updateNoResultsMessage(count) {
        var $container = $('#user-comments-container');
        var $noResults = $container.find('.cf-no-results');
        
        if (count === 0) {
            if ($noResults.length === 0) {
                $container.append('<div class="cf-no-results cf-text-center" style="padding: 40px; color: #7f8c8d;"><h3>🔍 No se encontraron comentarios</h3><p>Prueba cambiar los filtros para ver más resultados.</p></div>');
            }
        } else {
            $noResults.remove();
        }
    }
    
    // =================================================================
    // ACCIONES DE COMENTARIOS
    // =================================================================
    
    // Editar comentario - FUNCIÓN COMPLETA IMPLEMENTADA
    $(document).on('click', '.cf-edit-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        
        // Obtener datos del comentario para editar
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.comment) {
                    openEditModal(response.data.comment);
                } else {
                    // Manejar diferentes tipos de respuesta de error
                    var errorMsg = 'No se pudo obtener el comentario';
                    
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMsg = response.data;
                        } else if (response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } else if (response.message) {
                        errorMsg = response.message;
                    }
                    
                    showNotification('❌ Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión. Intenta nuevamente.', 'error');
            }
        });
    });
    
    // Función para abrir modal de edición
    function openEditModal(comment) {
        // Crear modal si no existe
        if (!$('#cf-edit-modal').length) {
            createEditModal();
        }
        
        // Llenar formulario con datos actuales
        $('#edit-comment-id').val(comment.id);
        $('#edit-comment-title').val(comment.title);
        $('#edit-comment-content').val(comment.content);
        $('#edit-comment-rating').val(comment.rating);
        
        // Actualizar estrellas visuales
        updateEditStars(comment.rating);
        
        // Mostrar modal
        $('#cf-edit-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
    }
    
    // Crear modal de edición
    function createEditModal() {
        var modalHTML = `
        <div id="cf-edit-modal" class="cf-modal" style="display: none;">
            <div class="cf-modal-content">
                <div class="cf-modal-header">
                    <h3>✏️ Editar Comentario</h3>
                    <button type="button" class="cf-modal-close">&times;</button>
                </div>
                <div class="cf-edit-warning">
                    <p><strong>⚠️ Importante:</strong> Solo puedes editar este comentario <strong>una vez</strong>. Después de guardar los cambios, no podrás modificarlo nuevamente.</p>
                </div>
                <div class="cf-modal-body">
                    <form id="cf-edit-form">
                        <input type="hidden" id="edit-comment-id" name="comment_id">
                        
                        <div class="cf-form-group">
                            <label>Calificación *</label>
                            <div class="cf-rating-input" id="edit-rating-stars">
                                <span class="cf-rating-star" data-rating="1">⭐</span>
                                <span class="cf-rating-star" data-rating="2">⭐</span>
                                <span class="cf-rating-star" data-rating="3">⭐</span>
                                <span class="cf-rating-star" data-rating="4">⭐</span>
                                <span class="cf-rating-star" data-rating="5">⭐</span>
                            </div>
                            <input type="hidden" id="edit-comment-rating" name="rating" required>
                        </div>
                        
                        <div class="cf-form-group">
                            <label for="edit-comment-title">Título *</label>
                            <input type="text" id="edit-comment-title" name="title" required maxlength="255">
                        </div>
                        
                        <div class="cf-form-group">
                            <label for="edit-comment-content">Comentario *</label>
                            <textarea id="edit-comment-content" name="content" required rows="5"></textarea>
                        </div>
                        
                        <div class="cf-form-actions">
                            <button type="button" class="button cf-modal-close">Cancelar</button>
                            <button type="submit" class="button button-primary">💾 Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>`;
        
        $('body').append(modalHTML);
    }
    
    // Manejar sistema de estrellas en edición
    var editCurrentRating = 0;
    
    $(document).on('click', '#edit-rating-stars .cf-rating-star', function() {
        editCurrentRating = parseInt($(this).data('rating'));
        $('#edit-comment-rating').val(editCurrentRating);
        updateEditStars(editCurrentRating);
    });
    
    $(document).on('mouseenter', '#edit-rating-stars .cf-rating-star', function() {
        var hoverRating = parseInt($(this).data('rating'));
        updateEditStars(hoverRating, true);
    });
    
    $(document).on('mouseleave', '#edit-rating-stars', function() {
        updateEditStars(editCurrentRating);
    });
    
    function updateEditStars(rating, hover = false) {
        $('#edit-rating-stars .cf-rating-star').each(function(index) {
            var starRating = index + 1;
            if (starRating <= rating) {
                $(this).css('opacity', '1');
            } else {
                $(this).css('opacity', hover ? '0.5' : '0.3');
            }
        });
    }
    
    // Cerrar modal de edición
    $(document).on('click', '.cf-modal-close, .cf-modal', function(e) {
        if (e.target === this) {
            $('#cf-edit-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
        }
    });
    
    // Enviar formulario de edición
    $(document).on('submit', '#cf-edit-form', function(e) {
        e.preventDefault();
        
        if (editCurrentRating === 0) {
            showNotification('❌ Por favor selecciona una calificación', 'error');
            return;
        }
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.html('🔄 Guardando...').prop('disabled', true);
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=comentarios_edit&comentarios_nonce=' + comentarios_ajax.nonce,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('✅ Comentario actualizado exitosamente. No podrás editarlo nuevamente.', 'success');
                    $('#cf-edit-modal').fadeOut(300);
                    $('body').css('overflow', 'auto');
                    
                    // Recargar página para mostrar cambios
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    // Manejar diferentes tipos de respuesta de error
                    var errorMsg = 'No se pudo actualizar';
                    
                    if (response.data) {
                        if (typeof response.data === 'string') {
                            errorMsg = response.data;
                        } else if (response.data.message) {
                            errorMsg = response.data.message;
                        }
                    } else if (response.message) {
                        errorMsg = response.message;
                    }
                    
                    showNotification('❌ Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión: ' + error + ' (Status: ' + status + ')', 'error');
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // =================================================================
    // ELIMINAR COMENTARIO
    // =================================================================
    
    // Eliminar comentario (usuarios y admin)
    $(document).on('click', '.cf-delete-comment, .cf-admin-delete-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        var $deleteBtn = $(this);
        var originalText = $deleteBtn.html();
        
        // Confirmación de eliminación con SweetAlert2
        Swal.fire({
            title: '¿Eliminar comentario?',
            text: 'Esta acción no se puede deshacer',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }
            
            $deleteBtn.html('🔄 Eliminando...').prop('disabled', true);
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Eliminado!',
                        text: 'El comentario ha sido eliminado',
                        timer: 2000,
                        showConfirmButton: false
                    });
                    
                    // Eliminar fila de la tabla con animación
                    $deleteBtn.closest('tr').fadeOut(500, function() {
                        $(this).remove();
                        
                        // Verificar si ya no hay comentarios en esta página
                        var remainingComments = $('.cf-comment-row').length;
                        
                        if (remainingComments === 0) {
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        }
                    });
                } else {
                    var errorMsg = response.data ? response.data.message : 'No se pudo eliminar el comentario';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                    $deleteBtn.html(originalText).prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se pudo eliminar el comentario'
                });
                $deleteBtn.html(originalText).prop('disabled', false);
            }
        });
        });
    });
    
    // Manejar clics en botones deshabilitados
    $(document).on('click', '.button-disabled', function(e) {
        e.preventDefault();
        showNotification('ℹ️ Este comentario ya fue editado. No se permiten más cambios.', 'info');
        return false;
    });
    
    // Ver comentario (abrir en nueva pestaña)
    $(document).on('click', '.cf-view-comment', function(e) {
        e.preventDefault();
        var url = $(this).attr('href');
        window.open(url, '_blank');
    });
    
    // =================================================================
    // FUNCIONES DE ADMINISTRADOR
    // =================================================================
    
    // Aprobar comentario (solo para administradores)
    $(document).on('click', '.cf-approve-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        updateCommentStatus(commentId, 'approved', $(this));
    });
    
    // Rechazar comentario (solo para administradores)
    $(document).on('click', '.cf-reject-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        updateCommentStatus(commentId, 'pending', $(this));
    });
    
    function updateCommentStatus(commentId, status, $button) {
        var originalText = $button.html();
        $button.html('🔄').prop('disabled', true);
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_update_status',
                comment_id: commentId,
                status: status,
                nonce: comentarios_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var $row = $button.closest('.cf-admin-comment-row');
                    var statusText = status === 'approved' ? '✅ Aprobado' : '⏳ Pendiente';
                    var statusClass = 'cf-status-' + status;
                    
                    $row.find('.cf-status-badge')
                        .removeClass('cf-status-approved cf-status-pending')
                        .addClass(statusClass)
                        .text(statusText);
                    
                    showNotification('✅ Estado actualizado correctamente', 'success');
                } else {
                    showNotification('❌ Error: ' + (response.data || 'No se pudo actualizar'), 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión', 'error');
            },
            complete: function() {
                $button.html(originalText).prop('disabled', false);
            }
        });
    }
    
    // =================================================================
    // FUNCIONES AUXILIARES
    // =================================================================
    
    function updateStats() {
        // Recalcular estadísticas después de eliminar comentarios
        var totalVisible = $('.cf-comment-row:visible').length;
        var approvedVisible = $('.cf-comment-row:visible[data-status="approved"]').length;
        var pendingVisible = $('.cf-comment-row:visible[data-status="pending"]').length;
        
        // Actualizar contadores si existen
        $('.cf-stat-total .cf-stat-number').text(totalVisible);
        $('.cf-stat-approved .cf-stat-number').text(approvedVisible);
        $('.cf-stat-pending .cf-stat-number').text(pendingVisible);
    }
    
    function showNotification(message, type) {
        var bgColor, icon;
        
        switch(type) {
            case 'success':
                bgColor = '#27ae60';
                icon = '✅';
                break;
            case 'error':
                bgColor = '#e74c3c';
                icon = '❌';
                break;
            case 'info':
                bgColor = '#3498db';
                icon = 'ℹ️';
                break;
            default:
                bgColor = '#95a5a6';
                icon = '📝';
        }
        
        var $notification = $('<div class="cf-notification">')
            .css({
                'position': 'fixed',
                'top': '20px',
                'right': '20px',
                'background': bgColor,
                'color': 'white',
                'padding': '15px 20px',
                'border-radius': '8px',
                'box-shadow': '0 4px 20px rgba(0,0,0,0.3)',
                'z-index': '9999',
                'max-width': '400px',
                'font-weight': '600'
            })
            .html(icon + ' ' + message);
        
        $('body').append($notification);
        
        // Auto-eliminar después de 3 segundos (4 para info)
        var delay = type === 'info' ? 4000 : 3000;
        setTimeout(function() {
            $notification.fadeOut(300, function() {
                $(this).remove();
            });
        }, delay);
    }
    
    // =================================================================
    // MEJORAS DE UX
    // =================================================================
    
    // Animación al hacer hover en las filas
    $('.cf-comment-row, .cf-admin-comment-row').hover(
        function() {
            $(this).css('transform', 'translateY(-2px)');
        },
        function() {
            $(this).css('transform', 'translateY(0)');
        }
    );
    
    // Resaltar filtros activos
    function highlightActiveFilters() {
        $('#admin-status-filter, #admin-rating-filter').each(function() {
            if ($(this).val()) {
                $(this).css('border-color', '#3498db').css('background-color', '#e3f2fd');
            } else {
                $(this).css('border-color', '#ced4da').css('background-color', 'white');
            }
        });
    }
    
    $('#admin-status-filter, #admin-rating-filter').on('change', highlightActiveFilters);
    
    // =================================================================
    // ESTADÍSTICAS DINÁMICAS
    // =================================================================
    
    // Actualizar estadísticas en tiempo real
    function updateRealTimeStats() {
        var $visibleRows = $('.cf-comment-row:visible');
        var totalVisible = $visibleRows.length;
        var approvedVisible = $visibleRows.filter('[data-status="approved"]').length;
        var pendingVisible = $visibleRows.filter('[data-status="pending"]').length;
        
        // Calcular rating promedio
        var totalRating = 0;
        var ratingCount = 0;
        $visibleRows.each(function() {
            var rating = $(this).find('.cf-rating-display').data('rating');
            if (rating) {
                totalRating += parseFloat(rating);
                ratingCount++;
            }
        });
        
        var avgRating = ratingCount > 0 ? (totalRating / ratingCount).toFixed(1) : 0;
        
        // Actualizar en la interfaz
        $('.cf-stat-total .cf-stat-number').text(totalVisible);
        $('.cf-stat-approved .cf-stat-number').text(approvedVisible);
        $('.cf-stat-pending .cf-stat-number').text(pendingVisible);
        $('.cf-stat-rating .cf-stat-number').text(avgRating);
    }
    
    // Actualizar estadísticas cada vez que cambian los filtros
    $('#admin-status-filter, #admin-rating-filter').on('change', function() {
        setTimeout(updateRealTimeStats, 100);
    });
    
    // =================================================================
    // FUNCIONES DE ADMINISTRADOR
    // =================================================================
    
    // Editar comentario como administrador (sin límites)
    $(document).on('click', '.cf-admin-edit-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        
        // Obtener datos del comentario para editar
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_admin_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.comment) {
                    openAdminEditModal(response.data.comment, response.data.images || []);
                } else {
                    var errorMsg = response.data ? response.data.message : 'No se pudo obtener el comentario';
                    showNotification('❌ Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión. Intenta nuevamente.', 'error');
            }
        });
    });
    
    // Función para abrir modal de edición del administrador
    function openAdminEditModal(comment, images) {
        
        // Mostrar modal
        $('#cf-admin-edit-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Llenar información de usuario
        $('#edit_comment_id').val(comment.id || '');
        $('#edit_user_name').text(comment.author_name || 'Sin nombre');
        $('#edit_user_email').text(comment.author_email || 'Sin email');
        $('#edit_user_country').text(comment.country || 'Sin país');
        
        // Llenar campos del formulario con valores seguros
        // Usar setTimeout para asegurar que el DOM está listo
        setTimeout(function() {
            // Normalizar valores - manejar null, undefined, "null", ""
            var rating = (comment.rating && comment.rating !== 'null' && comment.rating !== '' && comment.rating > 0) ? parseInt(comment.rating) : 5;
            var travelCompanion = (comment.travel_companion && comment.travel_companion !== 'null' && comment.travel_companion !== '') ? comment.travel_companion : 'solo';
            
            // Normalizar valores antiguos a los nuevos
            var travelCompanionMap = {
                'pareja': 'en_pareja',
                'familia': 'en_familia',
                'amigos': 'con_amigos'
            };
            if (travelCompanionMap[travelCompanion]) {
                travelCompanion = travelCompanionMap[travelCompanion];
            }
            
            // País - convertir código a nombre y guardar en data-country-code
            var countryCode = (comment.country && comment.country !== 'null' && comment.country !== '') ? comment.country : '';
            if (countryCode && countriesData[countryCode]) {
                var countryName = countriesData[countryCode].name;
                var countryInput = $('#edit_country');
                countryInput.val(countryName);
                countryInput.data('country-code', countryCode);
            } else {
                $('#edit_country').val('');
                $('#edit_country').data('country-code', '');
            }
            
            var language = (comment.language && comment.language !== 'null' && comment.language !== '') ? comment.language : 'es';
            var title = (comment.title && comment.title !== 'null') ? comment.title : '';
            var content = (comment.content && comment.content !== 'null') ? comment.content : '';
            
            // Asignar valores con fuerza
            $('#edit_rating').val(rating).prop('selectedIndex', rating - 1);
            $('#edit_travel_companion').val(travelCompanion);
            $('#edit_language').val(language);
            $('#edit_title').val(title);
            $('#edit_content').val(content);
            
            // Trigger change events para asegurar que se actualicen visualmente
            $('#edit_rating, #edit_travel_companion, #edit_language').trigger('change');
            
            // Segundo intento después de más tiempo si los campos siguen vacíos
            setTimeout(function() {
                // Verificación y corrección más agresiva
                var $languageSelect = $('#edit_language');
                var $travelSelect = $('#edit_travel_companion');
                
                if (!$languageSelect.val() || $languageSelect.val() === '' || $languageSelect.val() === null) {
                    $languageSelect.val(language);
                    if (!$languageSelect.val()) {
                        $languageSelect.find('option').each(function() {
                            if ($(this).val() === language) {
                                $(this).prop('selected', true);
                                return false;
                            }
                        });
                    }
                }
                
                if (!$travelSelect.val() || $travelSelect.val() === '' || $travelSelect.val() === null) {
                    $travelSelect.val(travelCompanion);
                    if (!$travelSelect.val()) {
                        $travelSelect.find('option').each(function() {
                            if ($(this).val() === travelCompanion) {
                                $(this).prop('selected', true);
                                return false;
                            }
                        });
                    }
                }
                
                // Trigger change events para actualizar visualmente
                $languageSelect.trigger('change');
                $travelSelect.trigger('change');
            }, 500); // Aumentado a 500ms para más tiempo
            
        }, 100); // Pequeño delay para asegurar que el DOM está listo
        
        // Mostrar imágenes actuales
        displayCurrentImages(images);
        
        // Inicializar autocompletado de países
        setTimeout(function() {
            initializeCountryAutocomplete();
        }, 300);
    }
    
    // Mostrar imágenes actuales en el modal
    function displayCurrentImages(images) {
        var $container = $('#edit_current_images');
        $container.empty();
        
        if (images && images.length > 0) {
            images.forEach(function(image, index) {
                var imageHtml = `
                    <div class="cf-current-image" data-image-id="${image.id}">
                        <img src="${image.file_url}" alt="${image.original_name}">
                        <button type="button" class="cf-remove-image" data-image-id="${image.id}" title="Eliminar esta imagen">×</button>
                    </div>
                `;
                $container.append(imageHtml);
            });
        } else {
            $container.html('<p class="cf-no-images">📷 No hay imágenes en este comentario</p>');
        }
    }
    
    // Array para almacenar IDs de imágenes a eliminar (modal admin)
    var adminImagesToDelete = [];
    
    // Eliminar imagen individual en modal de administrador
    $(document).on('click', '#cf-admin-edit-modal .cf-remove-image', function(e) {
        e.preventDefault();
        var imageId = $(this).data('image-id');
        var $imageContainer = $(this).closest('.cf-current-image');
        
        // Marcar visualmente como eliminada
        $imageContainer.css('opacity', '0.3');
        $imageContainer.addClass('marked-for-deletion');
        
        // Agregar a array de eliminación
        if (adminImagesToDelete.indexOf(imageId) === -1) {
            adminImagesToDelete.push(imageId);
        }
        
        // Ocultar el botón de eliminar
        $(this).hide();
        
        // Mostrar mensaje temporal
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Marcada para eliminar',
                text: 'La imagen se eliminará al guardar los cambios',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    // Cerrar modal de administrador
    $(document).on('click', '#cf-admin-edit-modal .cf-modal-close', function() {
        closeAdminEditModal();
    });
    
    $(document).on('click', '#cf-admin-edit-modal', function(e) {
        if (e.target === this) {
            closeAdminEditModal();
        }
    });
    
    // Función para cerrar modal con confirmación si hay cambios
    function closeAdminEditModal() {
        var hasChanges = false;
        
        // Verificar si hay cambios en los campos
        $('#cf-admin-edit-form input, #cf-admin-edit-form select, #cf-admin-edit-form textarea').each(function() {
            if ($(this).val() !== $(this).prop('defaultValue') && $(this).attr('type') !== 'hidden') {
                hasChanges = true;
                return false; // break
            }
        });
        
        if (hasChanges) {
            if (confirm('⚠️ Tienes cambios sin guardar. ¿Estás seguro de cerrar el modal?')) {
                doCloseModal();
            }
        } else {
            doCloseModal();
        }
    }
    
    function doCloseModal() {
        $('#cf-admin-edit-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
        
        // Limpiar array de imágenes a eliminar
        adminImagesToDelete = [];
        
        // Limpiar estilos de validación
        $('#cf-admin-edit-form input, #cf-admin-edit-form select, #cf-admin-edit-form textarea').css('border-color', '#e1e5e9');
    }
    
    // Validación en tiempo real de campos requeridos
    $(document).on('blur', '#cf-admin-edit-form input[required], #cf-admin-edit-form select[required], #cf-admin-edit-form textarea[required]', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (!value || value.trim() === '') {
            $field.css('border-color', '#dc3545');
        } else {
            $field.css('border-color', '#28a745');
        }
    });
    
    // Remover estilos de error al empezar a escribir
    $(document).on('input', '#cf-admin-edit-form input, #cf-admin-edit-form select, #cf-admin-edit-form textarea', function() {
        var $field = $(this);
        if ($field.css('border-color') === 'rgb(220, 53, 69)') { // color de error
            $field.css('border-color', '#e1e5e9');
        }
    });
    
    // Enviar formulario de edición del administrador
    $(document).on('submit', '#cf-admin-edit-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $cancelBtn = $form.find('.cf-btn-cancel');
        var originalSubmitHtml = $submitBtn.html();
        
        // Validar campos requeridos antes de enviar
        var isValid = true;
        var requiredFields = ['rating', 'travel_companion', 'language', 'title', 'content'];
        // admin_response es opcional, no se incluye en validación
        // country también es opcional, no requiere validación
        
        requiredFields.forEach(function(fieldName) {
            var $field = $form.find('[name="' + fieldName + '"]');
            if (!$field.val() || $field.val().trim() === '') {
                $field.css('border-color', '#dc3545');
                isValid = false;
            } else {
                $field.css('border-color', '#e1e5e9');
            }
        });
        
        // Validar país (campo especial con autocomplete) - es opcional
        var countryInput = $('#edit_country');
        if (countryInput.length) {
            countryInput.css('border-color', '#e1e5e9'); // Siempre verde porque es opcional
        }
        
        if (!isValid) {
            showNotification('❌ Por favor completa todos los campos obligatorios', 'error');
            return;
        }
        
        // Estados de loading
        $submitBtn.html('<span class="cf-btn-icon">⏳</span><span class="cf-btn-text">Guardando...</span>').prop('disabled', true);
        $cancelBtn.prop('disabled', true);
        
        // Crear FormData para manejar archivos
        var formData = new FormData();
        formData.append('action', 'comentarios_admin_edit');
        formData.append('nonce', comentarios_ajax.nonce);
        
        // Agregar IDs de imágenes a eliminar (admin)
        if (adminImagesToDelete.length > 0) {
            formData.append('delete_images', JSON.stringify(adminImagesToDelete));
        }
        
        // Obtener código del país desde data-country-code del input de autocomplete
        var countryInput = $('#edit_country');
        var countryCode = countryInput.data('country-code') || '';
        
        // Agregar todos los campos del formulario
        $form.find('input, select, textarea').each(function() {
            var $field = $(this);
            var name = $field.attr('name');
            var type = $field.attr('type');
            var fieldId = $field.attr('id');
            
            if (name) {
                // Caso especial: si es el campo de país, usar el código en lugar del nombre
                if (name === 'country') {
                    formData.append('country', countryCode);
                } else if (type === 'checkbox') {
                    formData.append(name, $field.is(':checked') ? $field.val() : '0');
                } else if (type === 'file') {
                    var files = $field[0].files;
                    if (files.length > 0) {
                        for (var i = 0; i < files.length; i++) {
                            formData.append(name, files[i]);
                        }
                    }
                } else {
                    formData.append(name, $field.val());
                }
            }
        });
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Limpiar array de imágenes a eliminar
                    adminImagesToDelete = [];
                    
                    $submitBtn.html('<span class="cf-btn-icon">✅</span><span class="cf-btn-text">¡Guardado!</span>');
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: 'Comentario actualizado correctamente por el administrador',
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        $('#cf-admin-edit-modal').fadeOut(300);
                        $('body').css('overflow', 'auto');
                        location.reload();
                    });
                } else {
                    var errorMsg = response.data ? response.data.message : 'No se pudo actualizar el comentario';
                    showNotification('❌ Error: ' + errorMsg, 'error');
                    
                    // Restaurar botones
                    $submitBtn.html(originalSubmitHtml).prop('disabled', false);
                    $cancelBtn.prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión: ' + error, 'error');
                
                // Restaurar botones
                $submitBtn.html(originalSubmitHtml).prop('disabled', false);
                $cancelBtn.prop('disabled', false);
            }
        });
    });
    
    // Responder a comentario (solo para administradores)
    $(document).on('click', '.cf-admin-reply-comment', function(e) {
        e.preventDefault();
        var commentId = $(this).data('comment-id');
        
        // Obtener datos del comentario original
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data && response.data.comment) {
                    openReplyModal(response.data.comment);
                } else {
                    var errorMsg = response.data ? response.data.message : 'No se pudo obtener el comentario';
                    showNotification('❌ Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión. Intenta nuevamente.', 'error');
            }
        });
    });
    // Función para crear y mostrar modal de respuesta
    function openReplyModal(comment) {
        // Crear modal si no existe
        if (!$('#cf-reply-modal').length) {
            createReplyModal();
        }
        
        // Llenar información del comentario original
        $('#reply-original-author').text(comment.author_name);
        $('#reply-original-title').text(comment.title);
        $('#reply-original-content').text(comment.content);
        $('#reply-comment-id').val(comment.id);
        
        // Limpiar formulario de respuesta
        $('#admin-reply-content').val('');
        
        // Mostrar modal
        $('#cf-reply-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
    }
    
    // Crear modal de respuesta
    function createReplyModal() {
        var modalHTML = `
        <div id="cf-reply-modal" class="cf-modal" style="display: none;">
            <div class="cf-modal-content cf-reply-modal-content">
                <div class="cf-modal-header">
                    <h3 class="text-white">💬 Responder Comentario</h3>
                    <button type="button" class="cf-modal-close">&times;</button>
                </div>
                <div class="cf-original-comment">
                    <h4>Comentario Original:</h4>
                    <div class="cf-original-info">
                        <strong>👤 <span id="reply-original-author"></span></strong>
                        <h5 id="reply-original-title"></h5>
                        <p id="reply-original-content"></p>
                    </div>
                </div>
                <div class="cf-modal-body">
                    <form id="cf-reply-form">
                        <input type="hidden" id="reply-comment-id" name="comment_id">
                        
                        <div class="cf-form-group">
                            <label for="admin-reply-content">Respuesta como Administrador</label>
                            <textarea id="admin-reply-content" name="reply_content" required rows="5" placeholder="Escribe tu respuesta aquí..."></textarea>
                        </div>
                        
                        <div class="cf-form-actions">
                            <button type="button" class="button cf-modal-close">Cancelar</button>
                            <button type="submit" class="button button-primary">📤 Enviar Respuesta</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        `;
        
        $('body').append(modalHTML);
    }
    
    // Cerrar modal de respuesta
    $(document).on('click', '#cf-reply-modal .cf-modal-close', function() {
        $('#cf-reply-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
    });
    
    // Cerrar modal al hacer clic en el fondo
    $(document).on('click', '#cf-reply-modal', function(e) {
        if (e.target === this) {
            $('#cf-reply-modal').fadeOut(300);
            $('body').css('overflow', 'auto');
        }
    });
    
    // Enviar respuesta de administrador
    $(document).on('submit', '#cf-reply-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var formData = $form.serialize();
        var $submitBtn = $form.find('button[type="submit"]');
        var originalText = $submitBtn.html();
        
        $submitBtn.html('🔄 Enviando...').prop('disabled', true);
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: formData + '&action=comentarios_admin_reply&nonce=' + comentarios_ajax.nonce,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('✅ Respuesta guardada exitosamente', 'success');
                    $('#cf-reply-modal').fadeOut(300);
                    $('body').css('overflow', 'auto');
                    
                    // Actualizar la columna de respuesta en la tabla
                    var commentId = $('#reply-comment-id').val();
                    var responseText = response.data.admin_response || $('#admin-reply-content').val();
                    var $responseColumn = $('tr[data-comment-id="' + commentId + '"] .cf-admin-response');
                    
                    if ($responseColumn.length) {
                        $responseColumn.html('<div class="cf-admin-response">✅ <small>' + responseText.substring(0, 50) + '...</small></div>');
                        $responseColumn.addClass('cf-updated');
                        
                        // Resaltar la actualización brevemente
                        setTimeout(function() {
                            $responseColumn.removeClass('cf-updated');
                        }, 2000);
                    }
                    
                    // Recargar página para ver cambios
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    var errorMsg = response.data ? response.data.message : 'No se pudo enviar la respuesta';
                    showNotification('❌ Error: ' + errorMsg, 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('❌ Error de conexión: ' + error, 'error');
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // =================================================================
    // INICIALIZACIÓN
    // =================================================================
    
    // Aplicar filtros iniciales si hay valores en la URL
    var urlParams = new URLSearchParams(window.location.search);
    var initialStatus = urlParams.get('status');
    var initialRating = urlParams.get('rating');
    
    if (initialStatus) {
        $('#admin-status-filter').val(initialStatus);
        currentFilters.status = initialStatus;
    }
    
    if (initialRating) {
        $('#admin-rating-filter').val(initialRating);
        currentFilters.rating = initialRating;
    }
    
    // Aplicar filtros iniciales si hay alguno
    if (initialStatus || initialRating) {
        applyFilters();
        highlightActiveFilters();
    }
    
    // Estadísticas iniciales
    updateRealTimeStats();
    
    // =================================================================
    // FUNCIONALIDAD DE FILTROS AVANZADOS PARA ADMINISTRADORES
    // =================================================================
    
    // Contador de resultados dinámico
    function updateResultsCount() {
        var visibleRows = $('.cf-admin-comment-row:visible').length;
        var totalRows = $('.cf-admin-comment-row').length;
        
        var countText = '';
        if (visibleRows === totalRows) {
            countText = '📊 Mostrando ' + totalRows + ' comentarios';
        } else {
            countText = '📊 Mostrando ' + visibleRows + ' de ' + totalRows + ' comentarios';
        }
        
        $('#cf-results-count').text(countText);
    }
    
    // Aplicar filtros en tiempo real (opcional para versión futura)
    function applyAdminFilters() {
        var productFilter = $('#filter_product').val();
        var ratingFilter = $('#filter_rating').val();
        var responseFilter = $('#filter_response').val();
        var statusFilter = $('#filter_status').val();
        
        $('.cf-admin-comment-row').each(function() {
            var $row = $(this);
            var showRow = true;
            
            // Filtro por producto (requeriría agregar data attributes)
            if (productFilter && $row.data('product-id') != productFilter) {
                showRow = false;
            }
            
            // Filtro por rating
            if (ratingFilter && $row.data('rating') != ratingFilter) {
                showRow = false;
            }
            
            // Filtro por respuesta
            if (responseFilter) {
                var hasResponse = $row.find('.cf-admin-response').text().trim() !== 'Sin responder';
                if (responseFilter === 'yes' && !hasResponse) {
                    showRow = false;
                } else if (responseFilter === 'no' && hasResponse) {
                    showRow = false;
                }
            }
            
            // Filtro por estado
            if (statusFilter && $row.data('status') != statusFilter) {
                showRow = false;
            }
            
            if (showRow) {
                $row.show();
            } else {
                $row.hide();
            }
        });
        
        updateResultsCount();
    }
    
    // Envío de formulario de filtros con loading
    $(document).on('submit', '#cf-admin-filters-form', function(e) {
        e.preventDefault();
        
        // Mostrar indicador de carga
        var $submitBtn = $(this).find('button[type="submit"]');
        var originalText = $submitBtn.html();
        $submitBtn.html('🔍 Aplicando...').prop('disabled', true);
        
        // Pequeño delay para mostrar el loading
        setTimeout(function() {
            // Enviar el formulario normalmente
            $('#cf-admin-filters-form')[0].submit();
        }, 500);
    });
    
    // Cambios dinámicos en selectores (opcional - para feedback inmediato)
    $('#filter_product, #filter_rating, #filter_response, #filter_status').on('change', function() {
        var hasFilters = false;
        
        $('#cf-admin-filters-form select').each(function() {
            if ($(this).val() !== '') {
                hasFilters = true;
                return false;
            }
        });
        
        // Resaltar botón de aplicar si hay cambios
        var $applyBtn = $('.cf-filter-apply');
        if (hasFilters) {
            $applyBtn.css('background', '#e67e22').html('🔍 Aplicar Filtros');
        } else {
            $applyBtn.css('background', '').html('🔍 Aplicar Filtros');
        }
    });
    
    // Confirmación antes de limpiar filtros
    $(document).on('click', '.cf-filter-clear', function(e) {
        var hasFilters = false;
        
        $('#cf-admin-filters-form select').each(function() {
            if ($(this).val() !== '') {
                hasFilters = true;
                return false;
            }
        });
        
        if (hasFilters) {
            if (!confirm('¿Estás seguro de que quieres limpiar todos los filtros?')) {
                e.preventDefault();
                return false;
            }
        }
    });
    
    // Actualizar contador inicial si existe
    if ($('#cf-results-count').length > 0) {
        updateResultsCount();
    }
    
    // =================================================================
    // FUNCIONALIDAD DE EDICIÓN PARA USUARIOS SUSCRIPTORES
    // =================================================================
    
    // Abrir modal de edición de usuario
    $(document).on('click', '.cf-user-edit-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        
        var commentId = $(this).data('comment-id');
        if (!commentId) {
            return;
        }
        
        openUserEditModal(commentId);
        
        return false; // Prevenir cualquier otro manejo
    });
    
    // Función para abrir modal de edición de usuario
    function openUserEditModal(commentId) {
        // Verificar que estamos en el contexto correcto
        if (!$('#cf-user-edit-modal').length) {
            return;
        }
        
        // Cerrar cualquier modal antiguo que pueda estar abierto
        $('#edit-comment-modal').hide();
        $('.comentarios-modal').hide();
        
        // Mostrar modal de inmediato con loading
        $('#cf-user-edit-modal').fadeIn(300);
        $('body').css('overflow', 'hidden');
        
        // Limpiar formulario y mostrar loading
        resetUserEditForm();
        showUserFormLoading(true);
        
        // Obtener datos del comentario
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            success: function(response) {
                if (response.success && response.data.comment) {
                    populateUserEditForm(response.data.comment, response.data.images || []);
                    showUserFormLoading(false);
                } else {
                    alert('Error: ' + (response.data.message || 'No se pudo cargar el comentario'));
                    $('#cf-user-edit-modal').fadeOut(300);
                    $('body').css('overflow', 'auto');
                }
            },
            error: function(xhr, status, error) {
                alert('Error de conexión: ' + error);
                $('#cf-user-edit-modal').fadeOut(300);
                $('body').css('overflow', 'auto');
            },
            complete: function() {
                showUserFormLoading(false);
            }
        });
    }
    
    // Función para llenar el formulario de edición de usuario
    function populateUserEditForm(comment, images) {
        // Llenar datos básicos
        $('#cf-user-comment-id').val(comment.id);
        $('#cf-user-rating').val(comment.rating);
        $('#cf-user-title').val(comment.title);
        $('#cf-user-content').val(comment.content);
        
        // Establecer país - convertir código a nombre y guardar en data-country-code
        var countryCode = comment.country || '';
        if (countryCode && countriesData[countryCode]) {
            var countryName = countriesData[countryCode].name;
            var countryInput = $('#cf-user-country');
            countryInput.val(countryName);
            countryInput.data('country-code', countryCode);
        } else {
            $('#cf-user-country').val('');
            $('#cf-user-country').data('country-code', '');
        }
        
        $('#cf-user-language').val(comment.language || '');
        $('#cf-user-travel-companion').val(comment.travel_companion || '');
        
        // Actualizar información del usuario
        $('.cf-user-name').text(comment.author_name || 'Usuario');
        
        // Actualizar contador de caracteres
        updateUserCharacterCount();
        
        // Cargar imágenes si existen
        displayAdminUserCurrentImages(images);
        
        // Inicializar autocompletado de países
        setTimeout(function() {
            initializeCountryAutocomplete();
        }, 100);
    }
    
    // Función para mostrar/ocultar loading en el formulario de usuario
    function showUserFormLoading(show) {
        if (show) {
            $('#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea').prop('disabled', true);
            $('.cf-btn-save').html('⏳ Cargando...').prop('disabled', true);
        } else {
            $('#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea').prop('disabled', false);
            $('.cf-btn-save').html('💾 Guardar Cambios').prop('disabled', false);
        }
    }
    
    // Función para resetear formulario de usuario
    function resetUserEditForm() {
        $('#cf-user-edit-form')[0].reset();
        $('#cf-user-current-images').empty();
        imagesToDelete = []; // Limpiar array de eliminación
        updateUserCharacterCount();
        
        // Limpiar estilos de validación
        $('#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea').css('border-color', '#e1e5e9');
    }
    
    // Función para mostrar imágenes actuales del usuario en modal de admin dashboard
    function displayAdminUserCurrentImages(images) {
        var $container = $('#cf-user-current-images');
        $container.empty();
        
        if (!images || images.length === 0) {
            $container.html('<p class="cf-no-images">📷 No hay imágenes en este comentario</p>');
            return;
        }
        
        images.forEach(function(image, index) {
            var imageHtml = 
                '<div class="cf-current-image" data-image-id="' + image.id + '">' +
                    '<img src="' + image.file_url + '" alt="' + (image.original_name || 'imagen.jpg') + '">' +
                    '<button type="button" class="cf-remove-image" data-image-id="' + image.id + '" title="Eliminar esta imagen">×</button>' +
                '</div>';
            $container.append(imageHtml);
        });
    }
    
    // Contador de caracteres para contenido de usuario
    function updateUserCharacterCount() {
        var content = $('#cf-user-content').val() || '';
        var count = content.length;
        var maxLength = 2000;
        
        $('#cf-user-content-count').text(count);
        
        if (count > maxLength * 0.9) {
            $('#cf-user-content-count').css('color', count >= maxLength ? '#dc3545' : '#ffc107');
        } else {
            $('#cf-user-content-count').css('color', '#6c757d');
        }
    }
    
    // Event listener para contador de caracteres de usuario
    $(document).on('input', '#cf-user-content', updateUserCharacterCount);
    
    // Array para almacenar IDs de imágenes a eliminar
    var imagesToDelete = [];
    
    // Eliminar imagen en modal de usuario (marcada para eliminación)
    $(document).on('click', '#cf-user-edit-modal .cf-remove-image', function(e) {
        e.preventDefault();
        var imageId = $(this).data('image-id');
        var $imageElement = $(this).closest('.cf-current-image');
        
        // Marcar visualmente como eliminada
        $imageElement.css('opacity', '0.3');
        $imageElement.addClass('marked-for-deletion');
        
        // Agregar a array de eliminación
        if (imagesToDelete.indexOf(imageId) === -1) {
            imagesToDelete.push(imageId);
        }
        
        // Ocultar el botón de eliminar
        $(this).hide();
        
        // Mostrar mensaje temporal
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Marcada para eliminar',
                text: 'La imagen se eliminará al guardar los cambios',
                icon: 'info',
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    // Función para eliminar imagen de usuario
    function deleteUserImage(imageId, $imageElement) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete_image',
                image_id: imageId,
                nonce: comentarios_ajax.nonce
            },
            beforeSend: function() {
                $imageElement.css('opacity', '0.5');
            },
            success: function(response) {
                if (response.success) {
                    $imageElement.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Si no quedan más imágenes, mostrar mensaje
                        if ($('#cf-user-current-images .cf-current-image').length === 0) {
                            $('#cf-user-current-images').html('<p class="cf-no-images">📷 No hay imágenes en este comentario</p>');
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
                    $imageElement.css('opacity', '1');
                    showNotification('❌ ' + (response.data.message || 'No se pudo eliminar la imagen'), 'error');
                }
            },
            error: function(xhr, status, error) {
                $imageElement.css('opacity', '1');
                showNotification('❌ Error de conexión al eliminar la imagen', 'error');
            }
        });
    }
    
    // Cerrar modal de usuario
    $(document).on('click', '#cf-user-edit-modal .cf-close-modal, #cf-user-edit-modal .cf-btn-cancel', function(e) {
        e.preventDefault();
        closeUserEditModal();
    });
    
    // Función para cerrar modal de edición de usuario
    function closeUserEditModal() {
        // Verificar si hay cambios sin guardar
        var hasChanges = false;
        
        $('#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea').each(function() {
            if ($(this).val() !== $(this).prop('defaultValue') && $(this).attr('type') !== 'hidden') {
                hasChanges = true;
                return false; // break
            }
        });
        
        if (hasChanges) {
            if (confirm('⚠️ Tienes cambios sin guardar. ¿Estás seguro de cerrar el modal?')) {
                doCloseUserModal();
            }
        } else {
            doCloseUserModal();
        }
    }
    
    function doCloseUserModal() {
        $('#cf-user-edit-modal').fadeOut(300);
        $('body').css('overflow', 'auto');
        
        // Limpiar estilos de validación
        $('#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea').css('border-color', '#e1e5e9');
    }
    
    // Validación en tiempo real para formulario de usuario
    $(document).on('blur', '#cf-user-edit-form input[required], #cf-user-edit-form select[required], #cf-user-edit-form textarea[required]', function() {
        var $field = $(this);
        var value = $field.val();
        
        if (!value || value.trim() === '') {
            $field.css('border-color', '#dc3545');
        } else {
            $field.css('border-color', '#28a745');
        }
    });
    
    // Remover estilos de error al empezar a escribir
    $(document).on('input', '#cf-user-edit-form input, #cf-user-edit-form select, #cf-user-edit-form textarea', function() {
        var $field = $(this);
        if ($field.css('border-color') === 'rgb(220, 53, 69)') { // color de error
            $field.css('border-color', '#e1e5e9');
        }
    });
    
    // Enviar formulario de edición del usuario
    $(document).on('submit', '#cf-user-edit-form', function(e) {
        e.preventDefault();
        
        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"]');
        var $cancelBtn = $form.find('.cf-btn-cancel');
        
        // Validar campos requeridos
        var hasErrors = false;
        $form.find('input[required], select[required], textarea[required]').each(function() {
            var $field = $(this);
            var value = $field.val();
            
            if (!value || value.trim() === '') {
                $field.css('border-color', '#dc3545');
                hasErrors = true;
            } else {
                $field.css('border-color', '#28a745');
            }
        });
        
        if (hasErrors) {
            Swal.fire({
                icon: 'warning',
                title: 'Campos incompletos',
                text: 'Por favor completa todos los campos obligatorios marcados en rojo',
                confirmButtonColor: '#f39c12'
            });
            return;
        }
        
        // Capturar todos los valores ANTES de deshabilitar campos
        // Obtener código del país desde data-country-code
        var countryInput = $('#cf-user-country');
        var countryCode = countryInput.data('country-code') || '';
        
        // Debug para verificar qué se está enviando
        console.log('=== DEBUG ADMIN EDIT ===');
        console.log('Input valor:', countryInput.val());
        console.log('Country code (data):', countryCode);
        console.log('=======================');
        
        var formValues = {
            comment_id: $('#cf-user-comment-id').val(),
            rating: $('#cf-user-rating').val(),
            title: $('#cf-user-title').val(),
            content: $('#cf-user-content').val(),
            country: countryCode,
            language: $('#cf-user-language').val(),
            travel_companion: $('#cf-user-travel-companion').val()
        };
        
        // Mostrar loading
        var originalText = $submitBtn.html();
        $submitBtn.html('💾 Guardando...').prop('disabled', true);
        $cancelBtn.prop('disabled', true);
        
        // Preparar datos con FormData para soportar archivos
        var formData = new FormData();
        formData.append('action', 'comentarios_edit');
        formData.append('comment_id', formValues.comment_id);
        formData.append('rating', formValues.rating);
        formData.append('title', formValues.title);
        formData.append('content', formValues.content);
        formData.append('country', formValues.country);
        formData.append('language', formValues.language);
        formData.append('travel_companion', formValues.travel_companion);
        formData.append('comentarios_nonce', comentarios_ajax.nonce);
        
        // Agregar IDs de imágenes a eliminar
        if (imagesToDelete.length > 0) {
            formData.append('delete_images', JSON.stringify(imagesToDelete));
        }
        
        // Agregar archivos de imagen si existen
        var fileInput = $('#cf-user-images')[0];
        if (fileInput && fileInput.files.length > 0) {
            for (var i = 0; i < fileInput.files.length; i++) {
                formData.append('new_images[]', fileInput.files[i]);
            }
        }
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Limpiar array de imágenes a eliminar
                    imagesToDelete = [];
                    
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: response.data.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(function() {
                        $('#cf-user-edit-modal').fadeOut(300);
                        $('body').css('overflow', 'auto');
                        window.location.reload();
                    });
                } else {
                    var errorMsg = response.data.message || 'Error desconocido del servidor';
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                var errorDetails = '';
                try {
                    if (xhr.responseText) {
                        errorDetails = '\n\nDetalles: ' + xhr.responseText.substring(0, 200);
                    }
                } catch (e) {
                    // Ignore JSON parsing errors
                }
                
                alert('❌ Error de conexión: ' + error + errorDetails);
            },
            complete: function() {
                $submitBtn.html(originalText).prop('disabled', false);
                $cancelBtn.prop('disabled', false);
            }
        });
    });
    
    // ================================
    // AUTOCOMPLETADO DE PAÍSES
    // ================================
    
    function initializeCountryAutocomplete() {
        var selectedCountryCode = "";
        
        $(".cf-country-input").each(function() {
            var input = $(this);
            var inputId = input.attr("id");
            var dropdownId = inputId + "-dropdown";
            var dropdown = $("#" + dropdownId);
            
            if (dropdown.length === 0) {
                dropdown = input.siblings(".cf-country-dropdown");
            }
            
            if (dropdown.length === 0) return;
            
            // Eliminar eventos previos
            input.off('input focus keydown');
            
            // Manejar input en el campo
            input.on('input focus', function() {
                var query = $(this).val().toLowerCase().trim();
                showCountryDropdown(input, dropdown, query);
            });
            
            // Ocultar dropdown al hacer clic fuera
            $(document).on('click', function(e) {
                if (!input.is(e.target) && !dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
                    dropdown.hide();
                }
            });
            
            // Manejar teclas
            input.on('keydown', function(e) {
                var highlighted = dropdown.find('.cf-country-option.cf-highlighted');
                var options = dropdown.find('.cf-country-option');
                
                if (e.keyCode === 40) { // Flecha abajo
                    e.preventDefault();
                    if (highlighted.length === 0) {
                        options.first().addClass('cf-highlighted');
                    } else {
                        highlighted.removeClass('cf-highlighted');
                        var next = highlighted.next('.cf-country-option');
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
                        var prev = highlighted.prev('.cf-country-option');
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
        });
        
        function showCountryDropdown(input, dropdown, query) {
            var html = "";
            var matchCount = 0;
            
            Object.keys(countriesData).forEach(function(code) {
                if (code === "") return;
                
                var country = countriesData[code];
                var name = country.name.toLowerCase();
                
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
                var code = $(this).data('code');
                var name = $(this).find('.cf-country-name-option').text();
                selectCountry(input, code, name);
                dropdown.hide();
            });
            
            // Agregar eventos de hover
            dropdown.find('.cf-country-option').on('mouseenter', function() {
                dropdown.find('.cf-country-option').removeClass('cf-highlighted');
                $(this).addClass('cf-highlighted');
            });
        }
        
        function selectCountry(input, code, name) {
            input.val(name);
            input.data('country-code', code);
            selectedCountryCode = code;
        }
    }
});