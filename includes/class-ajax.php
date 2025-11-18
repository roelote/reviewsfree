<?php
/**
 * Clase AJAX para el plugin Comentarios Free
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Ajax {
    
    private $database;
    
    /**
     * Obtener ID del post original (para compatibilidad WPML)
     * Si es una traducción, devuelve el ID del post en el idioma original
     */
    private function get_original_post_id($post_id) {
        // Verificar si WPML está activo
        if (function_exists('icl_object_id')) {
            // Obtener el idioma por defecto
            global $sitepress;
            if ($sitepress) {
                $default_lang = $sitepress->get_default_language();
                // Obtener el ID del post en el idioma original
                $original_id = icl_object_id($post_id, 'post', false, $default_lang);
                return $original_id ? $original_id : $post_id;
            }
        }
        return $post_id;
    }
    
    public function __construct() {
        $this->database = new ComentariosFree_Database();
        
        // Hooks para usuarios logueados
        add_action('wp_ajax_comentarios_submit', array($this, 'submit_comment'));
        add_action('wp_ajax_comentarios_load_more', array($this, 'load_more_comments'));
        add_action('wp_ajax_comentarios_filter', array($this, 'filter_comments'));
        add_action('wp_ajax_comentarios_delete', array($this, 'delete_comment'));
        add_action('wp_ajax_comentarios_edit', array($this, 'edit_comment'));
        add_action('wp_ajax_comentarios_get_comment', array($this, 'get_comment_for_edit'));
        add_action('wp_ajax_comentarios_admin_reply', array($this, 'admin_reply_comment'));
        add_action('wp_ajax_comentarios_admin_edit', array($this, 'admin_edit_comment'));
        add_action('wp_ajax_comentarios_admin_get_comment', array($this, 'admin_get_comment_for_edit'));
        add_action('wp_ajax_comentarios_get_images', array($this, 'get_comment_images'));
        add_action('wp_ajax_comentarios_delete_image', array($this, 'delete_comment_image'));
        
        // Nuevos endpoints para filtros
        add_action('wp_ajax_cf_filter_comments', array($this, 'filter_comments_new'));
        add_action('wp_ajax_nopriv_cf_filter_comments', array($this, 'filter_comments_new'));
        
        // Endpoint para verificar estado de login (para el flujo de dos pasos)
        add_action('wp_ajax_check_user_login_status', array($this, 'check_user_login_status'));
        add_action('wp_ajax_nopriv_check_user_login_status', array($this, 'check_user_login_status'));
        
        // Nuevos endpoints para login y registro personalizado
        add_action('wp_ajax_cf_user_login', array($this, 'user_login'));
        add_action('wp_ajax_nopriv_cf_user_login', array($this, 'user_login'));
        add_action('wp_ajax_cf_user_register', array($this, 'user_register'));
        add_action('wp_ajax_nopriv_cf_user_register', array($this, 'user_register'));
        
        // Hooks para usuarios no logueados (solo lectura - no pueden comentar)
        add_action('wp_ajax_nopriv_comentarios_load_more', array($this, 'load_more_comments'));
        add_action('wp_ajax_nopriv_comentarios_filter', array($this, 'filter_comments'));
    }
    
    /**
     * Enviar comentario con subida de imágenes
     */
    public function submit_comment() {
        try {
            // Logging para debug
            error_log('🚀 ComentariosFree: Iniciando submit_comment');
            error_log('POST data: ' . print_r($_POST, true));
            
            // REQUERIR que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => cf_trans('error_login_required')
                ));
                return;
            }
            
            // Obtener datos del usuario actual
            $user_id = get_current_user_id();
            $current_user = wp_get_current_user();
            
            // Sanitizar y validar datos
            $post_id = intval($_POST['post_id']);
            // Obtener ID del post original (para WPML)
            $post_id = $this->get_original_post_id($post_id);
            
            // Si el usuario está logueado, usar sus datos automáticamente
            if (is_user_logged_in()) {
                $author_name = !empty($_POST['author_name']) ? sanitize_text_field($_POST['author_name']) : $current_user->display_name;
                $author_email = $current_user->user_email; // Siempre usar el email del usuario logueado
                error_log('Usuario logueado: ' . $author_name . ' (' . $author_email . ')');
            } else {
                // Usuario no logueado - usar datos del formulario
                $author_name = sanitize_text_field($_POST['author_name']);
                $author_email = sanitize_email($_POST['author_email']);
                $user_id = 0;
                error_log('Usuario no logueado - usando datos del formulario');
            }
        $country = sanitize_text_field($_POST['country']);
        $language = sanitize_text_field($_POST['language']);
        $travel_companion = sanitize_text_field($_POST['travel_companion']);
        $rating = intval($_POST['rating']);
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);
        
        // Validaciones
        $errors = array();
        
        if (!$post_id || !get_post($post_id)) {
            $errors[] = cf_trans('error_invalid_post');
        }
        
        if (empty($author_name)) {
            $errors[] = cf_trans('error_name_required');
        }
        
        if (empty($author_email) || !is_email($author_email)) {
            $errors[] = cf_trans('error_email_invalid');
        }
        
        // País es opcional - no validar si está vacío
        
        if (empty($language)) {
            $errors[] = cf_trans('error_language_required');
        }
        
        if (empty($travel_companion)) {
            $errors[] = cf_trans('error_travel_companion_required');
        }
        
        if ($rating < 1 || $rating > 5) {
            $errors[] = cf_trans('error_rating_invalid');
        }
        
        if (empty($title)) {
            $errors[] = cf_trans('error_title_required');
        }
        
        if (empty($content)) {
            $errors[] = cf_trans('error_content_required');
        }
        
        // Verificar si el usuario ya comentó en este post - PREVENCIÓN DE DUPLICADOS
        global $wpdb;
        $table_name = $wpdb->prefix . 'comentarios_free';
        
        $existing_comment = null;
        
        // Buscar comentario existente con múltiples criterios
        if ($user_id > 0) {
            // Usuario logueado - verificar por user_id Y por email (por si cambió email)
            $existing_comment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_id = %d AND (user_id = %d OR author_email = %s) LIMIT 1",
                $post_id, $user_id, $author_email
            ));
        } else {
            // Usuario no logueado - verificar solo por email
            $existing_comment = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE post_id = %d AND author_email = %s LIMIT 1",
                $post_id, $author_email
            ));
        }
        
        if ($existing_comment) {
            $post_title = get_the_title($post_id);
            $current_url = get_permalink($post_id);
            wp_send_json_error(array(
                'message' => cf_trans('error_already_reviewed'),
                'duplicate_detected' => true,
                'existing_comment_id' => $existing_comment,
                'redirect_to' => $current_url
            ));
            return;
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode('<br>', $errors)
            ));
            return;
        }
        
        // Preparar datos del comentario
        $comment_data = array(
            'post_id' => $post_id,
            'user_id' => $user_id,
            'author_name' => $author_name,
            'author_email' => $author_email,
            'country' => $country,
            'language' => $language,
            'travel_companion' => $travel_companion,
            'rating' => $rating,
            'title' => $title,
            'content' => $content,
            'status' => 'approved'
        );
        
        // Insertar comentario
        $comment_id = $this->database->insert_comment($comment_data);
        
        if (!$comment_id) {
            wp_send_json_error(array(
                'message' => cf_trans('error_processing_request')
            ));
            return;
        }
        
        // Manejar imágenes si se subieron
        $uploaded_images = array();
        if (!empty($_FILES['images']['name'][0])) {
            $uploaded_images = $this->handle_image_uploads($comment_id);
        }
        
        // Respuesta de éxito
        wp_send_json_success(array(
            'message' => cf_trans('success_comment_submitted'),
            'comment_id' => $comment_id,
            'images' => $uploaded_images
        ));
        
        } catch (Exception $e) {
            error_log('❌ Error en submit_comment: ' . $e->getMessage());
            wp_send_json_error('Error interno del servidor. Por favor intenta nuevamente.');
        }
    }
    
    /**
     * Cargar más comentarios
     */
    public function load_more_comments() {
        $post_id = intval($_POST['post_id']);
        // Obtener ID del post original (para WPML)
        $post_id = $this->get_original_post_id($post_id);
        $offset = intval($_POST['offset']);
        $rating_filter = isset($_POST['rating_filter']) ? intval($_POST['rating_filter']) : '';
        $language_filter = isset($_POST['language_filter']) ? sanitize_text_field($_POST['language_filter']) : '';
        
        $args = array(
            'post_id' => $post_id,
            'status' => 'approved',
            'limit' => 10,
            'offset' => $offset
        );
        
        if (!empty($rating_filter)) {
            $args['rating'] = $rating_filter;
        }
        
        if (!empty($language_filter)) {
            $args['language'] = $language_filter;
        }
        
        $comments = $this->database->get_comments($args);
        
        // Usar reflection para acceder al método privado render_comments
        $frontend = new ComentariosFree_Frontend_TwoStep($this->database);
        $reflection = new ReflectionClass($frontend);
        $method = $reflection->getMethod('render_comments');
        $method->setAccessible(true);
        $html = $method->invoke($frontend, $comments);
        
        wp_send_json_success(array(
            'html' => $html,
            'loaded' => count($comments),
            'has_more' => count($comments) >= 10
        ));
    }
    
    /**
     * Filtrar comentarios
     */
    public function filter_comments() {
        $post_id = intval($_POST['post_id']);
        // Obtener ID del post original (para WPML)
        $post_id = $this->get_original_post_id($post_id);
        $rating_filter = isset($_POST['rating_filter']) ? intval($_POST['rating_filter']) : '';
        $language_filter = isset($_POST['language_filter']) ? sanitize_text_field($_POST['language_filter']) : '';
        
        $args = array(
            'post_id' => $post_id,
            'status' => 'approved',
            'limit' => 10
        );
        
        if (!empty($rating_filter)) {
            $args['rating'] = $rating_filter;
        }
        
        if (!empty($language_filter)) {
            $args['language'] = $language_filter;
        }
        
        $comments = $this->database->get_comments($args);
        
        ob_start();
        $frontend = new ComentariosFree_Frontend();
        
        if (empty($comments)) {
            echo '<div class="no-comments">';
            echo '<p>' . __('No se encontraron comentarios con los filtros seleccionados.', 'comentarios-free') . '</p>';
            echo '</div>';
        } else {
            foreach ($comments as $comment) {
                // Usar reflection para acceder al método privado
                $reflection = new ReflectionClass($frontend);
                $method = $reflection->getMethod('display_single_comment');
                $method->setAccessible(true);
                $method->invoke($frontend, $comment);
            }
        }
        
        $html = ob_get_clean();
        
        // Contar total de comentarios con filtros
        $total_filtered = $this->database->count_comments($args);
        
        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $total_filtered > 10,
            'total' => $total_filtered
        ));
    }
    
    /**
     * Eliminar comentario
     */
    public function delete_comment() {
        try {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Debes iniciar sesión'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $comment = $this->database->get_comment($comment_id);
            
            if (!$comment) {
                wp_send_json_error(array(
                    'message' => 'Comentario no encontrado'
                ));
                return;
            }
            
            // Verificar permisos: propietario del comentario o administrador
            $current_user_id = get_current_user_id();
            $is_admin = current_user_can('manage_options');
            $is_owner = ($comment->user_id == $current_user_id);
            
            // Log para debugging
            error_log("DELETE COMMENT DEBUG - User ID: $current_user_id, Comment User ID: {$comment->user_id}, Is Admin: " . ($is_admin ? 'YES' : 'NO') . ", Is Owner: " . ($is_owner ? 'YES' : 'NO'));
            
            if (!$is_owner && !$is_admin) {
                wp_send_json_error(array(
                    'message' => 'No tienes permisos para eliminar este comentario'
                ));
                return;
            }
            
            if ($this->database->delete_comment($comment_id)) {
                wp_send_json_success(array(
                    'message' => 'Comentario eliminado correctamente'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Error al eliminar el comentario'
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error interno del servidor'
            ));
        }
    }
    
    /**
     * Obtener comentario para edición o respuesta
     */
    public function get_comment_for_edit() {
        try {
            // Asegurar que todas las columnas necesarias existan
            $this->database->ensure_edit_count_column();
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Debes iniciar sesión'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $comment = $this->database->get_comment($comment_id);
            
            if (!$comment) {
                wp_send_json_error(array(
                    'message' => 'Comentario no encontrado'
                ));
                return;
            }
            
            // Verificar permisos: propietario del comentario (para editar) o administrador (para responder)
            if ($comment->user_id != get_current_user_id() && !current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => 'No tienes permisos para acceder a este comentario'
                ));
                return;
            }
            
            // Verificar límite de edición (solo para propietarios que quieren editar, no para administradores que responden)
            $edit_count = isset($comment->edit_count) ? intval($comment->edit_count) : 0;
            $is_owner = ($comment->user_id == get_current_user_id());
            $is_admin = current_user_can('manage_options');
            
            if ($is_owner && !$is_admin && $edit_count >= 1) {
                wp_send_json_error(array(
                    'message' => '⚠️ Este comentario ya fue editado. Solo se permite una edición por comentario.',
                    'edit_limit_reached' => true
                ));
                return;
            }
            
            // Asegurar que el comentario tenga todos los campos necesarios con valores por defecto
            if (!isset($comment->country) || empty($comment->country)) {
                $comment->country = '';
            }
            if (!isset($comment->language) || empty($comment->language)) {
                $comment->language = 'es';
            }
            if (!isset($comment->travel_companion) || empty($comment->travel_companion)) {
                $comment->travel_companion = 'solo';
            }
            
            // Obtener imágenes del comentario
            $images = $this->database->get_comment_images($comment_id);
            error_log('GET COMMENT DEBUG - Comment ID: ' . $comment_id);
            error_log('GET COMMENT DEBUG - Imágenes encontradas: ' . count($images));
            error_log('GET COMMENT DEBUG - Imágenes data: ' . json_encode($images));
            
            wp_send_json_success(array(
                'comment' => $comment,
                'images' => $images ? $images : array(),
                'can_edit' => true
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error interno del servidor'
            ));
        }
    }
    
    /**
     * Editar comentario
     */
    public function edit_comment() {
        try {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['comentarios_nonce'], 'comentarios_free_nonce')) {
                error_log('EDIT DEBUG: Error de nonce');
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Debes iniciar sesión'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $comment = $this->database->get_comment($comment_id);
            
            if (!$comment) {
                wp_send_json_error(array(
                    'message' => cf_trans('error_comment_not_found')
                ));
                return;
            }
            
            // Verificar que el usuario sea el propietario del comentario
            if ($comment->user_id != get_current_user_id()) {
                wp_send_json_error(array(
                    'message' => cf_trans('error_no_permission')
                ));
                return;
            }
        
        // Sanitizar y validar datos PRIMERO
        $rating = intval($_POST['rating']);
        $title = sanitize_text_field($_POST['title']);
        $content = sanitize_textarea_field($_POST['content']);
        
        // Nuevos campos agregados
        $country = isset($_POST['country']) ? sanitize_text_field($_POST['country']) : '';
        $language = sanitize_text_field($_POST['language']);
        $travel_companion = sanitize_text_field($_POST['travel_companion']);
        
        // VERIFICAR LÍMITE DE EDICIONES
        // Permitir ediciones si SOLO está modificando imágenes (agregando/eliminando fotos)
        // Solo bloquear si está modificando el contenido textual del comentario
        $edit_count = isset($comment->edit_count) ? intval($comment->edit_count) : 0;
        
        // Verificar si está modificando contenido textual (comparar valores sanitizados)
        $is_modifying_content = (
            trim($title) != trim($comment->title) ||
            trim($content) != trim($comment->content) ||
            $rating != intval($comment->rating)
        );
        
        error_log('EDIT DEBUG - Comparación de contenido:');
        error_log('  Title: "' . trim($title) . '" vs "' . trim($comment->title) . '" = ' . (trim($title) != trim($comment->title) ? 'DIFERENTE' : 'IGUAL'));
        error_log('  Content: "' . substr(trim($content), 0, 50) . '..." vs "' . substr(trim($comment->content), 0, 50) . '..." = ' . (trim($content) != trim($comment->content) ? 'DIFERENTE' : 'IGUAL'));
        error_log('  Rating: ' . $rating . ' vs ' . intval($comment->rating) . ' = ' . ($rating != intval($comment->rating) ? 'DIFERENTE' : 'IGUAL'));
        error_log('  ¿Modifica contenido? ' . ($is_modifying_content ? 'SÍ' : 'NO'));
        
        // Solo bloquear si ya editó el contenido y está intentando editarlo de nuevo
        if ($edit_count >= 1 && $is_modifying_content) {
            wp_send_json_error(array(
                'message' => '⚠️ Ya has editado el contenido de este comentario. Solo se permite una edición de contenido, pero puedes seguir agregando o eliminando fotos.',
                'edit_limit_reached' => true
            ));
            return;
        }
        
        // Debug del país
        error_log('EDIT USER DEBUG - País recibido: "' . $country . '" (vacío: ' . (empty($country) ? 'SI' : 'NO') . ')');
        
        // Validaciones
        $errors = array();
        
        if ($rating < 1 || $rating > 5) {
            $errors[] = 'La calificación debe ser entre 1 y 5';
        }
        
        if (empty($title)) {
            $errors[] = 'El título es obligatorio';
        }
        
        if (empty($content)) {
            $errors[] = 'El contenido es obligatorio';
        }
        
        // País es opcional - no validar
        
        if (empty($language)) {
            $errors[] = 'El idioma es obligatorio';
        }
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => implode(', ', $errors)
            ));
            return;
        }
        
        // Verificar que todas las columnas necesarias existen
        $this->database->ensure_edit_count_column();
        
        // Solo incrementar edit_count si está modificando contenido textual
        $new_edit_count = $is_modifying_content ? $edit_count + 1 : $edit_count;
        
        // Actualizar comentario
        $update_data = array(
            'rating' => $rating,
            'title' => $title,
            'content' => $content,
            'country' => $country,
            'language' => $language,
            'travel_companion' => $travel_companion,
            'edit_count' => $new_edit_count
        );
        
        error_log('EDIT USER DEBUG - ¿Modifica contenido? ' . ($is_modifying_content ? 'SÍ' : 'NO (solo fotos)'));
        error_log('EDIT USER DEBUG - Edit count: ' . $edit_count . ' -> ' . $new_edit_count);
        
        // Debug detallado para identificar problemas
        error_log('EDIT USER DEBUG - Datos recibidos: ' . print_r($_POST, true));
        error_log('EDIT USER DEBUG - Update data: ' . print_r($update_data, true));
        error_log('EDIT USER DEBUG - Comment ID: ' . $comment_id);
        error_log('EDIT USER DEBUG - User ID: ' . get_current_user_id());
        
        // Verificar que el comentario existe antes de actualizar
        if (!$comment) {
            error_log('EDIT USER ERROR - Comentario no encontrado para ID: ' . $comment_id);
            wp_send_json_error(array(
                'message' => 'Comentario no encontrado para editar'
            ));
            return;
        }
        
        // Intentar la actualización
        try {
            $result = $this->database->update_comment($comment_id, $update_data);
            error_log('EDIT USER DEBUG - Update result: ' . ($result !== false ? 'SUCCESS (' . $result . ' rows)' : 'FAILED'));
            
            if ($result !== false) {
                // Procesar eliminación de imágenes marcadas
                if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
                    $images_to_delete = json_decode(stripslashes($_POST['delete_images']), true);
                    error_log('EDIT USER DEBUG - Eliminando imágenes: ' . print_r($images_to_delete, true));
                    
                    if (is_array($images_to_delete) && count($images_to_delete) > 0) {
                        foreach ($images_to_delete as $image_id) {
                            $this->delete_image_by_id(intval($image_id), $comment_id);
                        }
                        error_log('EDIT USER DEBUG - Eliminadas ' . count($images_to_delete) . ' imagen(es)');
                    }
                }
                
                // Procesar nuevas imágenes si se subieron
                $uploaded_images = array();
                error_log('EDIT USER DEBUG - Verificando archivos: ' . print_r($_FILES, true));
                
                // Intentar con 'new_images' primero (modal de usuario), luego con 'images' (otros modales)
                if (!empty($_FILES['new_images']['name'][0])) {
                    error_log('EDIT USER DEBUG - Procesando imágenes desde new_images[]...');
                    $uploaded_images = $this->handle_admin_image_uploads($comment_id);
                    error_log('EDIT USER DEBUG - Imágenes subidas: ' . count($uploaded_images));
                } elseif (!empty($_FILES['images']['name'][0])) {
                    error_log('EDIT USER DEBUG - Procesando imágenes desde images[]...');
                    $uploaded_images = $this->handle_image_uploads($comment_id);
                    error_log('EDIT USER DEBUG - Imágenes subidas: ' . count($uploaded_images));
                } else {
                    error_log('EDIT USER DEBUG - No se recibieron archivos en $_FILES');
                }
                
                wp_send_json_success(array(
                    'message' => cf_trans('success_comment_updated') . (count($uploaded_images) > 0 ? ' ' . count($uploaded_images) . ' imagen(es).' : ''),
                    'images_uploaded' => count($uploaded_images)
                ));
            } else {
                // Obtener el error específico de la base de datos
                global $wpdb;
                $mysql_error = $wpdb->last_error;
                error_log('EDIT USER ERROR - MySQL Error: ' . $mysql_error);
                
                wp_send_json_error(array(
                    'message' => 'Error al actualizar el comentario: ' . ($mysql_error ? $mysql_error : 'Error de base de datos desconocido'),
                    'debug_info' => array(
                        'comment_id' => $comment_id,
                        'mysql_error' => $mysql_error,
                        'update_data' => $update_data
                    )
                ));
            }
        } catch (Exception $e) {
            error_log('EDIT USER EXCEPTION - Error: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Error interno: ' . $e->getMessage()
            ));
        }
        
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error interno del servidor'
            ));
        }
    }
    
    /**
     * Obtener imágenes de un comentario
     */
    public function get_comment_images() {
        try {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Debes iniciar sesión'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $images = $this->database->get_comment_images($comment_id);
            
            wp_send_json_success(array(
                'images' => $images ? $images : array()
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error al obtener imágenes'
            ));
        }
    }
    
    /**
     * Eliminar una imagen de un comentario
     */
    public function delete_comment_image() {
        try {
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            // Verificar que el usuario esté logueado
            if (!is_user_logged_in()) {
                wp_send_json_error(array(
                    'message' => 'Debes iniciar sesión'
                ));
                return;
            }
            
            $image_id = intval($_POST['image_id']);
            $current_user_id = get_current_user_id();
            
            // Obtener la imagen de la base de datos
            global $wpdb;
            $table_images = $wpdb->prefix . 'comentarios_free_images';
            $table_comments = $wpdb->prefix . 'comentarios_free';
            
            $image = $wpdb->get_row($wpdb->prepare(
                "SELECT i.*, c.user_id 
                FROM $table_images i
                INNER JOIN $table_comments c ON i.comment_id = c.id
                WHERE i.id = %d",
                $image_id
            ));
            
            if (!$image) {
                wp_send_json_error(array(
                    'message' => 'Imagen no encontrada'
                ));
                return;
            }
            
            // Verificar que el usuario sea dueño del comentario
            if ($image->user_id != $current_user_id) {
                wp_send_json_error(array(
                    'message' => 'No tienes permisos para eliminar esta imagen'
                ));
                return;
            }
            
            // Eliminar archivo físico
            $upload_dir = wp_upload_dir();
            $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image->image_url);
            
            if (file_exists($file_path)) {
                @unlink($file_path);
            }
            
            // Eliminar registro de la base de datos
            $deleted = $wpdb->delete(
                $table_images,
                array('id' => $image_id),
                array('%d')
            );
            
            if ($deleted) {
                wp_send_json_success(array(
                    'message' => 'Imagen eliminada correctamente'
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Error al eliminar la imagen de la base de datos'
                ));
            }
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error al eliminar la imagen: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Eliminar imagen por ID (método auxiliar para eliminación en lote)
     */
    private function delete_image_by_id($image_id, $comment_id) {
        global $wpdb;
        $table_images = $wpdb->prefix . 'comentarios_free_images';
        
        // Obtener información de la imagen
        $image = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_images WHERE id = %d AND comment_id = %d",
            $image_id,
            $comment_id
        ));
        
        if ($image) {
            // Eliminar archivo físico
            if (file_exists($image->file_path)) {
                @unlink($image->file_path);
                error_log('DELETE IMAGE - Archivo físico eliminado: ' . $image->file_path);
            }
            
            // Eliminar registro de la base de datos
            $deleted = $wpdb->delete(
                $table_images,
                array('id' => $image_id),
                array('%d')
            );
            
            error_log('DELETE IMAGE - Registro BD eliminado: ' . ($deleted ? 'SÍ' : 'NO'));
            return $deleted;
        }
        
        return false;
    }
    
    /**
     * Manejar subida de imágenes
     */
    private function handle_image_uploads($comment_id) {
        if (empty($_FILES['images']['name'][0])) {
            return array();
        }
        
        $upload_dir = wp_upload_dir();
        $comentarios_upload_dir = $upload_dir['basedir'] . '/comentarios-free/';
        $comentarios_upload_url = $upload_dir['baseurl'] . '/comentarios-free/';
        
        // Crear directorio si no existe
        if (!file_exists($comentarios_upload_dir)) {
            wp_mkdir_p($comentarios_upload_dir);
        }
        
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $max_file_size = 5 * 1024 * 1024; // 5MB (aumentado de 2MB)
        $max_images = 5;
        
        $uploaded_count = 0;
        $uploaded_images = array();
        $errors = array();
        
        foreach ($_FILES['images']['name'] as $key => $name) {
            if ($uploaded_count >= $max_images) {
                $errors[] = "Límite de $max_images imágenes alcanzado";
                break;
            }
            
            if (empty($name)) {
                continue;
            }
            
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $size = $_FILES['images']['size'][$key];
            $type = $_FILES['images']['type'][$key];
            $error = $_FILES['images']['error'][$key];
            
            // Obtener extensión real del archivo
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            // Log de información del archivo
            error_log("IMAGE UPLOAD - Procesando: $name (Tamaño: " . round($size/1024/1024, 2) . "MB, Tipo: $type, Extensión: $extension)");
            
            // Validaciones detalladas
            if ($error !== UPLOAD_ERR_OK) {
                $error_messages = array(
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize en php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                    UPLOAD_ERR_EXTENSION => 'Extensión PHP detuvo la subida'
                );
                $error_msg = isset($error_messages[$error]) ? $error_messages[$error] : "Error desconocido ($error)";
                error_log("IMAGE UPLOAD ERROR - $name: $error_msg");
                $errors[] = "$name: $error_msg";
                continue;
            }
            
            if ($size > $max_file_size) {
                $size_mb = round($size/1024/1024, 2);
                $max_mb = round($max_file_size/1024/1024, 2);
                error_log("IMAGE UPLOAD ERROR - $name: Tamaño {$size_mb}MB excede límite de {$max_mb}MB");
                $errors[] = "$name: Archivo muy grande ({$size_mb}MB, máximo {$max_mb}MB)";
                continue;
            }
            
            // Validar por extensión Y mime type
            if (!in_array($extension, $allowed_extensions)) {
                error_log("IMAGE UPLOAD ERROR - $name: Extensión '$extension' no permitida");
                $errors[] = "$name: Formato no permitido (usar: jpg, png, gif, webp)";
                continue;
            }
            
            if (!in_array($type, $allowed_types)) {
                error_log("IMAGE UPLOAD ERROR - $name: Tipo MIME '$type' no permitido");
                $errors[] = "$name: Tipo de archivo no válido";
                continue;
            }
            
            // Validar que el archivo temporal existe
            if (!file_exists($tmp_name)) {
                error_log("IMAGE UPLOAD ERROR - $name: Archivo temporal no existe");
                $errors[] = "$name: Error en archivo temporal";
                continue;
            }
            
            // Generar nombre único seguro
            $filename = $comment_id . '_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $comentarios_upload_dir . $filename;
            $fileurl = $comentarios_upload_url . $filename;
            
            // Verificar permisos del directorio
            if (!is_writable($comentarios_upload_dir)) {
                error_log("IMAGE UPLOAD ERROR - Directorio no tiene permisos de escritura: $comentarios_upload_dir");
                $errors[] = "$name: Error de permisos en servidor";
                continue;
            }
            
            // Mover archivo
            if (move_uploaded_file($tmp_name, $filepath)) {
                // Verificar que el archivo se creó correctamente
                if (!file_exists($filepath)) {
                    error_log("IMAGE UPLOAD ERROR - $name: Archivo no existe después de move_uploaded_file");
                    $errors[] = "$name: Error al guardar";
                    continue;
                }
                
                // Guardar información en base de datos
                $image_data = array(
                    'filename' => $filename,
                    'original_name' => sanitize_file_name($name),
                    'file_path' => $filepath,
                    'file_url' => $fileurl,
                    'file_size' => $size,
                    'mime_type' => $type
                );
                
                $insert_result = $this->database->insert_comment_image($comment_id, $image_data);
                
                if ($insert_result) {
                    $uploaded_count++;
                    error_log("IMAGE UPLOAD SUCCESS - $name guardada como $filename");
                    
                    $uploaded_images[] = array(
                        'url' => $fileurl,
                        'name' => $name
                    );
                } else {
                    error_log("IMAGE UPLOAD ERROR - $name: Fallo al insertar en BD");
                    // Eliminar archivo si no se pudo guardar en BD
                    @unlink($filepath);
                    $errors[] = "$name: Error al guardar en base de datos";
                }
            } else {
                $upload_error = error_get_last();
                error_log("IMAGE UPLOAD ERROR - No se pudo mover $name: " . print_r($upload_error, true));
                $errors[] = "$name: Error al mover archivo";
            }
        }
        
        // Log de resumen
        if (!empty($errors)) {
            error_log("IMAGE UPLOAD SUMMARY - Errores: " . implode(' | ', $errors));
        }
        error_log("IMAGE UPLOAD SUMMARY - Subidas exitosas: $uploaded_count de " . count($_FILES['images']['name']));
        
        return $uploaded_images;
    }
    
    /**
     * Nueva función para filtrar comentarios por calificación y país
     */
    public function filter_comments_new() {
        try {
            $post_id = intval($_POST['post_id']);
            // Obtener ID del post original (para WPML)
            $post_id = $this->get_original_post_id($post_id);
            $rating_filter = isset($_POST['rating_filter']) ? intval($_POST['rating_filter']) : '';
            $language_filter = isset($_POST['language_filter']) ? sanitize_text_field($_POST['language_filter']) : '';
            
            if (!$post_id) {
                wp_send_json_error(array('message' => 'ID del post no válido'));
                return;
            }
            
            global $wpdb;
            $table_name = $wpdb->prefix . 'comentarios_free';
            
            $where_clauses = array("post_id = %d", "status = 'approved'");
            $where_values = array($post_id);
            
            // Filtro por calificación
            if ($rating_filter) {
                $where_clauses[] = "rating = %d";
                $where_values[] = $rating_filter;
            }
            
            // Filtro por idioma
            if ($language_filter) {
                $where_clauses[] = "language = %s";
                $where_values[] = $language_filter;
            }
            
            $where_sql = implode(' AND ', $where_clauses);
            
            $query = $wpdb->prepare(
                "SELECT * FROM $table_name WHERE $where_sql ORDER BY created_at DESC LIMIT 10",
                $where_values
            );
            
            $comments = $wpdb->get_results($query);
            
            // Generar HTML
            $html = '';
            if ($comments) {
                foreach ($comments as $comment) {
                    $html .= $this->render_comment_html($comment);
                }
            }
            
            // Contar total
            $count_query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name WHERE $where_sql",
                $where_values
            );
            $total = $wpdb->get_var($count_query);
            
            wp_send_json_success(array(
                'html' => $html,
                'total' => $total,
                'has_more' => $total > 10
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Error al filtrar comentarios: ' . $e->getMessage()));
        }
    }
    
    /**
     * Renderizar HTML de un comentario
     */
    private function render_comment_html($comment) {
        $rating_stars = str_repeat('⭐', $comment->rating) . str_repeat('☆', 5 - $comment->rating);
        $created_at = date('d/m/Y', strtotime($comment->created_at));
        
        $html = '<div class="cf-comment-item" data-rating="' . $comment->rating . '" data-country="' . $comment->country . '">
                    <div class="cf-comment-header">
                        <div class="cf-comment-author">
                            <h4>' . esc_html($comment->author_name) . '</h4>
                            <div class="cf-user-meta">' . esc_html($comment->country) . ' • ' . esc_html($comment->language) . '</div>
                        </div>
                        <div class="cf-rating" data-rating="' . $comment->rating . '">' . $rating_stars . '</div>
                    </div>
                    <div class="cf-comment-content">
                        <h5>' . esc_html($comment->title) . '</h5>
                        <div class="cf-comment-text-wrapper">' . $this->format_comment_text($comment->content) . '</div>';
        
        // Agregar imágenes si existen, dentro del contenido
        $images = $this->database->get_comment_images($comment->id);
        if (!empty($images)) {
            $html .= '<div class="cf-comment-images">
                        <div class="cf-images-container">';
            
            foreach ($images as $image) {
                $html .= '<div class="cf-image-item">
                            <img src="' . esc_url($image->file_url) . '" 
                                 alt="' . esc_attr($image->original_name) . '"
                                 class="cf-comment-image"
                                 loading="lazy">
                          </div>';
            }
            
            $html .= '</div>
                    </div>';
        }
        
        $html .= '        <div class="cf-comment-date">' . $created_at . '</div>
                    </div>
                </div>';
                
                return $html;
    }
    
    /**
     * Formatear texto del comentario con truncado inteligente
     */
    private function format_comment_text($content) {
        // Escapar HTML y convertir saltos de línea a <br>
        $content = nl2br(esc_html($content));
        $max_length = 350;
        
        // Para truncar, usar la versión sin <br> tags
        $plain_content = strip_tags($content);
        
        if (strlen($plain_content) <= $max_length) {
            return '<p class="cf-comment-text">' . $content . '</p>';
        }
        
        // Truncar en la última palabra completa antes del límite
        $truncated = substr($plain_content, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        $truncated = nl2br(esc_html($truncated));
        
        return '<div class="cf-text-truncated">
                    <p class="cf-comment-text cf-text-short">' . $truncated . '...</p>
                    <p class="cf-comment-text cf-text-full" style="display: none;">' . $content . '</p>
                    <button class="cf-read-more-btn" type="button">
                        <span class="cf-read-more-text">Leer más</span>
                        <span class="cf-read-less-text" style="display: none;">Leer menos</span>
                    </button>
                </div>';
    }
    
    /**
     * Responder comentario como administrador
     */
    public function admin_reply_comment() {
        try {
            error_log('=== ADMIN REPLY DEBUG START ===');
            
            // Asegurar que la columna admin_response exista
            $this->database->ensure_edit_count_column();
            
            // Verificar que el usuario sea administrador
            if (!current_user_can('manage_options')) {
                error_log('ADMIN REPLY DEBUG - Usuario no es administrador');
                wp_send_json_error(array(
                    'message' => 'No tienes permisos de administrador'
                ));
                return;
            }
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                error_log('ADMIN REPLY DEBUG - Error de nonce');
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $reply_content = sanitize_textarea_field($_POST['reply_content']);
            
            error_log('ADMIN REPLY DEBUG - comment_id: ' . $comment_id . ', reply_content: ' . $reply_content);
            
            // Validar datos
            if (empty($reply_content)) {
                wp_send_json_error(array(
                    'message' => 'El contenido de la respuesta es obligatorio'
                ));
                return;
            }
            
            // Obtener comentario original para verificar que existe
            $original_comment = $this->database->get_comment($comment_id);
            if (!$original_comment) {
                wp_send_json_error(array(
                    'message' => 'Comentario no encontrado'
                ));
                return;
            }
            
            // Guardar solo el contenido de la respuesta (sin nombre del administrador)
            $admin_response = $reply_content;
            
            // Actualizar el comentario con la respuesta del administrador
            global $wpdb;
            $table_name = $wpdb->prefix . 'comentarios_free';
            
            error_log('ADMIN REPLY DEBUG - Actualizando tabla: ' . $table_name . ' con respuesta: ' . $admin_response);
            
            $result = $wpdb->update(
                $table_name,
                array('admin_response' => $admin_response),
                array('id' => $comment_id),
                array('%s'),
                array('%d')
            );
            
            error_log('ADMIN REPLY DEBUG - Resultado update: ' . var_export($result, true));
            error_log('ADMIN REPLY DEBUG - wpdb last_error: ' . $wpdb->last_error);
            
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'Respuesta guardada correctamente',
                    'admin_response' => $admin_response
                ));
            } else {
                wp_send_json_error(array(
                    'message' => 'Error al guardar la respuesta en la base de datos'
                ));
            }
            
        } catch (Exception $e) {
            error_log('ADMIN REPLY ERROR: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Error interno: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Verifica el estado de login del usuario (para el flujo de dos pasos)
     */
    public function check_user_login_status() {
        try {
            $is_logged_in = is_user_logged_in();
            $user_data = null;
            
            if ($is_logged_in) {
                $current_user = wp_get_current_user();
                $user_data = array(
                    'id' => $current_user->ID,
                    'name' => $current_user->display_name,
                    'email' => $current_user->user_email
                );
            }
            
            wp_send_json_success(array(
                'logged_in' => $is_logged_in,
                'user' => $user_data
            ));
            
        } catch (Exception $e) {
            error_log('CHECK LOGIN STATUS ERROR: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Error al verificar estado de login: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Login de usuario con email y contraseña
     */
    public function user_login() {
        try {
            error_log('🔑 CF Login: Iniciando proceso de login');
            
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($email) || empty($password)) {
                wp_send_json_error('Por favor completa todos los campos.');
                return;
            }
            
            // Buscar usuario por email
            $user = get_user_by('email', $email);
            
            if (!$user) {
                error_log('❌ CF Login: Usuario no encontrado con email: ' . $email);
                wp_send_json_error('Email o contraseña incorrectos.');
                return;
            }
            
            // Verificar contraseña
            if (!wp_check_password($password, $user->data->user_pass, $user->ID)) {
                error_log('❌ CF Login: Contraseña incorrecta para usuario: ' . $email);
                wp_send_json_error('Email o contraseña incorrectos.');
                return;
            }
            
            // Login exitoso
            wp_set_current_user($user->ID);
            wp_set_auth_cookie($user->ID);
            
            error_log('✅ CF Login: Login exitoso para usuario: ' . $email);
            
            wp_send_json_success(array(
                'message' => '¡Bienvenido! Redirigiendo...',
                'user' => array(
                    'id' => $user->ID,
                    'name' => $user->display_name,
                    'email' => $user->user_email
                )
            ));
            
        } catch (Exception $e) {
            error_log('❌ CF Login ERROR: ' . $e->getMessage());
            wp_send_json_error('Error al procesar el login: ' . $e->getMessage());
        }
    }
    
    /**
     * Registro de nuevo usuario
     */
    public function user_register() {
        try {
            error_log('📝 CF Register: Iniciando proceso de registro');
            
            $name = sanitize_text_field($_POST['name']);
            $email = sanitize_email($_POST['email']);
            $password = $_POST['password'];
            
            if (empty($name) || empty($email) || empty($password)) {
                wp_send_json_error('Por favor completa todos los campos.');
                return;
            }
            
            if (!is_email($email)) {
                wp_send_json_error('Email no válido.');
                return;
            }
            
            if (strlen($password) < 6) {
                wp_send_json_error('La contraseña debe tener al menos 6 caracteres.');
                return;
            }
            
            // Verificar si el email ya existe
            if (email_exists($email)) {
                error_log('❌ CF Register: Email ya existe: ' . $email);
                wp_send_json_error('Este email ya está registrado. Por favor inicia sesión.');
                return;
            }
            
            // Crear username único basado en email
            $username = sanitize_user(explode('@', $email)[0]);
            $base_username = $username;
            $counter = 1;
            
            while (username_exists($username)) {
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Crear usuario
            $user_id = wp_create_user($username, $password, $email);
            
            if (is_wp_error($user_id)) {
                error_log('❌ CF Register: Error al crear usuario: ' . $user_id->get_error_message());
                wp_send_json_error('Error al crear la cuenta: ' . $user_id->get_error_message());
                return;
            }
            
            // Actualizar nombre del usuario
            wp_update_user(array(
                'ID' => $user_id,
                'display_name' => $name,
                'first_name' => $name
            ));
            
            // Login automático después del registro
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id);
            
            error_log('✅ CF Register: Registro exitoso para: ' . $email);
            
            wp_send_json_success(array(
                'message' => '¡Cuenta creada exitosamente! Redirigiendo...',
                'user' => array(
                    'id' => $user_id,
                    'name' => $name,
                    'email' => $email
                )
            ));
            
        } catch (Exception $e) {
            error_log('❌ CF Register ERROR: ' . $e->getMessage());
            wp_send_json_error('Error al procesar el registro: ' . $e->getMessage());
        }
    }
    
    /**
     * Obtener comentario para edición por administrador
     */
    public function admin_get_comment_for_edit() {
        try {
            // Asegurar que todas las columnas existan y tengan valores por defecto
            $this->database->ensure_edit_count_column();
            
            // Verificar que las columnas necesarias existen
            $this->ensure_missing_columns();
            
            // Verificar que el usuario sea administrador
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => 'No tienes permisos de administrador'
                ));
                return;
            }
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $comment = $this->database->get_comment($comment_id);
            
            if (!$comment) {
                wp_send_json_error(array(
                    'message' => 'Comentario no encontrado'
                ));
                return;
            }
            
            // Log inicial para debug
            error_log('ADMIN EDIT DEBUG - Comentario RAW antes de procesar: ' . json_encode($comment));
            
            // Verificar si las propiedades existen y asignar valores por defecto más robustos
            $comment->country = (property_exists($comment, 'country') && $comment->country && $comment->country !== 'null' && $comment->country !== '') 
                ? $comment->country : '';
                
            $comment->language = (property_exists($comment, 'language') && $comment->language && $comment->language !== 'null' && $comment->language !== '') 
                ? $comment->language : 'es';
                
            $comment->travel_companion = (property_exists($comment, 'travel_companion') && $comment->travel_companion && $comment->travel_companion !== 'null' && $comment->travel_companion !== '') 
                ? $comment->travel_companion : 'solo';
                
            $comment->rating = (property_exists($comment, 'rating') && $comment->rating && $comment->rating > 0) 
                ? intval($comment->rating) : 5;
            
            error_log('ADMIN EDIT DEBUG - Comentario PROCESADO después de defaults: ' . json_encode(array(
                'id' => $comment->id,
                'country' => $comment->country,
                'language' => $comment->language, 
                'travel_companion' => $comment->travel_companion,
                'rating' => $comment->rating
            )));
            
            // Obtener imágenes del comentario
            $images = $this->database->get_comment_images($comment_id);
            
            wp_send_json_success(array(
                'comment' => $comment,
                'images' => $images
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error interno del servidor'
            ));
        }
    }
    
    /**
     * Editar comentario como administrador (sin límites)
     */
    public function admin_edit_comment() {
        try {
            // Verificar que el usuario sea administrador
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array(
                    'message' => 'No tienes permisos de administrador'
                ));
                return;
            }
            
            // Verificar nonce
            if (!wp_verify_nonce($_POST['nonce'], 'comentarios_free_nonce')) {
                wp_send_json_error(array(
                    'message' => 'Error de seguridad'
                ));
                return;
            }
            
            $comment_id = intval($_POST['comment_id']);
            $comment = $this->database->get_comment($comment_id);
            
            if (!$comment) {
                wp_send_json_error(array(
                    'message' => 'Comentario no encontrado'
                ));
                return;
            }
            
            // Sanitizar y validar datos
            $rating = intval($_POST['rating']);
            $travel_companion = sanitize_text_field($_POST['travel_companion']);
            $country = sanitize_text_field($_POST['country']);
            $language = sanitize_text_field($_POST['language']);
            $title = sanitize_text_field($_POST['title']);
            $content = sanitize_textarea_field($_POST['content']);
            // El estado siempre debe ser 'approved' - los administradores no deben cambiar esto
            $status = 'approved';
            $remove_all_images = isset($_POST['remove_all_images']) ? intval($_POST['remove_all_images']) : 0;
            
            // Validaciones
            $errors = array();
            
            if ($rating < 1 || $rating > 5) {
                $errors[] = 'La calificación debe ser entre 1 y 5';
            }
            
            if (empty($travel_companion)) {
                $errors[] = 'El campo "Con quién viajaste" es obligatorio';
            }
            
            // País es opcional - no validar
            
            if (empty($language)) {
                $errors[] = 'El idioma es obligatorio';
            }
            
            if (empty($title)) {
                $errors[] = 'El título es obligatorio';
            }
            
            if (empty($content)) {
                $errors[] = 'El contenido es obligatorio';
            }
            
            if (!empty($errors)) {
                wp_send_json_error(array(
                    'message' => implode(', ', $errors)
                ));
                return;
            }
            
            // Preparar datos de actualización
            $update_data = array(
                'rating' => $rating,
                'travel_companion' => $travel_companion,
                'country' => $country,
                'language' => $language,
                'title' => $title,
                'content' => $content,
                'status' => $status
            );
            
            // Actualizar comentario
            $result = $this->database->update_comment($comment_id, $update_data);
            
            if (!$result && $result !== 0) {
                wp_send_json_error(array(
                    'message' => 'Error al actualizar el comentario'
                ));
                return;
            }
            
            // Manejar eliminación de imágenes individuales (desde array)
            if (isset($_POST['delete_images']) && !empty($_POST['delete_images'])) {
                $images_to_delete = json_decode(stripslashes($_POST['delete_images']), true);
                error_log('ADMIN EDIT DEBUG - Eliminando imágenes: ' . print_r($images_to_delete, true));
                
                if (is_array($images_to_delete) && count($images_to_delete) > 0) {
                    foreach ($images_to_delete as $image_id) {
                        $this->delete_image_by_id(intval($image_id), $comment_id);
                    }
                    error_log('ADMIN EDIT DEBUG - Eliminadas ' . count($images_to_delete) . ' imagen(es)');
                }
            }
            
            // Manejar eliminación de todas las imágenes si se solicitó
            if ($remove_all_images) {
                $this->database->delete_comment_images($comment_id);
            }
            
            // Manejar nuevas imágenes si se subieron
            $uploaded_images = array();
            if (!empty($_FILES['new_images']['name'][0])) {
                $uploaded_images = $this->handle_admin_image_uploads($comment_id);
            }
            
            wp_send_json_success(array(
                'message' => cf_trans('success_admin_updated'),
                'uploaded_images' => $uploaded_images
            ));
            
        } catch (Exception $e) {
            error_log('ADMIN EDIT ERROR: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => 'Error interno del servidor: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Manejar subida de imágenes para administrador
     */
    private function handle_admin_image_uploads($comment_id) {
        if (empty($_FILES['new_images']['name'][0])) {
            return array();
        }
        
        $upload_dir = wp_upload_dir();
        $comentarios_upload_dir = $upload_dir['basedir'] . '/comentarios-free/';
        $comentarios_upload_url = $upload_dir['baseurl'] . '/comentarios-free/';
        
        // Crear directorio si no existe
        if (!file_exists($comentarios_upload_dir)) {
            wp_mkdir_p($comentarios_upload_dir);
        }
        
        $allowed_types = array('image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp');
        $allowed_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp');
        $max_file_size = 5 * 1024 * 1024; // 5MB (aumentado de 2MB)
        $max_images = 5;
        
        $uploaded_count = 0;
        $uploaded_images = array();
        $errors = array();
        
        foreach ($_FILES['new_images']['name'] as $key => $name) {
            if ($uploaded_count >= $max_images) {
                $errors[] = "Límite de $max_images imágenes alcanzado";
                break;
            }
            
            if (empty($name)) {
                continue;
            }
            
            $tmp_name = $_FILES['new_images']['tmp_name'][$key];
            $size = $_FILES['new_images']['size'][$key];
            $type = $_FILES['new_images']['type'][$key];
            $error = $_FILES['new_images']['error'][$key];
            
            // Obtener extensión real del archivo
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            
            // Log de información del archivo
            error_log("ADMIN IMAGE UPLOAD - Procesando: $name (Tamaño: " . round($size/1024/1024, 2) . "MB, Tipo: $type, Extensión: $extension)");
            
            // Validaciones detalladas
            if ($error !== UPLOAD_ERR_OK) {
                $error_messages = array(
                    UPLOAD_ERR_INI_SIZE => 'El archivo excede upload_max_filesize en php.ini',
                    UPLOAD_ERR_FORM_SIZE => 'El archivo excede MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'El archivo se subió parcialmente',
                    UPLOAD_ERR_NO_FILE => 'No se subió ningún archivo',
                    UPLOAD_ERR_NO_TMP_DIR => 'Falta carpeta temporal',
                    UPLOAD_ERR_CANT_WRITE => 'Error al escribir en disco',
                    UPLOAD_ERR_EXTENSION => 'Extensión PHP detuvo la subida'
                );
                $error_msg = isset($error_messages[$error]) ? $error_messages[$error] : "Error desconocido ($error)";
                error_log("ADMIN IMAGE UPLOAD ERROR - $name: $error_msg");
                $errors[] = "$name: $error_msg";
                continue;
            }
            
            if ($size > $max_file_size) {
                $size_mb = round($size/1024/1024, 2);
                $max_mb = round($max_file_size/1024/1024, 2);
                error_log("ADMIN IMAGE UPLOAD ERROR - $name: Tamaño {$size_mb}MB excede límite de {$max_mb}MB");
                $errors[] = "$name: Archivo muy grande ({$size_mb}MB, máximo {$max_mb}MB)";
                continue;
            }
            
            // Validar por extensión Y mime type
            if (!in_array($extension, $allowed_extensions)) {
                error_log("ADMIN IMAGE UPLOAD ERROR - $name: Extensión '$extension' no permitida");
                $errors[] = "$name: Formato no permitido (usar: jpg, png, gif, webp)";
                continue;
            }
            
            if (!in_array($type, $allowed_types)) {
                error_log("ADMIN IMAGE UPLOAD ERROR - $name: Tipo MIME '$type' no permitido");
                $errors[] = "$name: Tipo de archivo no válido";
                continue;
            }
            
            // Validar que el archivo temporal existe
            if (!file_exists($tmp_name)) {
                error_log("ADMIN IMAGE UPLOAD ERROR - $name: Archivo temporal no existe");
                $errors[] = "$name: Error en archivo temporal";
                continue;
            }
            
            // Generar nombre único seguro
            $filename = $comment_id . '_admin_' . uniqid() . '_' . time() . '.' . $extension;
            $filepath = $comentarios_upload_dir . $filename;
            $fileurl = $comentarios_upload_url . $filename;
            
            // Verificar permisos del directorio
            if (!is_writable($comentarios_upload_dir)) {
                error_log("ADMIN IMAGE UPLOAD ERROR - Directorio no tiene permisos de escritura: $comentarios_upload_dir");
                $errors[] = "$name: Error de permisos en servidor";
                continue;
            }
            
            // Mover archivo
            if (move_uploaded_file($tmp_name, $filepath)) {
                // Verificar que el archivo se creó correctamente
                if (!file_exists($filepath)) {
                    error_log("ADMIN IMAGE UPLOAD ERROR - $name: Archivo no existe después de move_uploaded_file");
                    $errors[] = "$name: Error al guardar";
                    continue;
                }
                
                // Guardar información en base de datos
                $image_data = array(
                    'filename' => $filename,
                    'original_name' => sanitize_file_name($name),
                    'file_path' => $filepath,
                    'file_url' => $fileurl,
                    'file_size' => $size,
                    'mime_type' => $type
                );
                
                $insert_result = $this->database->insert_comment_image($comment_id, $image_data);
                
                if ($insert_result) {
                    $uploaded_count++;
                    error_log("ADMIN IMAGE UPLOAD SUCCESS - $name guardada como $filename");
                    
                    $uploaded_images[] = array(
                        'url' => $fileurl,
                        'name' => $name
                    );
                } else {
                    error_log("ADMIN IMAGE UPLOAD ERROR - $name: Fallo al insertar en BD");
                    // Eliminar archivo si no se pudo guardar en BD
                    @unlink($filepath);
                    $errors[] = "$name: Error al guardar en base de datos";
                }
            } else {
                $upload_error = error_get_last();
                error_log("ADMIN IMAGE UPLOAD ERROR - No se pudo mover $name: " . print_r($upload_error, true));
                $errors[] = "$name: Error al mover archivo";
            }
        }
        
        // Log de resumen
        if (!empty($errors)) {
            error_log("ADMIN IMAGE UPLOAD SUMMARY - Errores: " . implode(' | ', $errors));
        }
        error_log("ADMIN IMAGE UPLOAD SUMMARY - Subidas exitosas: $uploaded_count de " . count($_FILES['new_images']['name']));
        
        return $uploaded_images;
    }
    
    /**
     * Verificar que las columnas necesarias existan en la tabla
     */
    private function ensure_missing_columns() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'comentarios_free';
        
        // Obtener todas las columnas de la tabla
        $columns = $wpdb->get_col("SHOW COLUMNS FROM $table_name");
        
        // Verificar si faltan columnas críticas
        $missing_columns = array();
        
        if (!in_array('country', $columns)) {
            $missing_columns[] = 'country';
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN country varchar(100) DEFAULT '' AFTER author_email");
        }
        
        if (!in_array('language', $columns)) {
            $missing_columns[] = 'language';
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN language varchar(10) NOT NULL DEFAULT 'es' AFTER country");
        }
        
        if (!in_array('travel_companion', $columns)) {
            $missing_columns[] = 'travel_companion';
            $wpdb->query("ALTER TABLE $table_name ADD COLUMN travel_companion varchar(50) DEFAULT 'solo' AFTER language");
        }
        
        if (!empty($missing_columns)) {
            error_log('ADMIN EDIT DEBUG - Columnas agregadas: ' . implode(', ', $missing_columns));
            
            // Actualizar comentarios existentes con valores por defecto
            if (in_array('country', $missing_columns)) {
                $wpdb->query("UPDATE $table_name SET country = '' WHERE country IS NULL");
            }
            if (in_array('language', $missing_columns)) {
                $wpdb->query("UPDATE $table_name SET language = 'es' WHERE language = '' OR language IS NULL");
            }
            if (in_array('travel_companion', $missing_columns)) {
                $wpdb->query("UPDATE $table_name SET travel_companion = 'solo' WHERE travel_companion = '' OR travel_companion IS NULL");
            }
        }
    }
    
    // Login ahora manejado por plugin loginfree via shortcode [advanced_registration_form]
}