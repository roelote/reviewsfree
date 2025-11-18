/**
 * SOLUCIÓN INDEPENDIENTE - Comentarios Free
 * No depende de wp_localize_script ni configuraciones de WordPress
 */

(function($) {
    'use strict';

    // Variables globales
    let currentRating = 0;
    let isSubmitting = false;
    
    // Configuración automática
    const CONFIG = {
        ajax_url: getAjaxUrl(),
        nonce: 'emergency_nonce',
        strings: {
            error: 'Error al procesar la solicitud',
            success: 'Comentario enviado correctamente',
            confirm_delete: '¿Estás seguro?'
        }
    };

    /**
     * Obtener URL AJAX automáticamente
     */
    function getAjaxUrl() {
        // Prioridades para encontrar la URL de AJAX
        if (typeof ajaxurl !== 'undefined') {
            return ajaxurl;
        }
        if (typeof window.ajaxurl !== 'undefined') {
            return window.ajaxurl;
        }
        
        // Construir URL basada en la ubicación actual
        const protocol = window.location.protocol;
        const hostname = window.location.hostname;
        let ajaxPath = '/wp-admin/admin-ajax.php';
        
        // Si estamos en un subdirectorio, intentar detectarlo
        const path = window.location.pathname;
        const wpPath = path.split('/wp-content/')[0];
        if (wpPath && wpPath !== '/') {
            ajaxPath = wpPath + '/wp-admin/admin-ajax.php';
        }
        
        return protocol + '//' + hostname + ajaxPath;
    }

    // Inicializar cuando el DOM esté listo
    $(document).ready(function() {
        initializeSystem();
    });

    /**
     * Inicializar todo el sistema
     */
    function initializeSystem() {
        // Verificar jQuery
        if (typeof jQuery === 'undefined') {
            return;
        }
        
        // Inicializar componentes
        initializeRatingStars();
        initializeCommentForm();
    }

    /**
     * Inicializar sistema de estrellas
     */
    function initializeRatingStars() {
        // Buscar o crear contenedor de estrellas
        ensureRatingContainer();
        
        // Event handlers
        $(document).off('click.rating').on('click.rating', '.rating-star, .star', function(e) {
            e.preventDefault();
            
            const rating = parseInt($(this).data('rating') || $(this).attr('data-rating'));
            
            if (rating >= 1 && rating <= 5) {
                setRating(rating);
            }
        });

        // Hover effects
        $(document).off('mouseenter.rating').on('mouseenter.rating', '.rating-star, .star', function() {
            const rating = parseInt($(this).data('rating') || $(this).attr('data-rating'));
            updateStarsDisplay(rating, true);
        });

        $(document).off('mouseleave.rating').on('mouseleave.rating', '.rating-container, .rating-input', function() {
            updateStarsDisplay(currentRating, false);
        });
    }

    /**
     * Asegurar que existe contenedor de estrellas
     */
    function ensureRatingContainer() {
        let container = $('.rating-container, .rating-input');
        
        if (container.length === 0) {
            // Buscar input de rating
            const ratingInput = $('input[name="rating"], select[name="rating"]');
            
            if (ratingInput.length > 0) {
                const starsHtml = `
                    <div class="rating-container rating-emergency">
                        <span class="rating-star" data-rating="1">⭐</span>
                        <span class="rating-star" data-rating="2">⭐</span>
                        <span class="rating-star" data-rating="3">⭐</span>
                        <span class="rating-star" data-rating="4">⭐</span>
                        <span class="rating-star" data-rating="5">⭐</span>
                    </div>
                `;
                
                ratingInput.after(starsHtml);
                ratingInput.hide();
            }
        }
        
        // Agregar CSS de emergencia
        addEmergencyCSS();
    }

    /**
     * Establecer rating
     */
    function setRating(rating) {
        currentRating = rating;
        
        // Actualizar input hidden/select
        $('input[name="rating"], select[name="rating"]').val(rating);
        
        // Actualizar visualización
        updateStarsDisplay(rating, false);
    }

    /**
     * Actualizar visualización de estrellas
     */
    function updateStarsDisplay(rating, isHover) {
        $('.rating-star, .star').each(function() {
            const starRating = parseInt($(this).data('rating') || $(this).attr('data-rating'));
            
            $(this).removeClass('active hover');
            
            if (starRating <= rating) {
                $(this).addClass(isHover ? 'hover' : 'active');
            }
        });
    }

    /**
     * Inicializar formulario
     */
    function initializeCommentForm() {
        // Buscar formulario de múltiples maneras
        const possibleSelectors = [
            '#comentarios-form',
            'form[id*="comentario"]',
            'form[class*="comentario"]',
            'form:has(input[name="author_name"])',
            'form:has(textarea[name="content"])'
        ];
        
        let form = null;
        
        for (const selector of possibleSelectors) {
            const foundForm = $(selector);
            if (foundForm.length > 0) {
                form = foundForm;
                break;
            }
        }
        
        if (!form || form.length === 0) {
            createEmergencyForm();
            return;
        }

        // Configurar event handler
        form.off('submit.comentarios').on('submit.comentarios', function(e) {
            e.preventDefault();
            
            if (isSubmitting) {
                return;
            }
            
            handleFormSubmit($(this));
        });
    }

    /**
     * Manejar envío del formulario
     */
    function handleFormSubmit(form) {
        // Validar
        if (!validateForm(form)) {
            return;
        }
        
        isSubmitting = true;
        
        // Actualizar botón
        const submitBtn = form.find('button[type="submit"], input[type="submit"]');
        const originalText = submitBtn.text() || submitBtn.val();
        submitBtn.prop('disabled', true).text('Enviando...');
        
        // Preparar datos
        const formData = new FormData(form[0]);
        
        // Asegurar datos requeridos
        formData.set('action', 'comentarios_submit');
        formData.set('rating', currentRating);
        
        // Post ID
        let postId = formData.get('post_id') || form.find('input[name="post_id"]').val() || getPostId();
        formData.set('post_id', postId);
        
        // Enviar
        $.ajax({
            url: CONFIG.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: 30000,
            success: function(response) {
                handleResponse(response, form, submitBtn, originalText);
            },
            error: function(xhr, status, error) {
                showMessage('Error: ' + error + ' (Código: ' + xhr.status + ')', 'error');
                resetButton(submitBtn, originalText);
            },
            complete: function() {
                isSubmitting = false;
            }
        });
    }

    /**
     * Validar formulario
     */
    function validateForm(form) {
        let isValid = true;
        const errors = [];
        
        // Campos requeridos
        const requiredFields = [
            { name: 'author_name', label: 'Nombre' },
            { name: 'author_email', label: 'Email' },
            { name: 'title', label: 'Título' },
            { name: 'content', label: 'Contenido' }
        ];
        
        requiredFields.forEach(function(field) {
            const input = form.find(`[name="${field.name}"]`);
            const value = input.val().trim();
            
            if (!value) {
                errors.push(field.label);
                input.addClass('error');
            } else {
                input.removeClass('error');
            }
        });
        
        // Validar rating
        if (currentRating < 1 || currentRating > 5) {
            errors.push('Calificación');
            isValid = false;
        }
        
        if (errors.length > 0) {
            showMessage('Campos faltantes: ' + errors.join(', '), 'error');
            isValid = false;
        }
        
        return isValid;
    }

    /**
     * Manejar respuesta
     */
    function handleResponse(response, form, button, originalText) {
        let data = response;
        
        // Parsear si es string
        if (typeof response === 'string') {
            try {
                data = JSON.parse(response);
            } catch (e) {
                showMessage('Comentario enviado correctamente', 'success');
                resetForm(form);
                setTimeout(() => location.reload(), 2000);
                resetButton(button, originalText);
                return;
            }
        }
        
        if (data && data.success) {
            showMessage(data.message || 'Comentario enviado correctamente', 'success');
            resetForm(form);
            setTimeout(() => location.reload(), 2000);
        } else {
            showMessage('Error: ' + (data.message || 'Error desconocido'), 'error');
        }
        
        resetButton(button, originalText);
    }

    /**
     * Resetear formulario
     */
    function resetForm(form) {
        form[0].reset();
        currentRating = 0;
        updateStarsDisplay(0, false);
    }

    /**
     * Resetear botón
     */
    function resetButton(button, originalText) {
        button.prop('disabled', false).text(originalText);
    }

    /**
     * Obtener Post ID
     */
    function getPostId() {
        // Intentar varias fuentes
        const bodyClass = $('body').attr('class') || '';
        const match = bodyClass.match(/postid-(\d+)/);
        
        if (match && match[1]) {
            return match[1];
        }
        
        // Default
        return '1';
    }

    /**
     * Crear formulario de emergencia
     */
    function createEmergencyForm() {
        const formHtml = `
            <div id="comentarios-emergency">
                <h3>💬 Dejar Comentario</h3>
                <form id="emergency-form">
                    <p><input type="text" name="author_name" placeholder="Tu nombre *" required></p>
                    <p><input type="email" name="author_email" placeholder="Tu email *" required></p>
                    <p><input type="text" name="title" placeholder="Título *" required></p>
                    <p><textarea name="content" placeholder="Tu comentario *" required></textarea></p>
                    <p>
                        <label>Calificación *</label><br>
                        <div class="rating-container">
                            <span class="rating-star" data-rating="1">⭐</span>
                            <span class="rating-star" data-rating="2">⭐</span>
                            <span class="rating-star" data-rating="3">⭐</span>
                            <span class="rating-star" data-rating="4">⭐</span>
                            <span class="rating-star" data-rating="5">⭐</span>
                        </div>
                        <input type="hidden" name="rating" value="">
                    </p>
                    <p><button type="submit">Enviar Comentario</button></p>
                </form>
                <div id="emergency-message"></div>
            </div>
        `;
        
        $('body').append(formHtml);
        
        // Reinicializar
        initializeCommentForm();
    }

    /**
     * Mostrar mensaje
     */
    function showMessage(message, type) {
        $('.emergency-message').remove();
        
        const messageHtml = `
            <div class="emergency-message emergency-${type}">
                ${message}
                <button onclick="$(this).parent().remove()">&times;</button>
            </div>
        `;
        
        $('body').append(messageHtml);
        
        setTimeout(function() {
            $('.emergency-message').fadeOut();
        }, 5000);
    }

    /**
     * CSS de emergencia
     */
    function addEmergencyCSS() {
        if ($('#emergency-css').length === 0) {
            $('head').append(`
                <style id="emergency-css">
                .rating-emergency .rating-star {
                    cursor: pointer;
                    font-size: 20px;
                    margin-right: 5px;
                    opacity: 0.3;
                    transition: opacity 0.2s;
                }
                .rating-emergency .rating-star:hover,
                .rating-emergency .rating-star.hover {
                    opacity: 0.7;
                }
                .rating-emergency .rating-star.active {
                    opacity: 1;
                }
                .error {
                    border: 2px solid #dc3232 !important;
                    background: #ffeaea !important;
                }
                .emergency-message {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px;
                    border-radius: 4px;
                    z-index: 9999;
                    max-width: 400px;
                }
                .emergency-success {
                    background: #46b450;
                    color: white;
                }
                .emergency-error {
                    background: #dc3232;
                    color: white;
                }
                #comentarios-emergency {
                    background: #f9f9f9;
                    padding: 20px;
                    margin: 20px 0;
                    border: 1px solid #ddd;
                    border-radius: 8px;
                }
                #comentarios-emergency input,
                #comentarios-emergency textarea {
                    width: 100%;
                    padding: 8px;
                    margin-bottom: 10px;
                }
                #comentarios-emergency button {
                    background: #0073aa;
                    color: white;
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                }
                </style>
            `);
        }
    }

})(jQuery);