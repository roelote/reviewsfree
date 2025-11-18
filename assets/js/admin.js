/**
 * JavaScript Admin para Comentarios Free Plugin
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initializeAdminInterface();
        initializeCommentManagement();
        initializeSettings();
        initializeStatistics();
        initializeBulkActions();
    });

    /**
     * Inicializar interfaz de administración
     */
    function initializeAdminInterface() {
        // Tooltips
        initializeTooltips();
        
        // Confirmaciones
        initializeConfirmations();
        
        // Tabs si existen
        initializeTabs();
        
        // Búsqueda en tiempo real
        initializeSearch();
    }

    /**
     * Inicializar gestión de comentarios
     */
    function initializeCommentManagement() {
        // Acciones rápidas
        initializeQuickActions();
        
        // Preview de imágenes
        initializeImagePreviews();
        
        // Filtros avanzados
        initializeAdvancedFilters();
        
        // Auto-refresh de estadísticas
        initializeAutoRefresh();
    }

    /**
     * Inicializar acciones rápidas
     */
    function initializeQuickActions() {
        // Aprobar comentario
        $('.quick-approve').on('click', function(e) {
            e.preventDefault();
            
            const link = $(this);
            const commentId = link.data('comment-id');
            
            quickAction(commentId, 'approve', link);
        });
        
        // Marcar como spam
        $('.quick-spam').on('click', function(e) {
            e.preventDefault();
            
            const link = $(this);
            const commentId = link.data('comment-id');
            
            if (confirm('¿Estás seguro de marcar este comentario como spam?')) {
                quickAction(commentId, 'spam', link);
            }
        });
        
        // Eliminar comentario
        $('.quick-delete').on('click', function(e) {
            e.preventDefault();
            
            const link = $(this);
            const commentId = link.data('comment-id');
            
            if (confirm('¿Estás seguro de eliminar este comentario? Esta acción no se puede deshacer.')) {
                quickAction(commentId, 'delete', link);
            }
        });
    }

    /**
     * Ejecutar acción rápida
     */
    function quickAction(commentId, action, element) {
        const originalText = element.text();
        element.text('Procesando...').addClass('disabled');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'comentarios_quick_action',
                comment_id: commentId,
                quick_action: action,
                nonce: $('#comentarios_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    showAdminNotification(response.data.message, 'success');
                    
                    if (action === 'delete') {
                        // Remover fila de la tabla
                        element.closest('tr').fadeOut(function() {
                            $(this).remove();
                            updateRowColors();
                        });
                    } else {
                        // Actualizar estado visual
                        updateCommentStatus(element.closest('tr'), action);
                    }
                } else {
                    showAdminNotification(response.data.message, 'error');
                }
            },
            error: function() {
                showAdminNotification('Error al procesar la acción', 'error');
            },
            complete: function() {
                element.text(originalText).removeClass('disabled');
            }
        });
    }

    /**
     * Actualizar estado visual del comentario
     */
    function updateCommentStatus(row, newStatus) {
        const statusCell = row.find('.column-status');
        const actionsCell = row.find('.column-actions');
        
        // Actualizar texto del estado
        let statusText = '';
        let statusClass = '';
        
        switch(newStatus) {
            case 'approve':
                statusText = 'Aprobado';
                statusClass = 'status-approved';
                break;
            case 'spam':
                statusText = 'Spam';
                statusClass = 'status-spam';
                break;
        }
        
        statusCell.html('<span class="' + statusClass + '">' + statusText + '</span>');
        
        // Actualizar acciones disponibles
        updateRowActions(actionsCell, newStatus);
    }

    /**
     * Actualizar acciones disponibles en una fila
     */
    function updateRowActions(actionsCell, status) {
        const actions = actionsCell.find('.row-actions');
        actions.empty();
        
        const commentId = actionsCell.closest('tr').find('input[type="checkbox"]').val();
        
        if (status !== 'approved') {
            actions.append('<span class="approve"><a href="#" class="quick-approve" data-comment-id="' + commentId + '">Aprobar</a> | </span>');
        }
        
        if (status !== 'spam') {
            actions.append('<span class="spam"><a href="#" class="quick-spam" data-comment-id="' + commentId + '">Spam</a> | </span>');
        }
        
        actions.append('<span class="delete"><a href="#" class="quick-delete" data-comment-id="' + commentId + '">Eliminar</a></span>');
        
        // Reinicializar eventos
        initializeQuickActions();
    }

    /**
     * Actualizar colores de filas alternadas
     */
    function updateRowColors() {
        $('.wp-list-table tbody tr').each(function(index) {
            $(this).removeClass('alternate');
            if (index % 2 === 1) {
                $(this).addClass('alternate');
            }
        });
    }

    /**
     * Inicializar acciones en lote
     */
    function initializeBulkActions() {
        // Select all checkbox
        $('#cb-select-all-1, #cb-select-all-2').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('tbody input[type="checkbox"]').prop('checked', isChecked);
            updateBulkActionButton();
        });
        
        // Individual checkboxes
        $(document).on('change', 'tbody input[type="checkbox"]', function() {
            updateBulkActionButton();
            updateSelectAllCheckbox();
        });
        
        // Bulk action form
        $('.bulkactions').on('submit', function(e) {
            const action = $(this).find('select[name="action"]').val();
            const selectedComments = $('tbody input[type="checkbox"]:checked').length;
            
            if (!action || selectedComments === 0) {
                e.preventDefault();
                showAdminNotification('Selecciona una acción y al menos un comentario', 'warning');
                return;
            }
            
            let confirmMessage = '';
            switch(action) {
                case 'delete':
                    confirmMessage = '¿Estás seguro de eliminar los comentarios seleccionados?';
                    break;
                case 'spam':
                    confirmMessage = '¿Estás seguro de marcar los comentarios seleccionados como spam?';
                    break;
                case 'approve':
                    confirmMessage = '¿Estás seguro de aprobar los comentarios seleccionados?';
                    break;
            }
            
            if (confirmMessage && !confirm(confirmMessage)) {
                e.preventDefault();
            }
        });
    }

    /**
     * Actualizar estado del botón de acciones en lote
     */
    function updateBulkActionButton() {
        const selectedCount = $('tbody input[type="checkbox"]:checked').length;
        const bulkButton = $('.bulkactions input[type="submit"]');
        
        if (selectedCount > 0) {
            bulkButton.prop('disabled', false);
        } else {
            bulkButton.prop('disabled', true);
        }
    }

    /**
     * Actualizar checkbox "Seleccionar todo"
     */
    function updateSelectAllCheckbox() {
        const totalCheckboxes = $('tbody input[type="checkbox"]').length;
        const checkedCheckboxes = $('tbody input[type="checkbox"]:checked').length;
        
        const selectAllCheckboxes = $('#cb-select-all-1, #cb-select-all-2');
        
        if (checkedCheckboxes === totalCheckboxes) {
            selectAllCheckboxes.prop('checked', true).prop('indeterminate', false);
        } else if (checkedCheckboxes > 0) {
            selectAllCheckboxes.prop('checked', false).prop('indeterminate', true);
        } else {
            selectAllCheckboxes.prop('checked', false).prop('indeterminate', false);
        }
    }

    /**
     * Inicializar configuraciones
     */
    function initializeSettings() {
        // Validación de formulario de configuración
        $('#comentarios-settings-form').on('submit', function(e) {
            if (!validateSettingsForm()) {
                e.preventDefault();
            }
        });
        
        // Campo de email de notificación
        $('#notification_email').on('blur', function() {
            const email = $(this).val();
            if (email && !isValidEmail(email)) {
                $(this).addClass('error');
                showAdminNotification('Email no válido', 'error');
            } else {
                $(this).removeClass('error');
            }
        });
        
        // Campos numéricos
        $('input[type="number"]').on('change', function() {
            const min = parseInt($(this).attr('min'));
            const max = parseInt($(this).attr('max'));
            const value = parseInt($(this).val());
            
            if (value < min) {
                $(this).val(min);
            } else if (max && value > max) {
                $(this).val(max);
            }
        });
        
        // Preview de configuraciones
        initializeSettingsPreview();
    }

    /**
     * Validar formulario de configuración
     */
    function validateSettingsForm() {
        let isValid = true;
        
        // Limpiar errores anteriores
        $('.error').removeClass('error');
        
        // Validar email de notificación
        const email = $('#notification_email').val();
        if (email && !isValidEmail(email)) {
            $('#notification_email').addClass('error');
            isValid = false;
        }
        
        // Validar números
        $('input[type="number"]').each(function() {
            const value = parseInt($(this).val());
            const min = parseInt($(this).attr('min'));
            const max = parseInt($(this).attr('max'));
            
            if (isNaN(value) || value < min || (max && value > max)) {
                $(this).addClass('error');
                isValid = false;
            }
        });
        
        if (!isValid) {
            showAdminNotification('Por favor, corrige los errores en el formulario', 'error');
        }
        
        return isValid;
    }

    /**
     * Inicializar preview de configuraciones
     */
    function initializeSettingsPreview() {
        // Preview de tipos de archivo permitidos
        $('#allowed_file_types').on('input', function() {
            const types = $(this).val().split(',').map(type => type.trim());
            const preview = types.map(type => '<span class="file-type">' + type + '</span>').join(' ');
            $('#file-types-preview').html(preview);
        });
        
        // Preview de tamaño máximo
        $('#max_file_size').on('input', function() {
            const size = $(this).val();
            $('#max-size-preview').text(size + ' MB por imagen');
        });
    }

    /**
     * Inicializar estadísticas
     */
    function initializeStatistics() {
        // Gráficos si hay datos
        initializeCharts();
        
        // Actualización en tiempo real desactivada temporalmente
        // TODO: Implementar endpoint comentarios_get_live_stats en el backend
        /*
        setInterval(function() {
            updateLiveStats();
        }, 30000); // Cada 30 segundos
        */
    }

    /**
     * Inicializar gráficos
     */
    function initializeCharts() {
        // Solo si existe Chart.js o similar
        if (typeof Chart === 'undefined') {
            return;
        }
        
        // Gráfico de ratings
        const ratingChart = document.getElementById('rating-chart');
        if (ratingChart) {
            createRatingChart(ratingChart);
        }
        
        // Gráfico de comentarios por tiempo
        const timeChart = document.getElementById('time-chart');
        if (timeChart) {
            createTimeChart(timeChart);
        }
    }

    /**
     * Crear gráfico de ratings
     */
    function createRatingChart(canvas) {
        // Ejemplo de implementación
        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: ['5 estrellas', '4 estrellas', '3 estrellas', '2 estrellas', '1 estrella'],
                datasets: [{
                    data: window.ratingData || [0, 0, 0, 0, 0],
                    backgroundColor: ['#4CAF50', '#8BC34A', '#FFC107', '#FF9800', '#F44336']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    /**
     * Actualizar estadísticas en vivo
     */
    function updateLiveStats() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'comentarios_get_live_stats',
                nonce: $('#comentarios_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    updateStatCards(response.data);
                }
            }
        });
    }

    /**
     * Actualizar tarjetas de estadísticas
     */
    function updateStatCards(data) {
        $('.stat-card').each(function() {
            const type = $(this).data('stat-type');
            if (data[type]) {
                $(this).find('.stat-number').text(data[type]);
            }
        });
    }

    /**
     * Inicializar tooltips
     */
    function initializeTooltips() {
        $('[data-tooltip]').hover(
            function() {
                const tooltip = $('<div class="comentarios-tooltip-content">' + $(this).data('tooltip') + '</div>');
                $('body').append(tooltip);
                
                const pos = $(this).offset();
                tooltip.css({
                    position: 'absolute',
                    top: pos.top - tooltip.outerHeight() - 10,
                    left: pos.left + ($(this).outerWidth() / 2) - (tooltip.outerWidth() / 2)
                });
            },
            function() {
                $('.comentarios-tooltip-content').remove();
            }
        );
    }

    /**
     * Inicializar confirmaciones
     */
    function initializeConfirmations() {
        $('[data-confirm]').on('click', function(e) {
            const message = $(this).data('confirm');
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    }

    /**
     * Inicializar tabs
     */
    function initializeTabs() {
        $('.comentarios-admin-tabs a').on('click', function(e) {
            e.preventDefault();
            
            const target = $(this).attr('href');
            
            // Activar tab
            $('.comentarios-admin-tabs a').removeClass('active');
            $(this).addClass('active');
            
            // Mostrar contenido
            $('.tab-content').hide();
            $(target).show();
        });
    }

    /**
     * Inicializar búsqueda
     */
    function initializeSearch() {
        let searchTimeout;
        
        $('#comment-search').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val();
            
            searchTimeout = setTimeout(function() {
                if (query.length >= 3) {
                    performSearch(query);
                } else if (query.length === 0) {
                    clearSearch();
                }
            }, 500);
        });
    }

    /**
     * Realizar búsqueda
     */
    function performSearch(query) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'comentarios_search',
                query: query,
                nonce: $('#comentarios_nonce').val()
            },
            success: function(response) {
                if (response.success) {
                    updateSearchResults(response.data);
                }
            }
        });
    }

    /**
     * Actualizar resultados de búsqueda
     */
    function updateSearchResults(data) {
        const tbody = $('.wp-list-table tbody');
        tbody.empty();
        
        if (data.comments.length > 0) {
            data.comments.forEach(function(comment) {
                tbody.append(createCommentRow(comment));
            });
        } else {
            tbody.append('<tr><td colspan="8" style="text-align: center;">No se encontraron comentarios</td></tr>');
        }
        
        updateRowColors();
    }

    /**
     * Limpiar búsqueda
     */
    function clearSearch() {
        location.reload();
    }

    /**
     * Inicializar previews de imágenes
     */
    function initializeImagePreviews() {
        $('.comment-images-toggle').on('click', function(e) {
            e.preventDefault();
            
            const commentId = $(this).data('comment-id');
            const container = $('#images-modal-' + commentId);
            
            if (container.length === 0) {
                loadCommentImages(commentId);
            } else {
                container.toggle();
            }
        });
    }

    /**
     * Cargar imágenes de comentario
     */
    function loadCommentImages(commentId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'comentarios_get_images',
                comment_id: commentId,
                nonce: $('#comentarios_nonce').val()
            },
            success: function(response) {
                if (response.success && response.data.images.length > 0) {
                    showImageModal(commentId, response.data.images);
                }
            }
        });
    }

    /**
     * Mostrar modal de imágenes
     */
    function showImageModal(commentId, images) {
        const modal = $('<div class="comentarios-image-modal" id="images-modal-' + commentId + '">');
        const gallery = $('<div class="image-gallery">');
        
        images.forEach(function(image) {
            const img = $('<img src="' + image.file_url + '" alt="' + image.original_name + '">');
            gallery.append(img);
        });
        
        modal.append(gallery);
        $('body').append(modal);
        
        modal.on('click', function() {
            $(this).remove();
        });
    }

    /**
     * Inicializar filtros avanzados
     */
    function initializeAdvancedFilters() {
        // Filtro por fecha
        if ($('#date-range-picker').length > 0) {
            initializeDateRangePicker();
        }
        
        // Filtro por rating
        $('#rating-filter-admin').on('change', function() {
            applyAdminFilters();
        });
        
        // Filtro por país
        $('#country-filter-admin').on('change', function() {
            applyAdminFilters();
        });
    }

    /**
     * Inicializar selector de rango de fechas
     */
    function initializeDateRangePicker() {
        // Implementar con una librería como daterangepicker
        // $('#date-range-picker').daterangepicker({...});
    }

    /**
     * Aplicar filtros del admin
     */
    function applyAdminFilters() {
        const filters = {
            rating: $('#rating-filter-admin').val(),
            country: $('#country-filter-admin').val(),
            date_from: $('#date-from').val(),
            date_to: $('#date-to').val()
        };
        
        const queryString = $.param(filters);
        window.location.href = window.location.pathname + '?' + queryString;
    }

    /**
     * Inicializar auto-refresh
     */
    function initializeAutoRefresh() {
        if ($('#auto-refresh-enabled').is(':checked')) {
            setInterval(function() {
                if (document.visibilityState === 'visible') {
                    refreshCommentsList();
                }
            }, 60000); // Cada minuto
        }
    }

    /**
     * Refrescar lista de comentarios
     */
    function refreshCommentsList() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'comentarios_refresh_list',
                nonce: $('#comentarios_nonce').val()
            },
            success: function(response) {
                if (response.success && response.data.has_new) {
                    showAdminNotification('Nuevos comentarios disponibles. <a href="#" onclick="location.reload()">Recargar página</a>', 'info');
                }
            }
        });
    }

    /**
     * Mostrar notificación de admin
     */
    function showAdminNotification(message, type) {
        $('.comentarios-admin-notice').remove();
        
        const notice = $('<div class="notice comentarios-admin-notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap h1').after(notice);
        
        // Auto-hide después de 5 segundos (excepto errores)
        if (type !== 'error') {
            setTimeout(function() {
                notice.fadeOut();
            }, 5000);
        }
        
        // Botón de cerrar
        notice.find('.notice-dismiss').on('click', function() {
            notice.remove();
        });
    }

    /**
     * Utilidades
     */
    
    function isValidEmail(email) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return regex.test(email);
    }
    
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    function createCommentRow(comment) {
        // Función para crear una fila de comentario dinámicamente
        return `
            <tr>
                <th class="check-column">
                    <input type="checkbox" name="comment_ids[]" value="${comment.id}" />
                </th>
                <td class="author column-author">
                    <strong>${comment.author_name}</strong><br>
                    <a href="mailto:${comment.author_email}">${comment.author_email}</a>
                </td>
                <td class="comment column-comment">
                    <div class="comment-title"><strong>${comment.title}</strong></div>
                    <div class="comment-content">${comment.content.substring(0, 100)}...</div>
                </td>
                <td class="rating column-rating">
                    ${createStarRating(comment.rating)}
                </td>
                <td class="response column-response">
                    <a href="${comment.post_url}" target="_blank">${comment.post_title}</a>
                </td>
                <td class="date column-date">${formatDate(comment.created_at)}</td>
                <td class="status column-status">
                    <span class="status-${comment.status}">${comment.status}</span>
                </td>
                <td class="actions column-actions">
                    <div class="row-actions">
                        <!-- Acciones dinámicas basadas en el estado -->
                    </div>
                </td>
            </tr>
        `;
    }
    
    function createStarRating(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '<span class="star filled">★</span>';
            } else {
                stars += '<span class="star empty">☆</span>';
            }
        }
        return stars;
    }
    
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('es-ES') + ' ' + date.toLocaleTimeString('es-ES');
    }

})(jQuery);