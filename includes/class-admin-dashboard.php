<?php
/**
 * Clase Admin Dashboard para usuarios suscriptores - Panel de Comentarios
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Admin_Dashboard {
    
    private $database;
    
    public function __construct() {
        $this->database = new ComentariosFree_Database();
        
        // Hooks para el dashboard
        add_action('admin_menu', array($this, 'add_user_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Agregar menú en el dashboard para usuarios
     */
    public function add_user_menu() {
        // Solo mostrar para usuarios logueados (cualquier rol)
        if (current_user_can('read')) {
            add_menu_page(
                '📝 Mis Reseñas',                    // Page title
                '📝 Mis Reseñas',                    // Menu title  
                'read',                              // Capability (básico para subscriber)
                'comentarios-free-user-panel',       // Menu slug
                array($this, 'display_user_dashboard'), // Callback
                'dashicons-star-filled',             // Icon
                30                                   // Position
            );
        }
        
        // Para administradores - panel completo
        if (current_user_can('manage_options')) {
            add_submenu_page(
                'comentarios-free-user-panel',
                '⚙️ Gestión de Comentarios',
                '⚙️ Administrar Todo',
                'manage_options',
                'comentarios-free-admin-panel',
                array($this, 'display_admin_panel')
            );
        }
    }
    
    /**
     * Encolar assets para el dashboard
     */
    public function enqueue_admin_assets($hook) {
        // Solo cargar en nuestras páginas
        if (strpos($hook, 'comentarios-free') !== false) {
            wp_enqueue_style('comentarios-free-admin-dashboard', plugins_url('../assets/css/admin-dashboard.css', __FILE__), array(), '1.0.8');
            
            // SweetAlert2 se carga centralizadamente en comentarios-free.php
            // NO cargar aquí para evitar conflictos que rompen el admin
            
                        wp_enqueue_script('comentarios-free-admin-dashboard', plugins_url('../assets/js/admin-dashboard.js', __FILE__), array('jquery'), '1.1.0', true);
            
            // Variables para AJAX
            wp_localize_script('comentarios-free-admin-dashboard', 'comentarios_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('comentarios_free_nonce'),
                'countries' => ComentariosFree_Countries::get_countries_list(),
                'strings' => array(
                    'confirm_delete' => '¿Estás seguro de eliminar esta reseña?',
                    'error' => 'Error al procesar la solicitud',
                    'success' => 'Operación completada exitosamente'
                )
            ));
        }
    }
    
    /**
     * Mostrar dashboard de usuario (para suscriptores y otros roles)
     */
    public function display_user_dashboard() {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        echo '<div class="wrap comentarios-user-dashboard">';
        echo '<h1>📝 Mis Reseñas y Comentarios</h1>';
        
        // Obtener comentarios del usuario
        $comments = $this->database->get_comments(array(
            'user_id' => $user_id,
            'limit' => 0
        ));
        
        // Estadísticas rápidas
        $this->display_user_stats($comments, $current_user);
        
        // Lista de comentarios con nombres de productos
        echo '<div id="user-comments-container">';
        $this->display_user_comments_table($comments);
        echo '</div>';
        
        // Modal de edición para usuarios suscriptores
        $this->render_user_edit_modal();
        
        echo '</div>';
    }
    
    /**
     * Mostrar estadísticas del usuario
     */
    private function display_user_stats($comments, $user) {
        $total = count($comments);
        $approved = count(array_filter($comments, function($c) { return $c->status === 'approved'; }));
        $pending = count(array_filter($comments, function($c) { return $c->status === 'pending'; }));
        
        echo '<div class="cf-user-stats-grid">';
        
        echo '<div class="cf-stat-card cf-stat-total">';
        echo '<div class="cf-stat-number">' . $total . '</div>';
        echo '<div class="cf-stat-label">💬 Total Reseñas</div>';
        echo '</div>';
        
        echo '<div class="cf-stat-card cf-stat-approved">';
        echo '<div class="cf-stat-number">' . $approved . '</div>';
        echo '<div class="cf-stat-label">✅ Aprobadas</div>';
        echo '</div>';
        
        echo '<div class="cf-stat-card cf-stat-pending">';
        echo '<div class="cf-stat-number">' . $pending . '</div>';
        echo '<div class="cf-stat-label">⏳ Pendientes</div>';
        echo '</div>';
        
        echo '</div>';
    }
    
    /**
     * Mostrar tabla de comentarios con nombres de productos
     */
    private function display_user_comments_table($comments) {
        if (empty($comments)) {
            echo '<div class="cf-no-comments">';
            echo '<div class="cf-empty-state">';
            echo '<div class="cf-empty-icon">📝</div>';
            echo '<h3>¡Aún no tienes reseñas!</h3>';
            echo '<p>Comienza dejando reseñas en nuestros productos y contenidos.</p>';
            echo '<a href="' . home_url() . '" class="button button-primary">🏠 Ir al sitio web</a>';
            echo '</div>';
            echo '</div>';
            return;
        }
        
        echo '<div class="cf-comments-table-container">';
        echo '<table class="wp-list-table widefat fixed striped cf-comments-table">';
        
        // Header
        echo '<thead>';
        echo '<tr>';
        echo '<th class="cf-col-rating">Calificación</th>';
        echo '<th class="cf-col-product">🏷️ Producto</th>';
        echo '<th class="cf-col-title">Título</th>';
        echo '<th class="cf-col-status">Estado</th>';
        echo '<th class="cf-col-date">Fecha</th>';
        echo '<th class="cf-col-actions">Acciones</th>';
        echo '</tr>';
        echo '</thead>';
        
        echo '<tbody>';
        foreach ($comments as $comment) {
            $this->display_comment_row($comment);
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Mostrar fila de comentario en la tabla
     */
    private function display_comment_row($comment) {
        $post = get_post($comment->post_id);
        $post_title = $post ? $post->post_title : '❌ Contenido eliminado';
        $post_url = $post ? get_permalink($post->ID) : '#';
        
        $status_class = 'cf-status-' . $comment->status;
        $status_text = $comment->status === 'approved' ? '✅ Aprobado' : '⏳ Pendiente';
        
        echo '<tr class="cf-comment-row ' . $status_class . '" data-comment-id="' . $comment->id . '" data-status="' . $comment->status . '">';
        
        // Calificación
        echo '<td class="cf-col-rating">';
        echo '<div class="cf-rating-display" data-rating="' . $comment->rating . '">';
        for ($i = 1; $i <= 5; $i++) {
            $class = $i <= $comment->rating ? 'cf-star-filled' : 'cf-star-empty';
            echo '<span class="cf-star ' . $class . '">⭐</span>';
        }
        echo '<div class="cf-rating-number">' . $comment->rating . '/5</div>';
        echo '</div>';
        echo '</td>';
        
        // Producto
        echo '<td class="cf-col-product">';
        if ($post) {
            echo '<div class="cf-product-link">';
            echo '<a href="' . esc_url($post_url) . '" target="_blank" title="Ver producto">';
            echo esc_html($post_title);
            echo '</a>';
            echo '</div>';
        } else {
            echo '<span class="cf-product-deleted">❌ ' . esc_html($post_title) . '</span>';
        }
        echo '</td>';
        
        // Título del comentario
        echo '<td class="cf-col-title">';
        echo '<div class="cf-comment-title">' . esc_html($comment->title) . '</div>';
        echo '<div class="cf-comment-excerpt">' . esc_html(wp_trim_words($comment->content, 15)) . '</div>';
        echo '</td>';
        
        // Estado
        echo '<td class="cf-col-status">';
        echo '<span class="cf-status-badge ' . $status_class . '">' . $status_text . '</span>';
        echo '</td>';
        
        // Fecha
        echo '<td class="cf-col-date">';
        echo '<div class="cf-date-display">';
        echo '<strong>' . date_i18n('d/m/Y', strtotime($comment->created_at)) . '</strong>';
        echo '<div>' . date_i18n('H:i', strtotime($comment->created_at)) . '</div>';
        echo '</div>';
        echo '</td>';
        
        // Acciones
        echo '<td class="cf-col-actions">';
        echo '<div class="cf-actions-group">';
        
        if ($post) {
            echo '<a href="' . esc_url($post_url . '#comment-' . $comment->id) . '" target="_blank" class="button button-small" title="Ver comentario">';
            echo '👁️';
            echo '</a>';
        }
        
        // Solo permitir editar si está aprobado Y no ha sido editado antes
        $edit_count = isset($comment->edit_count) ? intval($comment->edit_count) : 0;
        if ($comment->status === 'approved' && $edit_count == 0) {
            echo '<button type="button" class="button button-small cf-user-edit-btn" data-comment-id="' . $comment->id . '" title="Editar comentario (solo 1 vez)">';
            echo '✏️';
            echo '</button>';
        } elseif ($edit_count > 0) {
            echo '<span class="button button-small button-disabled" title="Ya editaste este comentario">';
            echo '✅';
            echo '</span>';
        }
        
        // Opción de eliminar comentario
        echo '<button type="button" class="button button-small cf-delete-comment button-delete" data-comment-id="' . $comment->id . '" title="Eliminar comentario permanentemente">';
        echo '🗑️';
        echo '</button>';
        
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    /**
     * Renderizar modal de edición para usuarios suscriptores
     */
    private function render_user_edit_modal() {
        echo '<div id="cf-user-edit-modal" class="cf-modal" style="display:none;">';
        echo '<div class="cf-modal-overlay"></div>';
        echo '<div class="cf-modal-content cf-compact">';
        
        // Header del modal
        echo '<div class="cf-modal-header">';
        echo '<h2>✏️ Editar Mi Comentario <span class="cf-user-badge">USUARIO</span></h2>';
        echo '<button type="button" class="cf-close-modal">&times;</button>';
        echo '</div>';
        
        // Info del usuario compacta
        echo '<div class="cf-user-info-compact">';
        echo '<div class="cf-user-avatar">👤</div>';
        echo '<div class="cf-user-details">';
        echo '<div class="cf-user-name">' . wp_get_current_user()->display_name . '</div>';
        echo '<div class="cf-edit-warning">⚠️ <strong>Solo puedes editar 1 vez</strong></div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="cf-modal-body">';
        
        // Formulario de edición
        echo '<form id="cf-user-edit-form">';
        echo '<input type="hidden" id="cf-user-comment-id" name="comment_id" value="">';
        
        echo '<div class="cf-form-grid cf-form-grid-2cols">';
        
        // Primera columna
        echo '<div class="cf-form-column">';
        
        // Calificación
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-rating">⭐ <strong>Calificación</strong> <span class="cf-required">*</span></label>';
        echo '<select id="cf-user-rating" name="rating" required class="cf-input-compact">';
        echo '<option value="">Selecciona calificación...</option>';
        for ($i = 5; $i >= 1; $i--) {
            $stars = str_repeat('⭐', $i);
            echo '<option value="' . $i . '">' . $stars . ' ' . $i . ' estrellas</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Título del comentario
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-title">📝 <strong>Título del comentario</strong> <span class="cf-required">*</span></label>';
        echo '<input type="text" id="cf-user-title" name="title" required maxlength="200" class="cf-input-compact" placeholder="Escribe un título llamativo...">';
        echo '<small>Máximo 200 caracteres</small>';
        echo '</div>';
        
        // País - Autocompletado
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-country">🌍 <strong>País</strong></label>';
        echo '<div class="cf-country-autocomplete-container">';
        echo '<input type="text" id="cf-user-country" class="cf-country-input cf-input-compact" placeholder="Elegir país (opcional)" autocomplete="off">';
        echo '<div class="cf-country-dropdown" id="cf-user-country-dropdown" style="display: none;"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Fin primera columna
        
        // Segunda columna
        echo '<div class="cf-form-column">';
        
        // Compañía de viaje
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-travel-companion">👥 <strong>¿Con quién viajaste?</strong></label>';
        echo '<select id="cf-user-travel-companion" name="travel_companion" class="cf-input-compact">';
        echo '<option value="">Selecciona una opción...</option>';
        echo '<option value="solo">🚶 Solo</option>';
        echo '<option value="en_pareja">💑 En pareja</option>';
        echo '<option value="en_familia">👨‍👩‍👧‍👦 En familia</option>';
        echo '<option value="con_amigos">👥 Con amigos</option>';
        echo '</select>';
        echo '</div>';
        
        // Comentario
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-content">� <strong>Tu comentario</strong> <span class="cf-required">*</span></label>';
        echo '<textarea id="cf-user-content" name="content" required rows="4" maxlength="2000" class="cf-input-compact" placeholder="Comparte tu experiencia detallada..."></textarea>';
        echo '<small>Máximo 2000 caracteres. <span id="cf-user-content-count" style="color: #6c757d;">0</span> caracteres usados.</small>';
        echo '</div>';
        
        // Idioma
        echo '<div class="cf-form-group">';
        echo '<label for="cf-user-language">🌍 <strong>Idioma</strong> <span class="cf-required">*</span></label>';
        echo '<select id="cf-user-language" name="language" required class="cf-input-compact">';
        echo '<option value="">Selecciona idioma...</option>';
        echo '<option value="es">🇪🇸 Español</option>';
        echo '<option value="en">🇺🇸 English</option>';
        echo '<option value="de">🇩🇪 Deutsch</option>';
        echo '<option value="fr">🇫🇷 Français</option>';
        echo '<option value="pt">🇧🇷 Português</option>';
        echo '<option value="it">🇮🇹 Italiano</option>';
        echo '<option value="nl">🇳🇱 Nederlands</option>';
        echo '<option value="zh">🇨🇳 中文</option>';
        echo '<option value="ja">🇯🇵 日本語</option>';
        echo '<option value="ko">🇰🇷 한국어</option>';
        echo '<option value="he">🇮🇱 עברית</option>';
        echo '<option value="otros">🌐 Otros</option>';
        echo '</select>';
        echo '</div>';
        
        echo '</div>'; // Fin segunda columna
        
        echo '</div>'; // Fin cf-form-grid
        
        // Sección de fotos - 100% ancho fuera del grid
        echo '<div class="cf-form-photos-section">';
        echo '<div class="cf-form-group">';
        echo '<label>📷 <strong>Fotos</strong></label>';
        echo '<div id="cf-user-current-images" class="cf-current-images">';
        echo '<p class="cf-no-images">📷 No hay imágenes en este comentario</p>';
        echo '</div>';
        echo '<div class="cf-photo-controls">';
        echo '<input type="file" name="new_images[]" id="cf-user-images" accept="image/*" multiple class="cf-file-input">';
        echo '</div>';
        echo '<small class="cf-file-help">Máximo 5 fotos (JPG, PNG, GIF, WebP - 2MB c/u). Clic en × para eliminar.</small>';
        echo '</div>';
        echo '</div>'; // Fin cf-form-photos-section
        echo '</form>';
        echo '</div>';
        
        // Footer del modal
        echo '<div class="cf-modal-footer">';
        echo '<button type="button" class="button cf-btn-cancel">❌ Cancelar</button>';
        echo '<button type="submit" form="cf-user-edit-form" class="button button-primary cf-btn-save">💾 Guardar Cambios</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Panel de administración completo
     */
    public function display_admin_panel() {
        if (!current_user_can('manage_options')) {
            wp_die('No tienes permisos para acceder a esta página.');
        }
        
        echo '<div class="wrap comentarios-admin-dashboard">';
        echo '<h1>⚙️ Gestión Completa de Comentarios</h1>';
        
        // Estadísticas globales (con caché de 5 minutos)
        echo '<div class="cf-admin-stats">';
        echo '<h2>📊 Estadísticas Generales</h2>';
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'comentarios_free';
        
        // Intentar obtener estadísticas del caché
        $cache_key = 'cf_admin_stats';
        $stats = wp_cache_get($cache_key);
        
        if (false === $stats) {
            $stats = $wpdb->get_row("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
                    AVG(rating) as avg_rating,
                    COUNT(DISTINCT user_id) as unique_users,
                    COUNT(DISTINCT post_id) as unique_posts
                FROM $table_name
            ");
            
            // Guardar en caché por 5 minutos
            wp_cache_set($cache_key, $stats, '', 300);
        }
        
        if ($stats) {
            echo '<div class="cf-global-stats-grid">';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . $stats->total . '</div>';
            echo '<div class="cf-stat-label">💬 Total Comentarios</div>';
            echo '</div>';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . $stats->approved . '</div>';
            echo '<div class="cf-stat-label">✅ Aprobados</div>';
            echo '</div>';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . $stats->pending . '</div>';
            echo '<div class="cf-stat-label">⏳ Pendientes</div>';
            echo '</div>';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . number_format($stats->avg_rating, 1) . '</div>';
            echo '<div class="cf-stat-label">⭐ Rating Promedio</div>';
            echo '</div>';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . $stats->unique_users . '</div>';
            echo '<div class="cf-stat-label">👥 Usuarios Únicos</div>';
            echo '</div>';
            
            echo '<div class="cf-stat-card">';
            echo '<div class="cf-stat-number">' . $stats->unique_posts . '</div>';
            echo '<div class="cf-stat-label">📄 Posts con Comentarios</div>';
            echo '</div>';
            
            echo '</div>';
        }
        
        echo '</div>';
        
        // Filtros de administrador
        echo '<h2>🔍 Filtros de Búsqueda</h2>';
        $this->display_admin_filters();
        
        // Lista de todos los comentarios con paginación
        echo '<h2>📝 Todos los Comentarios</h2>';
        
        // Aplicar filtros y paginación
        $filter_args = $this->get_filter_args();
        $pagination_data = $this->get_comments_with_pagination($filter_args);
        
        // Mostrar información de paginación
        $this->display_pagination_info($pagination_data);
        
        // Mostrar controles de paginación (arriba)
        $this->display_pagination_controls($pagination_data, 'top');
        
        // Mostrar tabla de comentarios
        $this->display_admin_comments_table($pagination_data['comments']);
        
        // Mostrar controles de paginación (abajo)
        $this->display_pagination_controls($pagination_data, 'bottom');
        
        // Modal para edición de comentarios
        $this->render_admin_edit_modal();
        
        echo '</div>';
    }
    
    /**
     * Tabla de administración con todos los comentarios
     */
    private function display_admin_comments_table($comments) {
        echo '<div class="cf-admin-table-container">';
        echo '<table class="wp-list-table widefat fixed striped cf-admin-comments-table">';
        
        echo '<thead>';
        echo '<tr>';
        echo '<th>👤 Usuario</th>';
        echo '<th>🏷️ Producto</th>';
        echo '<th>⭐ Rating</th>';
        echo '<th>📝 Comentario</th>';
        echo '<th>💬 Respuesta Admin</th>';
        echo '<th>📊 Estado</th>';
        echo '<th>📅 Fecha</th>';
        echo '<th>⚙️ Acciones</th>';
        echo '</tr>';
        echo '</thead>';
        
        echo '<tbody>';
        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $this->display_admin_comment_row($comment);
            }
        } else {
            echo '<tr><td colspan="8" style="text-align: center; padding: 20px;">📝 No hay comentarios que mostrar</td></tr>';
        }
        echo '</tbody>';
        
        echo '</table>';
        echo '</div>';
    }
    
    /**
     * Fila de comentario en tabla de administrador
     */
    private function display_admin_comment_row($comment) {
        $user = get_user_by('id', $comment->user_id);
        $user_name = $user ? $user->display_name : $comment->author_name;
        $post_title = get_the_title($comment->post_id);
        
        $status_class = 'cf-status-' . $comment->status;
        $status_text = $comment->status === 'approved' ? '✅ Aprobado' : '⏳ Pendiente';
        
        echo '<tr class="cf-comment-row" data-comment-id="' . $comment->id . '">';
        
        // Usuario
        echo '<td>';
        echo '<div class="cf-user-info">';
        echo '<strong>' . esc_html($user_name) . '</strong><br>';
        echo '<small>' . esc_html($comment->author_email) . '</small>';
        if (!empty($comment->country)) {
            echo '<br><small>🌍 ' . esc_html($comment->country) . '</small>';
        }
        echo '</div>';
        echo '</td>';
        
        // Producto/Post
        echo '<td>';
        echo '<a href="' . get_permalink($comment->post_id) . '" target="_blank">';
        echo esc_html(wp_trim_words($post_title, 8));
        echo '</a>';
        echo '</td>';
        
        // Rating
        echo '<td>';
        echo '<div class="cf-rating-display">';
        for ($i = 1; $i <= 5; $i++) {
            echo $i <= $comment->rating ? '⭐' : '☆';
        }
        echo '<br><small>' . $comment->rating . '/5</small>';
        echo '</div>';
        echo '</td>';
        
        // Comentario
        echo '<td>';
        echo '<div class="cf-comment-content">';
        if (!empty($comment->title)) {
            echo '<strong>' . esc_html(wp_trim_words($comment->title, 6)) . '</strong><br>';
        }
        echo '<small>' . esc_html(wp_trim_words($comment->content, 15)) . '</small>';
        echo '</div>';
        echo '</td>';
        
        // Respuesta Admin
        echo '<td>';
        if (!empty($comment->admin_response)) {
            echo '<div class="cf-admin-response">';
            echo '✅ <small>' . esc_html(wp_trim_words($comment->admin_response, 10)) . '</small>';
            echo '</div>';
        } else {
            echo '<span class="cf-no-response">❌ Sin respuesta</span>';
        }
        echo '</td>';
        
        // Estado
        echo '<td>';
        echo '<span class="cf-status-badge ' . $status_class . '">' . $status_text . '</span>';
        echo '</td>';
        
        // Fecha
        echo '<td>';
        echo '<small>' . date('d/m/Y H:i', strtotime($comment->created_at)) . '</small>';
        echo '</td>';
        
        // Acciones
        echo '<td>';
        echo '<div class="cf-admin-actions">';
        
        // Editar
        echo '<button class="button cf-admin-edit-comment" data-comment-id="' . $comment->id . '" title="Editar">✏️</button> ';
        
        // Responder
        echo '<button class="button cf-admin-reply-comment" data-comment-id="' . $comment->id . '" style="background: #28a745; color: white;" title="Responder">💬</button> ';
        
        // Eliminar
        echo '<button class="button cf-admin-delete-comment" data-comment-id="' . $comment->id . '" style="color: #dc3545;" title="Eliminar">🗑️</button>';
        
        echo '</div>';
        echo '</td>';
        
        echo '</tr>';
    }
    
    /**
     * Mostrar filtros de administrador
     */
    private function display_admin_filters() {
        // Obtener listas para los filtros
        global $wpdb;
        $table_name = $wpdb->prefix . 'comentarios_free';
        
        // Obtener productos únicos con comentarios (con caché de 10 minutos)
        $cache_key_products = 'cf_admin_products';
        $products = wp_cache_get($cache_key_products);
        
        if (false === $products) {
            $products = $wpdb->get_results("
                SELECT DISTINCT p.ID, p.post_title, COUNT(c.id) as comment_count
                FROM {$wpdb->posts} p
                INNER JOIN $table_name c ON p.ID = c.post_id
                WHERE p.post_status = 'publish'
                GROUP BY p.ID, p.post_title
                ORDER BY p.post_title ASC
            ");
            
            // Guardar en caché por 10 minutos
            wp_cache_set($cache_key_products, $products, '', 600);
        }
        
        echo '<div class="cf-admin-filters-container">';
        echo '<form method="GET" id="cf-admin-filters-form" class="cf-filters-form">';
        
        // Preservar parámetros actuales (incluyendo paginación)
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['filter_product', 'filter_rating', 'filter_response', 'filter_status', 'paged'])) {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        
        echo '<div class="cf-filters-grid">';
        
        // Filtro por producto
        echo '<div class="cf-filter-group">';
        echo '<label for="filter_product">🏷️ <strong>Producto:</strong></label>';
        echo '<select name="filter_product" id="filter_product" class="cf-filter-select">';
        echo '<option value="">📋 Todos los productos</option>';
        foreach ($products as $product) {
            $selected = (isset($_GET['filter_product']) && $_GET['filter_product'] == $product->ID) ? 'selected' : '';
            echo '<option value="' . $product->ID . '" ' . $selected . '>';
            echo esc_html($product->post_title) . ' (' . $product->comment_count . ' comentarios)';
            echo '</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Filtro por calificación
        echo '<div class="cf-filter-group">';
        echo '<label for="filter_rating">⭐ <strong>Calificación:</strong></label>';
        echo '<select name="filter_rating" id="filter_rating" class="cf-filter-select">';
        echo '<option value="">🌟 Todas las calificaciones</option>';
        for ($i = 5; $i >= 1; $i--) {
            $selected = (isset($_GET['filter_rating']) && $_GET['filter_rating'] == $i) ? 'selected' : '';
            $stars = str_repeat('⭐', $i);
            echo '<option value="' . $i . '" ' . $selected . '>' . $stars . ' ' . $i . ' estrellas</option>';
        }
        echo '</select>';
        echo '</div>';
        
        // Filtro por estado
        echo '<div class="cf-filter-group">';
        echo '<label for="filter_status">📊 <strong>Estado:</strong></label>';
        echo '<select name="filter_status" id="filter_status" class="cf-filter-select">';
        echo '<option value="">📋 Todos los estados</option>';
        $status_approved = (isset($_GET['filter_status']) && $_GET['filter_status'] == 'approved') ? 'selected' : '';
        $status_pending = (isset($_GET['filter_status']) && $_GET['filter_status'] == 'pending') ? 'selected' : '';
        echo '<option value="approved" ' . $status_approved . '>✅ Aprobados</option>';
        echo '<option value="pending" ' . $status_pending . '>⏳ Pendientes</option>';
        echo '</select>';
        echo '</div>';
        
        echo '</div>'; // Fin filters-grid
        
        // Botones de acción
        echo '<div class="cf-filter-actions">';
        echo '<button type="submit" class="button button-primary">🔍 Filtrar Comentarios</button>';
        echo '<a href="' . remove_query_arg(['filter_product', 'filter_rating', 'filter_status']) . '" class="button">🧹 Limpiar Filtros</a>';
        echo '</div>';
        
        echo '</form>';
        
        // Mostrar filtros activos
        $this->display_active_filters();
        
        echo '</div>'; // Fin admin-filters-container
    }
    
    /**
     * Mostrar filtros activos
     */
    private function display_active_filters() {
        $active_filters = [];
        
        if (isset($_GET['filter_product']) && !empty($_GET['filter_product'])) {
            $product_title = get_the_title($_GET['filter_product']);
            $active_filters[] = '🏷️ Producto: ' . $product_title;
        }
        
        if (isset($_GET['filter_rating']) && !empty($_GET['filter_rating'])) {
            $stars = str_repeat('⭐', $_GET['filter_rating']);
            $active_filters[] = '⭐ Rating: ' . $stars . ' ' . $_GET['filter_rating'];
        }
        
        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
            $status_text = $_GET['filter_status'] === 'approved' ? '✅ Aprobados' : '⏳ Pendientes';
            $active_filters[] = '📊 Estado: ' . $status_text;
        }
        
        if (!empty($active_filters)) {
            echo '<div class="cf-active-filters">';
            echo '<h4>🔎 Filtros Activos:</h4>';
            echo '<div class="cf-active-filters-list">';
            foreach ($active_filters as $filter) {
                echo '<span class="cf-filter-tag">' . $filter . '</span>';
            }
            echo '</div>';
            echo '</div>';
        }
    }
    
    /**
     * Obtener argumentos de filtro para la consulta
     */
    private function get_filter_args() {
        $args = array('limit' => 0);
        
        if (isset($_GET['filter_product']) && !empty($_GET['filter_product'])) {
            $args['post_id'] = intval($_GET['filter_product']);
        }
        
        if (isset($_GET['filter_rating']) && !empty($_GET['filter_rating'])) {
            $args['rating'] = intval($_GET['filter_rating']);
        }
        
        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
            $args['status'] = sanitize_text_field($_GET['filter_status']);
        }
        
        return $args;
    }
    
    /**
     * Obtener comentarios con paginación completa
     */
    private function get_comments_with_pagination($filter_args) {
        // Configuración de paginación
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Agregar paginación a los argumentos
        $filter_args['limit'] = $per_page;
        $filter_args['offset'] = $offset;
        
        // Obtener comentarios de la página actual
        $comments = $this->database->get_comments($filter_args);
        
        // Obtener total de comentarios usando función optimizada
        $total_comments = $this->database->get_comments_count($filter_args);
        
        // Calcular información de paginación
        $total_pages = ceil($total_comments / $per_page);
        
        return array(
            'comments' => $comments,
            'current_page' => $current_page,
            'per_page' => $per_page,
            'total_comments' => $total_comments,
            'total_pages' => $total_pages,
            'offset' => $offset
        );
    }
    
    /**
     * Mostrar información de paginación
     */
    private function display_pagination_info($data) {
        $start = $data['offset'] + 1;
        $end = min($data['offset'] + $data['per_page'], $data['total_comments']);
        
        echo '<div class="cf-pagination-info">';
        echo '<p>📊 Mostrando <strong>' . $start . '-' . $end . '</strong> de <strong>' . $data['total_comments'] . '</strong> comentarios</p>';
        
        // Selector de elementos por página
        echo '<form method="GET" class="cf-per-page-selector">';
        
        // Preservar todos los parámetros actuales excepto per_page y paged
        foreach ($_GET as $key => $value) {
            if (!in_array($key, ['per_page', 'paged'])) {
                echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '">';
            }
        }
        
        echo '<label for="per_page">Mostrar:</label> ';
        echo '<select name="per_page" id="per_page" onchange="this.form.submit()">';
        
        $per_page_options = array(20, 50, 100, 200);
        foreach ($per_page_options as $option) {
            $selected = ($data['per_page'] == $option) ? 'selected' : '';
            echo '<option value="' . $option . '" ' . $selected . '>' . $option . ' por página</option>';
        }
        echo '</select>';
        echo '</form>';
        echo '</div>';
    }
    
    /**
     * Mostrar controles de paginación
     */
    private function display_pagination_controls($data, $position = 'bottom') {
        if ($data['total_pages'] <= 1) {
            return; // No mostrar paginación si solo hay una página
        }
        
        echo '<div class="cf-pagination cf-pagination-' . $position . '">';
        
        $current_url = $this->build_pagination_url();
        
        // Botón anterior
        if ($data['current_page'] > 1) {
            $prev_url = $this->build_pagination_url($data['current_page'] - 1);
            echo '<a href="' . $prev_url . '" class="cf-pagination-btn cf-pagination-prev">« Anterior</a>';
        }
        
        // Números de página
        $range = 3; // Mostrar 3 páginas a cada lado de la actual
        $start = max(1, $data['current_page'] - $range);
        $end = min($data['total_pages'], $data['current_page'] + $range);
        
        // Primera página si no está en el rango
        if ($start > 1) {
            $first_url = $this->build_pagination_url(1);
            echo '<a href="' . $first_url . '" class="cf-pagination-btn">1</a>';
            if ($start > 2) {
                echo '<span class="cf-pagination-dots">...</span>';
            }
        }
        
        // Páginas en el rango
        for ($i = $start; $i <= $end; $i++) {
            if ($i == $data['current_page']) {
                echo '<span class="cf-pagination-btn cf-pagination-current">' . $i . '</span>';
            } else {
                $page_url = $this->build_pagination_url($i);
                echo '<a href="' . $page_url . '" class="cf-pagination-btn">' . $i . '</a>';
            }
        }
        
        // Última página si no está en el rango
        if ($end < $data['total_pages']) {
            if ($end < $data['total_pages'] - 1) {
                echo '<span class="cf-pagination-dots">...</span>';
            }
            $last_url = $this->build_pagination_url($data['total_pages']);
            echo '<a href="' . $last_url . '" class="cf-pagination-btn">' . $data['total_pages'] . '</a>';
        }
        
        // Botón siguiente
        if ($data['current_page'] < $data['total_pages']) {
            $next_url = $this->build_pagination_url($data['current_page'] + 1);
            echo '<a href="' . $next_url . '" class="cf-pagination-btn cf-pagination-next">Siguiente »</a>';
        }
        
        echo '</div>';
    }
    
    /**
     * Construir URL de paginación manteniendo parámetros actuales
     */
    private function build_pagination_url($page = null) {
        // Obtener URL actual
        $url = $_SERVER['REQUEST_URI'];
        $url_parts = explode('?', $url);
        $base_url = $url_parts[0];
        
        // Obtener parámetros actuales
        $params = $_GET;
        
        if ($page !== null) {
            if ($page > 1) {
                $params['paged'] = $page;
            } else {
                unset($params['paged']); // Remover paged=1
            }
        } else {
            unset($params['paged']); // Remover paginación completamente
        }
        
        // Construir query string
        if (!empty($params)) {
            return $base_url . '?' . http_build_query($params);
        }
        
        return $base_url;
    }

    /**
     * Modal de edición para administradores
     */
    private function render_admin_edit_modal() {
        echo '<div id="cf-admin-edit-modal" class="cf-modal" style="display:none;">';
        echo '<div class="cf-modal-overlay"></div>';
        echo '<div class="cf-modal-content cf-compact">';
        
        echo '<div class="cf-modal-header">';
        echo '<h2>✏️ Editar Comentario <span class="cf-admin-badge">ADMIN</span></h2>';
        echo '<span class="cf-modal-close">&times;</span>';
        echo '</div>';
        
        echo '<div class="cf-modal-body">';
        echo '<form id="cf-admin-edit-form">';
        echo '<input type="hidden" id="edit_comment_id" name="comment_id" value="">';
        
        // Información del usuario compacta
        echo '<div class="cf-user-info-compact">';
        echo '👤 <strong><span id="edit_user_name">Usuario</span></strong> ';
        echo '(<span id="edit_user_email">email@ejemplo.com</span>)';
        echo ' - País: <span id="edit_user_country">País</span>';
        echo '</div>';
        
        // Campos en grid compacto
        echo '<div class="cf-form-grid">';
        
        // Fila 1: Rating y Travel Companion
        echo '<div class="cf-form-row">';
        echo '<div class="cf-field-half">';
        echo '<label for="edit_rating">⭐ Calificación:</label>';
        echo '<select name="rating" id="edit_rating" required>';
        echo '<option value="1">⭐ 1 estrella</option>';
        echo '<option value="2">⭐⭐ 2 estrellas</option>';
        echo '<option value="3">⭐⭐⭐ 3 estrellas</option>';
        echo '<option value="4">⭐⭐⭐⭐ 4 estrellas</option>';
        echo '<option value="5">⭐⭐⭐⭐⭐ 5 estrellas</option>';
        echo '</select>';
        echo '</div>';
        echo '<div class="cf-field-half">';
        echo '<label for="edit_travel_companion">👥 ¿Con quién viajaste?:</label>';
        echo '<select name="travel_companion" id="edit_travel_companion" required>';
        echo '<option value="">Selecciona</option>';
        echo '<option value="solo">🚶 Solo</option>';
        echo '<option value="en_pareja">💑 En pareja</option>';
        echo '<option value="en_familia">👨‍👩‍👧‍👦 En familia</option>';
        echo '<option value="con_amigos">👥 Con amigos</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        // Fila 2: País y Idioma
        echo '<div class="cf-form-row">';
        echo '<div class="cf-field-half">';
        echo '<label for="edit_country">🌍 País:</label>';
        echo '<div class="cf-country-autocomplete-container">';
        echo '<input type="text" id="edit_country" name="country" class="cf-country-input cf-input-compact" placeholder="Elegir país (opcional)" autocomplete="off">';
        echo '<div class="cf-country-dropdown" id="edit_country-dropdown" style="display: none;"></div>';
        echo '</div>';
        echo '</div>';
        echo '<div class="cf-field-half">';
        echo '<label for="edit_language">🗣️ Idioma:</label>';
        echo '<select name="language" id="edit_language" required>';
        echo '<option value="">Selecciona idioma</option>';
        echo '<option value="es">🇪🇸 Español</option>';
        echo '<option value="en">🇺🇸 English</option>';
        echo '<option value="de">🇩🇪 Deutsch</option>';
        echo '<option value="fr">🇫🇷 Français</option>';
        echo '<option value="pt">🇧🇷 Português</option>';
        echo '<option value="it">🇮🇹 Italiano</option>';
        echo '<option value="nl">🇳🇱 Nederlands</option>';
        echo '<option value="zh">🇨🇳 中文</option>';
        echo '<option value="ja">🇯🇵 日本語</option>';
        echo '<option value="ko">🇰🇷 한국어</option>';
        echo '<option value="he">🇮🇱 עברית</option>';
        echo '<option value="otros">🌍 Otros</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        // Fila 3: Título (full width)
        echo '<div class="cf-form-row">';
        echo '<div class="cf-field-full">';
        echo '<label for="edit_title">📝 Título del comentario:</label>';
        echo '<input type="text" name="title" id="edit_title" required maxlength="255" placeholder="Ej. ¡Una experiencia increíble!">';
        echo '</div>';
        echo '</div>';
        
        // Fila 4: Comentario (full width)
        echo '<div class="cf-form-row">';
        echo '<div class="cf-field-full">';
        echo '<label for="edit_content">💬 Comentario:</label>';
        echo '<textarea name="content" id="edit_content" rows="4" required placeholder="Comparte tu experiencia detallada..."></textarea>';
        echo '</div>';
        echo '</div>';
        
        // Fila 5: Gestión de fotos
        echo '<div class="cf-form-row cf-photos-section">';
        echo '<div class="cf-field-full">';
        echo '<label>📸 Fotos:</label>';
        echo '<div id="edit_current_images" class="cf-current-images">';
        echo '<p class="cf-no-images">📷 No hay imágenes en este comentario</p>';
        echo '</div>';
        echo '<div class="cf-photo-controls">';
        echo '<input type="file" name="new_images[]" id="edit_new_images" accept="image/*" multiple class="cf-file-input">';
        echo '<label>';
        echo '<input type="checkbox" name="remove_all_images" id="edit_remove_all_images" value="1">';
        echo '🗑️ Eliminar todas';
        echo '</label>';
        echo '</div>';
        echo '<div class="cf-file-help">Máximo 5 fotos (JPG, PNG, GIF, WebP - 2MB c/u)</div>';
        echo '</div>';
        echo '</div>';
        
        echo '</div>'; // Fin cf-form-grid
        
        // Botones mejorados
        echo '<div class="cf-modal-footer">';
        echo '<button type="button" class="cf-btn cf-btn-cancel cf-modal-close">';
        echo '<span class="cf-btn-icon">✕</span>';
        echo '<span class="cf-btn-text">Cancelar</span>';
        echo '</button>';
        echo '<button type="submit" class="cf-btn cf-btn-primary">';
        echo '<span class="cf-btn-icon">💾</span>';
        echo '<span class="cf-btn-text">Guardar Cambios</span>';
        echo '</button>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}