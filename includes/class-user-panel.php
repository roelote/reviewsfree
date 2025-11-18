<?php
/**
 * Clase User Panel para el plugin Comentarios Free
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_User_Panel {
    
    private $database;
    
    public function __construct() {
        $this->database = new ComentariosFree_Database();
    }
    
    /**
     * Mostrar panel de usuario
     */
    public function display_user_panel() {
        if (!is_user_logged_in()) {
            return '<div class="comentarios-login-required">' . 
                   '<p>' . __('Debes iniciar sesión para ver tus comentarios.', 'comentarios-free') . '</p>' .
                   '<a href="' . wp_login_url(get_permalink()) . '" class="comentarios-btn comentarios-btn-primary">' . 
                   __('Iniciar Sesión', 'comentarios-free') . '</a>' .
                   '</div>';
        }
        
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        
        // Obtener comentarios del usuario
        $comments = $this->database->get_comments(array(
            'user_id' => $user_id,
            'limit' => 0 // Todos los comentarios
        ));
        
        // Contar estadísticas
        $total_comments = count($comments);
        $approved_comments = count(array_filter($comments, function($comment) {
            return $comment->status === 'approved';
        }));
        $pending_comments = count(array_filter($comments, function($comment) {
            return $comment->status === 'pending';
        }));
        
        ob_start();
        
        echo '<div class="comentarios-user-panel">';
        
        // Header del panel
        echo '<div class="user-panel-header">';
        echo '<h2>' . sprintf(__('Panel de %s', 'comentarios-free'), esc_html($current_user->display_name)) . '</h2>';
        echo '<div class="user-stats">';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $total_comments . '</span>';
        echo '<span class="stat-label">' . __('Total comentarios', 'comentarios-free') . '</span>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $approved_comments . '</span>';
        echo '<span class="stat-label">' . __('Aprobados', 'comentarios-free') . '</span>';
        echo '</div>';
        echo '<div class="stat-item">';
        echo '<span class="stat-number">' . $pending_comments . '</span>';
        echo '<span class="stat-label">' . __('Pendientes', 'comentarios-free') . '</span>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
        
        // Filtros
        echo '<div class="user-panel-filters">';
        echo '<div class="filter-group">';
        echo '<label for="status-filter">' . __('Filtrar por estado:', 'comentarios-free') . '</label>';
        echo '<select id="user-status-filter" class="comentarios-select">';
        echo '<option value="">' . __('Todos los estados', 'comentarios-free') . '</option>';
        echo '<option value="approved">' . __('Aprobados', 'comentarios-free') . '</option>';
        echo '<option value="pending">' . __('Pendientes', 'comentarios-free') . '</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        // Lista de comentarios
        echo '<div class="user-comments-list" id="user-comments-list">';
        
        if (empty($comments)) {
            echo '<div class="no-comments">';
            echo '<p>' . __('Aún no tienes comentarios. ¡Comienza dejando reseñas en nuestros contenidos!', 'comentarios-free') . '</p>';
            echo '</div>';
        } else {
            foreach ($comments as $comment) {
                $this->display_user_comment($comment);
            }
        }
        
        echo '</div>';
        
        echo '</div>';
        
        // Modal para editar comentario - siempre mostrarlo en el panel de usuario
        $this->display_edit_modal();
        
        return ob_get_clean();
    }
    
    /**
     * Mostrar comentario individual en el panel de usuario
     */
    private function display_user_comment($comment) {
        $post = get_post($comment->post_id);
        $post_title = $post ? $post->post_title : __('Contenido eliminado', 'comentarios-free');
        $post_url = $post ? get_permalink($post->ID) : '#';
        
        $status_class = 'status-' . $comment->status;
        $status_text = $comment->status === 'approved' ? __('Aprobado', 'comentarios-free') : __('Pendiente', 'comentarios-free');
        
        $images = $this->database->get_comment_images($comment->id);
        
        echo '<div class="user-comment-item ' . $status_class . '" data-comment-id="' . $comment->id . '" data-status="' . $comment->status . '">';
        
        // Header del comentario
        echo '<div class="comment-header">';
        echo '<div class="comment-info">';
        echo '<h4 class="comment-post-title">';
        echo '<a href="' . esc_url($post_url) . '" target="_blank">' . esc_html($post_title) . '</a>';
        echo '</h4>';
        echo '<div class="comment-meta">';
        echo '<span class="comment-rating">';
        $this->display_stars($comment->rating);
        echo '</span>';
        echo '<span class="comment-date">' . date_i18n(get_option('date_format'), strtotime($comment->created_at)) . '</span>';
        echo '<span class="comment-status ' . $status_class . '">' . $status_text . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="comment-actions">';
        if ($comment->status === 'approved') {
            echo '<button class="btn-edit-comment comentarios-btn comentarios-btn-sm" data-comment-id="' . $comment->id . '">';
            echo __('Editar', 'comentarios-free');
            echo '</button>';
        }
        echo '<button class="btn-delete-comment comentarios-btn comentarios-btn-sm comentarios-btn-danger" data-comment-id="' . $comment->id . '">';
        echo __('Eliminar', 'comentarios-free');
        echo '</button>';
        echo '</div>';
        echo '</div>';
        
        // Título y contenido del comentario
        echo '<div class="comment-body">';
        echo '<h5 class="comment-title">' . esc_html($comment->title) . '</h5>';
        echo '<div class="comment-content">' . wpautop(esc_html($comment->content)) . '</div>';
        echo '</div>';
        
        // Imágenes del comentario
        if (!empty($images)) {
            echo '<div class="comment-images">';
            echo '<h6>' . __('Imágenes adjuntas:', 'comentarios-free') . '</h6>';
            echo '<div class="images-grid">';
            foreach ($images as $image) {
                echo '<div class="comment-image">';
                echo '<img src="' . esc_url($image->file_url) . '" alt="' . esc_attr($image->original_name) . '" loading="lazy">';
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    /**
     * Mostrar estrellas de rating
     */
    private function display_stars($rating) {
        $rating = floatval($rating);
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                echo '<span class="star filled">⭐</span>';
            } else {
                echo '<span class="star empty">☆</span>';
            }
        }
    }
    
    /**
     * Mostrar modal de edición
     */
    private function display_edit_modal() {
        echo '<div id="edit-comment-modal" class="comentarios-modal" style="display: none;">';
        echo '<div class="modal-content">';
        echo '<div class="modal-header">';
        echo '<h3>' . __('Editar Comentario', 'comentarios-free') . '</h3>';
        echo '<button type="button" class="modal-close">&times;</button>';
        echo '</div>';
        
        echo '<div class="modal-body">';
        echo '<form id="edit-comment-form" class="comentarios-form" enctype="multipart/form-data">';
        wp_nonce_field('comentarios_free_nonce', 'comentarios_nonce');
        echo '<input type="hidden" id="edit-comment-id" name="comment_id" value="">';
        
        echo '<div class="form-group">';
        echo '<label for="edit-rating">' . __('Calificación *', 'comentarios-free') . '</label>';
        echo '<div class="rating-input" id="edit-rating-input">';
        for ($i = 1; $i <= 5; $i++) {
            echo '<span class="star" data-rating="' . $i . '">⭐</span>';
        }
        echo '<input type="hidden" id="edit-rating" name="rating" value="0" required>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="edit-title">' . __('Título del comentario *', 'comentarios-free') . '</label>';
        echo '<input type="text" id="edit-title" name="title" required maxlength="255">';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="edit-country">' . __('País (opcional)', 'comentarios-free') . '</label>';
        echo '<div class="cf-country-autocomplete-container">';
        echo '<input type="text" id="edit-country" class="cf-country-input" placeholder="' . __('Elegir país (opcional)', 'comentarios-free') . '" autocomplete="off">';
        echo '<div class="cf-country-dropdown" id="edit-country-dropdown" style="display: none;"></div>';
        echo '</div>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="edit-language">' . __('Idioma *', 'comentarios-free') . '</label>';
        echo '<select id="edit-language" name="language" required>';
        echo '<option value="es">Español</option>';
        echo '<option value="en">English</option>';
        echo '<option value="fr">Français</option>';
        echo '<option value="de">Deutsch</option>';
        echo '<option value="it">Italiano</option>';
        echo '<option value="pt">Português</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="edit-travel-companion">' . __('¿Con quién viajaste? *', 'comentarios-free') . '</label>';
        echo '<select id="edit-travel-companion" name="travel_companion" required>';
        echo '<option value="">' . __('Selecciona una opción', 'comentarios-free') . '</option>';
        echo '<option value="solo">' . __('Solo', 'comentarios-free') . '</option>';
        echo '<option value="en_pareja">' . __('En pareja', 'comentarios-free') . '</option>';
        echo '<option value="en_familia">' . __('En familia', 'comentarios-free') . '</option>';
        echo '<option value="con_amigos">' . __('Con amigos', 'comentarios-free') . '</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="form-group">';
        echo '<label for="edit-content">' . __('Tu comentario *', 'comentarios-free') . '</label>';
        echo '<textarea id="edit-content" name="content" rows="5" required></textarea>';
        echo '</div>';
        
        echo '<div class="form-group cf-photos-section">';
        echo '<label>📸 ' . __('Fotos', 'comentarios-free') . '</label>';
        
        // Fotos actuales
        echo '<div id="current-images-container" class="cf-current-images">';
        echo '<p class="cf-no-images">📷 ' . __('No hay imágenes en este comentario', 'comentarios-free') . '</p>';
        echo '</div>';
        
        // Controles para agregar nuevas fotos
        echo '<div class="cf-photo-controls">';
        echo '<input type="file" id="edit-images" name="images[]" multiple accept="image/*" class="cf-file-input">';
        echo '</div>';
        
        echo '<small class="cf-file-help">' . __('Máximo 5 fotos (JPG, PNG, GIF, WebP - 2MB c/u). Clic en × para eliminar.', 'comentarios-free') . '</small>';
        echo '</div>';
        
        echo '</form>';
        echo '</div>';
        
        echo '<div class="modal-footer">';
        echo '<button type="button" class="comentarios-btn comentarios-btn-secondary modal-close">' . __('Cancelar', 'comentarios-free') . '</button>';
        echo '<button type="button" id="save-comment-edit" class="comentarios-btn comentarios-btn-primary">' . __('Guardar Cambios', 'comentarios-free') . '</button>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
}