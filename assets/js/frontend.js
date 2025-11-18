/**
 * JavaScript Frontend para Comentarios Free Plugin
 */

(function($) {
    'use strict';

    // Variables globales
    let currentRating = 0;
    let selectedFiles = [];
    let isSubmitting = false;

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        initializeCommentSystem();
        initializeUserPanel();
        initializeModals();
        initializeImageHandling();
    });

    /**
     * Inicializar sistema de comentarios
     */
    function initializeCommentSystem() {
        // Rating input
        initializeRatingInput();
        
        // Filtros
        initializeFilters();
        
        // Formulario de comentario
        initializeCommentForm();
        
        // Botón "Ver más"
        initializeLoadMore();
        
        // Preview de imágenes
        initializeImagePreview();
        
        // Texto truncado
        initializeReadMore();
    }

    /**
     * Inicializar input de rating
     */
    function initializeRatingInput() {
        $(document).on('click', '.rating-input .star', function() {
            const rating = $(this).data('rating');
            const container = $(this).closest('.rating-input');
            
            currentRating = rating;
            container.find('input[type="hidden"]').val(rating);
            
            // Actualizar visualización
            container.find('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('active');
                } else {
                    $(this).removeClass('active');
                }
            });
        });

        // Hover effect
        $(document).on('mouseenter', '.rating-input .star', function() {
            const rating = $(this).data('rating');
            const container = $(this).closest('.rating-input');
            
            container.find('.star').each(function(index) {
                if (index < rating) {
                    $(this).addClass('hover');
                } else {
                    $(this).removeClass('hover');
                }
            });
        });

        $(document).on('mouseleave', '.rating-input', function() {
            $(this).find('.star').removeClass('hover');
        });
    }

    /**
     * Inicializar filtros
     */
    function initializeFilters() {
        // Botón aplicar filtros
        $(document).on('click', '#cf-apply-filters', function() {
            const postId = getPostId();
            const ratingFilter = $('#cf-filter-rating').val();
            const countryFilter = $('#cf-filter-country').val();
            
            filterComments(postId, ratingFilter, countryFilter);
        });
        
        // Botón limpiar filtros
        $(document).on('click', '#cf-clear-filters', function() {
            $('#cf-filter-rating').val('');
            $('#cf-filter-country').val('');
            
            const postId = getPostId();
            filterComments(postId, '', '');
        });
        
        // Compatibilidad con filtros antiguos
        $('#rating-filter, #language-filter').on('change', function() {
            const postId = $(this).data('post-id');
            const ratingFilter = $('#rating-filter').val();
            const languageFilter = $('#language-filter').val();
            
            filterComments(postId, ratingFilter, languageFilter);
        });
    }
    
    /**
     * Obtener ID del post actual
     */
    function getPostId() {
        // Intentar obtener de diferentes fuentes
        if (typeof comentarios_ajax !== 'undefined' && comentarios_ajax.post_id) {
            return comentarios_ajax.post_id;
        }
        
        const postIdMeta = document.querySelector('meta[name="cf-post-id"]');
        if (postIdMeta) {
            return postIdMeta.getAttribute('content');
        }
        
        return window.location.pathname.match(/\/(\d+)\//)?.[1] || 0;
    }

    /**
     * Filtrar comentarios
     */
    function filterComments(postId, ratingFilter, countryFilter) {
        const $commentsList = $('.cf-comments-list');
        const $loadMoreContainer = $('.cf-load-more-container');
        
        // Fallback a selectors alternativos
        if (!$commentsList.length) {
            $commentsList = $('#comentarios-list');
        }
        if (!$loadMoreContainer.length) {
            $loadMoreContainer = $('.comentarios-load-more-container');
        }
        
        // Mostrar loading
        $commentsList.addClass('cf-loading');
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'cf_filter_comments',
                post_id: postId,
                rating_filter: ratingFilter,
                country_filter: countryFilter,
                nonce: comentarios_ajax.nonce
            },
            success: function(response) {
                $commentsList.removeClass('cf-loading');
                
                if (response.success) {
                    // Filtrar en el frontend si no hay respuesta del servidor
                    if (response.data && response.data.html) {
                        $commentsList.html(response.data.html);
                        
                        if (response.data.has_more) {
                            $loadMoreContainer.show();
                        } else {
                            $loadMoreContainer.hide();
                        }
                        
                        updateCommentCounter(response.data.total);
                    } else {
                        // Filtro del lado cliente
                        filterCommentsClient(ratingFilter, countryFilter);
                    }
                } else {
                    // Filtro del lado cliente como fallback
                    filterCommentsClient(ratingFilter, countryFilter);
                }
            },
            error: function() {
                $commentsList.removeClass('cf-loading');
                // Filtro del lado cliente como fallback
                filterCommentsClient(ratingFilter, countryFilter);
            }
        });
    }
    
    /**
     * Filtrar comentarios del lado cliente
     */
    function filterCommentsClient(ratingFilter, countryFilter) {
        const $comments = $('.cf-comment-item');
        let visibleCount = 0;
        
        $comments.each(function() {
            const $comment = $(this);
            const commentRating = $comment.data('rating') || $comment.find('.cf-rating').data('rating');
            const commentCountry = $comment.data('country') || $comment.find('.cf-country').text().trim();
            
            let showComment = true;
            
            // Filtro por rating
            if (ratingFilter && commentRating != ratingFilter) {
                showComment = false;
            }
            
            // Filtro por país
            if (countryFilter && commentCountry !== countryFilter) {
                showComment = false;
            }
            
            if (showComment) {
                $comment.show();
                visibleCount++;
            } else {
                $comment.hide();
            }
        });
        
        // Actualizar contador
        updateCommentCounter(visibleCount);
        
        // Mostrar mensaje si no hay resultados
        if (visibleCount === 0) {
            showNoResultsMessage();
        } else {
            hideNoResultsMessage();
        }
    }
    
    /**
     * Mostrar mensaje de no resultados
     */
    function showNoResultsMessage() {
        const $commentsList = $('.cf-comments-list').length ? $('.cf-comments-list') : $('#comentarios-list');
        
        if (!$commentsList.find('.cf-no-results').length) {
            $commentsList.append('<div class="cf-no-results"><p>No se encontraron comentarios con los filtros seleccionados.</p></div>');
        }
    }
    
    /**
     * Ocultar mensaje de no resultados
     */
    function hideNoResultsMessage() {
        $('.cf-no-results').remove();
    }

    /**
     * Inicializar formulario de comentario
     */
    function initializeCommentForm() {
        $('#comentarios-form').on('submit', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return;
            }
            
            const form = $(this);
            const formData = new FormData(this);
            
            // Validar formulario
            if (!validateCommentForm(form)) {
                return;
            }
            
            // Agregar archivos seleccionados
            selectedFiles.forEach(function(file, index) {
                formData.append('images[]', file);
            });
            
            isSubmitting = true;
            form.find('button[type="submit"]').prop('disabled', true).text('Enviando...');
            
            $.ajax({
                url: comentarios_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    isSubmitting = false;
                    form.find('button[type="submit"]').prop('disabled', false).text('Enviar Comentario');
                    
                    // Intentar parsear la respuesta si es string
                    if (typeof response === 'string') {
                        try {
                            response = JSON.parse(response);
                        } catch (e) {
                            showNotification('Error en la respuesta del servidor', 'error');
                            return;
                        }
                    }
                    
                    // Validar estructura de respuesta
                    if (!response || typeof response !== 'object') {
                        showNotification('Error: Respuesta inválida del servidor', 'error');
                        return;
                    }
                    
                    if (response.success) {
                        // Obtener mensaje desde diferentes ubicaciones posibles
                        let message = 'Comentario enviado correctamente';
                        if (response.data && response.data.message) {
                            message = response.data.message;
                        } else if (response.message) {
                            message = response.message;
                        }
                        
                        showNotification(message, 'success');
                        resetCommentForm(form);
                        
                        // Recargar comentarios
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        // Obtener mensaje de error desde diferentes ubicaciones posibles
                        let errorMessage = 'Error desconocido';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        } else if (response.message) {
                            errorMessage = response.message;
                        }
                        
                        showNotification(errorMessage, 'error');
                    }
                },
                error: function() {
                    isSubmitting = false;
                    form.find('button[type="submit"]').prop('disabled', false).text('Enviar Comentario');
                    showNotification(comentarios_ajax.strings.error, 'error');
                }
            });
        });
    }

    /**
     * Validar formulario de comentario
     */
    function validateCommentForm(form) {
        let isValid = true;
        
        // Limpiar errores anteriores
        form.find('.error').removeClass('error');
        
        // Validar campos obligatorios
        form.find('[required]').each(function() {
            const field = $(this);
            const value = field.val().trim();
            
            if (!value || (field.attr('name') === 'rating' && value == '0')) {
                field.addClass('error');
                isValid = false;
            }
        });
        
        // Validar email
        const email = form.find('input[type="email"]').val();
        if (email && !isValidEmail(email)) {
            form.find('input[type="email"]').addClass('error');
            isValid = false;
        }
        
        // Validar archivos
        if (selectedFiles.length > 5) {
            showNotification('Máximo 5 imágenes permitidas', 'error');
            isValid = false;
        }
        
        if (!isValid) {
            showNotification('Por favor, completa todos los campos obligatorios', 'error');
        }
        
        return isValid;
    }

    /**
     * Validar email
     */
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }

    /**
     * Resetear formulario
     */
    function resetCommentForm(form) {
        form[0].reset();
        currentRating = 0;
        selectedFiles = [];
        
        // Reset rating stars
        form.find('.rating-input .star').removeClass('active');
        form.find('input[name="rating"]').val(0);
        
        // Reset image preview
        form.find('#image-preview').empty();
        form.find('input[type="file"]').val('');
    }

    /**
     * Inicializar botón "Ver más"
     */
    function initializeLoadMore() {
        $(document).on('click', '#comentarios-load-more', function() {
            const button = $(this);
            const postId = button.data('post-id');
            const offset = button.data('offset');
            const ratingFilter = $('#rating-filter').val();
            const languageFilter = $('#language-filter').val();
            
            button.prop('disabled', true).text('Cargando...');
            
            $.ajax({
                url: comentarios_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'comentarios_load_more',
                    post_id: postId,
                    offset: offset,
                    rating_filter: ratingFilter,
                    language_filter: languageFilter,
                    nonce: comentarios_ajax.nonce
                },
                success: function(response) {
                    button.prop('disabled', false).text('Ver más comentarios');
                    
                    if (response.success) {
                        $('#comentarios-list').append(response.data.html);
                        
                        if (response.data.has_more) {
                            button.data('offset', offset + 10);
                        } else {
                            button.parent().hide();
                        }
                    } else {
                        showNotification(response.data.message, 'error');
                    }
                },
                error: function() {
                    button.prop('disabled', false).text('Ver más comentarios');
                    showNotification(comentarios_ajax.strings.error, 'error');
                }
            });
        });
    }

    /**
     * Inicializar preview de imágenes
     */
    function initializeImagePreview() {
        $(document).on('change', '#images', function() {
            const files = Array.from(this.files);
            
            // Validar archivos
            const validFiles = [];
            for (let file of files) {
                if (validateImageFile(file)) {
                    validFiles.push(file);
                }
            }
            
            selectedFiles = validFiles.slice(0, 5); // Máximo 5 imágenes
            updateImagePreview();
        });
    }
    
    /**
     * Validar archivo de imagen
     */
    function validateImageFile(file) {
        // Validar tipo
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png'];
        if (!allowedTypes.includes(file.type)) {
            showNotification('Solo se permiten archivos JPG y PNG', 'error');
            return false;
        }
        
        // Validar tamaño (5MB máximo)
        const maxSize = 5 * 1024 * 1024; // 5MB en bytes
        if (file.size > maxSize) {
            showNotification('El archivo ' + file.name + ' es demasiado grande. Máximo 5MB', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Actualizar preview de imágenes
     */
    function updateImagePreview() {
        const container = $('#image-preview');
        container.empty();
        
        selectedFiles.forEach(function(file, index) {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const previewItem = $(`
                        <div class="image-preview-item">
                            <img src="${e.target.result}" alt="${file.name}">
                            <button type="button" class="image-preview-remove" data-index="${index}">&times;</button>
                        </div>
                    `);
                    container.append(previewItem);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    /**
     * Manejar subida de imágenes
     */
    function initializeImageHandling() {
        // Remover imagen del preview
        $(document).on('click', '.image-preview-remove', function() {
            const index = $(this).data('index');
            selectedFiles.splice(index, 1);
            updateImagePreview();
        });
        
        // Drag & drop en la zona de carga
        const dropZone = $('.cf-file-upload-zone');
        
        dropZone.on('dragover dragenter', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).addClass('cf-drag-over');
        });
        
        dropZone.on('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('cf-drag-over');
        });
        
        dropZone.on('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $(this).removeClass('cf-drag-over');
            
            const files = Array.from(e.originalEvent.dataTransfer.files);
            
            // Validar y filtrar archivos
            const validFiles = [];
            for (let file of files) {
                if (validateImageFile(file)) {
                    validFiles.push(file);
                }
            }
            
            selectedFiles = validFiles.slice(0, 5);
            updateImagePreview();
            
            // Actualizar el input file
            const input = document.getElementById('images');
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            input.files = dt.files;
        });
        
        // Prevenir drag & drop por defecto en toda la página
        $(document).on('dragover drop', function(e) {
            e.preventDefault();
        });
        
        // Lightbox para imágenes de comentarios
        $(document).on('click', '.cf-comment-image', function() {
            const imgSrc = $(this).attr('src');
            const imgAlt = $(this).attr('alt') || 'Imagen';
            
            const lightbox = $(`
                <div class="cf-image-lightbox">
                    <button class="cf-lightbox-close">&times;</button>
                    <img src="${imgSrc}" alt="${imgAlt}" style="max-width: 90%; max-height: 90%; object-fit: contain;">
                </div>
            `);
            
            $('body').append(lightbox);
            
            // Cerrar lightbox al hacer click fuera o en el botón
            lightbox.on('click', function(e) {
                if (e.target === this || $(e.target).hasClass('cf-lightbox-close')) {
                    lightbox.remove();
                }
            });
            
            // Cerrar con tecla ESC
            $(document).on('keyup.lightbox', function(e) {
                if (e.keyCode === 27) { // ESC
                    lightbox.remove();
                    $(document).off('keyup.lightbox');
                }
            });
        });
    }

    /**
     * Inicializar panel de usuario
     */
    function initializeUserPanel() {
        // Filtro de estado en el panel de usuario
        $('#user-status-filter').on('change', function() {
            const status = $(this).val();
            filterUserComments(status);
        });
        
        // Botones de editar comentario
        $(document).on('click', '.btn-edit-comment', function() {
            const commentId = $(this).data('comment-id');
            openEditModal(commentId);
        });
        
        // Botones de eliminar comentario
        $(document).on('click', '.btn-delete-comment', function() {
            const commentId = $(this).data('comment-id');
            
            if (confirm(comentarios_ajax.strings.confirm_delete)) {
                deleteUserComment(commentId);
            }
        });
        
        // Guardar edición de comentario
        $('#save-comment-edit').on('click', function() {
            saveCommentEdit();
        });
    }

    /**
     * Filtrar comentarios del usuario
     */
    function filterUserComments(status) {
        $('.user-comment-item').each(function() {
            const commentStatus = $(this).data('status');
            
            if (status === '' || commentStatus === status) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });
    }

    /**
     * Abrir modal de edición
     */
    function openEditModal(commentId) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_get_comment',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const comment = response.data.comment;
                    
                    $('#edit-comment-id').val(comment.id);
                    $('#edit-title').val(comment.title);
                    $('#edit-content').val(comment.content);
                    
                    // Establecer rating
                    setEditRating(comment.rating);
                    
                    $('#edit-comment-modal').show();
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(comentarios_ajax.strings.error, 'error');
            }
        });
    }

    /**
     * Establecer rating en el modal de edición
     */
    function setEditRating(rating) {
        $('#edit-rating').val(rating);
        
        $('#edit-rating-input .star').each(function(index) {
            if (index < rating) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        });
    }

    /**
     * Guardar edición de comentario
     */
    function saveCommentEdit() {
        const form = $('#edit-comment-form');
        const formData = form.serialize();
        
        // Agregar action
        const data = formData + '&action=comentarios_edit';
        
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    $('#edit-comment-modal').hide();
                    
                    // Recargar página después de un breve delay
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(comentarios_ajax.strings.error, 'error');
            }
        });
    }

    /**
     * Eliminar comentario del usuario
     */
    function deleteUserComment(commentId) {
        $.ajax({
            url: comentarios_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'comentarios_delete',
                comment_id: commentId,
                nonce: comentarios_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message, 'success');
                    
                    // Remover del DOM
                    $('.user-comment-item[data-comment-id="' + commentId + '"]').fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showNotification(comentarios_ajax.strings.error, 'error');
            }
        });
    }

    /**
     * Inicializar modales
     */
    function initializeModals() {
        // Cerrar modal
        $(document).on('click', '.modal-close', function() {
            $(this).closest('.comentarios-modal').hide();
        });
        
        // Cerrar modal al hacer clic fuera
        $(document).on('click', '.comentarios-modal', function(e) {
            if (e.target === this) {
                $(this).hide();
            }
        });
        
        // Cerrar modal con ESC
        $(document).on('keyup', function(e) {
            if (e.keyCode === 27) {
                $('.comentarios-modal').hide();
            }
        });
        
        // Rating en modal de edición
        $(document).on('click', '#edit-rating-input .star', function() {
            const rating = $(this).data('rating');
            setEditRating(rating);
        });
    }

    /**
     * Mostrar notificación
     */
    function showNotification(message, type) {
        // Remover notificaciones anteriores
        $('.comentarios-notification').remove();
        
        const notification = $(`
            <div class="comentarios-notification comentarios-notification-${type}">
                <span>${message}</span>
                <button type="button" class="notification-close">&times;</button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-hide después de 5 segundos
        setTimeout(function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Cerrar manualmente
        notification.find('.notification-close').on('click', function() {
            notification.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Actualizar contador de comentarios
     */
    function updateCommentCounter(total) {
        $('.comentarios-counter').text(total);
    }

    /**
     * Inicializar funcionalidad de leer más/menos
     */
    function initializeReadMore() {
        $(document).on('click', '.cf-read-more-btn', function() {
            const button = $(this);
            const container = button.closest('.cf-text-truncated');
            const shortText = container.find('.cf-text-short');
            const fullText = container.find('.cf-text-full');
            const readMoreSpan = button.find('.cf-read-more-text');
            const readLessSpan = button.find('.cf-read-less-text');
            
            if (shortText.is(':visible')) {
                // Mostrar texto completo
                shortText.hide();
                fullText.show();
                readMoreSpan.hide();
                readLessSpan.show();
            } else {
                // Mostrar texto truncado
                fullText.hide();
                shortText.show();
                readLessSpan.hide();
                readMoreSpan.show();
            }
        });
    }

    /**
     * Utilidades
     */
    
    // Debounce function
    function debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction() {
            const context = this;
            const args = arguments;
            const later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    }
    
    // Scroll suave a elemento
    function smoothScrollTo(element) {
        $('html, body').animate({
            scrollTop: $(element).offset().top - 100
        }, 500);
    }
    
    // Validar tamaño de archivo
    function validateFileSize(file, maxSizeMB) {
        const maxSize = maxSizeMB * 1024 * 1024;
        return file.size <= maxSize;
    }
    
    // Formatear fecha
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    }

    // Lazy loading para imágenes
    function initializeLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img.lazy').forEach(function(img) {
                imageObserver.observe(img);
            });
        }
    }

    // Inicializar lazy loading cuando esté listo
    $(window).on('load', function() {
        initializeLazyLoading();
    });

})(jQuery);