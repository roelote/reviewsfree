<?php
/**
 * Frontend con Flujo de Dos Pasos para Usuarios No Logueados
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Frontend_TwoStep {
    
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
        // Asegurar que la columna admin_response y travel_companion existen
        $this->database->ensure_edit_count_column();
    }
    
    public function display_comments($atts) {
        $post_id = intval($atts['post_id']);
        // Obtener ID del post original (para WPML)
        $post_id = $this->get_original_post_id($post_id);
        $limit = intval($atts['limit']);
        // Ahora TODOS los usuarios pueden ver el botón (logueados y no logueados)
        $show_form = ($atts['show_form'] === 'true');
        
        if (!$post_id) {
            return '<p>Error: ID de post no válido</p>';
        }
        
        ob_start();
        
        echo '<div id="comentarios-free-container" class="comentarios-free-container">';
        
        // Header
        echo '<div class="cf-header">';
        echo '<h3 class="cf-title">' . cf_trans('comments_title') . '</h3>';
        if ($show_form) {
            // Verificar si el usuario ya tiene un comentario en este post
            $user_has_comment = $this->user_has_existing_comment($post_id);
            
            if ($user_has_comment) {
                // Mostrar botón "Editar reseña" que redirige al panel de usuario
                echo '<div class="cf-edit-review-container">';
                echo '<small style="color: #666; font-size: 12px;">' . cf_trans('already_reviewed') . '</small>';
                echo '<a href="' . admin_url('admin.php?page=comentarios-free-user-panel') . '" class="cf-btn cf-btn-edit">' . cf_trans('edit_review') . '</a>';
                echo '</div>';
            } else {
                // Mostrar botón "Escribir una Reseña" normal
                echo '<button id="cf-add-comment-btn" class="cf-btn cf-btn-primary">' . cf_trans('write_review') . '</button>';
            }
        }
        echo '</div>';
        
        // Obtener estadísticas de comentarios
        $stats = $this->get_comments_stats($post_id);
        
        // Barra de filtros y estadísticas
        echo '<div class="cf-filters-stats-bar">';
        
        // Lado izquierdo: Filtros
        echo '<div class="cf-filters-section">';
        
        echo '<div class="cf-filter-group">';
        echo '<label for="cf-filter-rating">' . cf_trans('filter_by_rating') . '</label>';
        echo '<select id="cf-filter-rating" class="cf-filter-select">';
        echo '<option value="">' . cf_trans('all') . '</option>';
        echo '<option value="5">⭐⭐⭐⭐⭐ (5)</option>';
        echo '<option value="4">⭐⭐⭐⭐ (4)</option>';
        echo '<option value="3">⭐⭐⭐ (3)</option>';
        echo '<option value="2">⭐⭐ (2)</option>';
        echo '<option value="1">⭐ (1)</option>';
        echo '</select>';
        echo '</div>';
        
        echo '<div class="cf-filter-group">';
        echo '<label for="cf-filter-language">' . cf_trans('filter_by_language') . '</label>';
        echo '<select id="cf-filter-language" class="cf-filter-select">';
        echo '<option value="">' . cf_trans('all') . '</option>';
        echo '<option value="es">Español</option>';
        echo '<option value="en">English</option>';
        echo '<option value="de">Deutsch</option>';
        echo '<option value="fr">Français</option>';
        echo '<option value="pt">Português</option>';
        echo '<option value="it">Italiano</option>';
        echo '<option value="nl">Nederlands</option>';
        echo '<option value="zh">中文</option>';
        echo '<option value="ja">日本語</option>';
        echo '<option value="ko">한국어</option>';
        echo '<option value="he">עברית</option>';
        echo '<option value="otros">' . cf_trans('others') . '</option>';
        echo '</select>';
        echo '</div>';
        
        echo '</div>'; // fin cf-filters-section
        
        // Lado derecho: Estadísticas
        echo '<div class="cf-stats-section">';
        echo '<div class="cf-stats-summary">';
        echo '<div class="cf-rating-summary">';
         echo '<span class="cf-total-comments"> ' . $stats['total'] . ' ' . cf_trans('reviews') . '</span>  ';
        echo '<span class="cf-avg-rating">' . number_format($stats['avg_rating'], 1) . '</span>';
        echo '<span class="cf-rating-separator"> / </span>';
        echo '<span class="cf-max-rating">5</span>';
       
        echo '</div>';
        echo '</div>';
        
        // Estrellas visuales del promedio
        echo '<div class="cf-visual-rating">';
        $this->display_rating_stars($stats['avg_rating']);
        echo '</div>';
        
        echo '</div>'; // fin cf-stats-section
        
        echo '</div>'; // fin cf-filters-stats-bar
        
        // Lista de comentarios - mostrar solo los primeros 5
        $initial_limit = 5;
        $comments = $this->database->get_comments(array(
            'post_id' => $post_id,
            'limit' => $initial_limit,
            'status' => 'approved'
        ));
        
        // Verificar si hay más comentarios
        $total_comments = $this->database->get_comments_count(array(
            'post_id' => $post_id,
            'status' => 'approved'
        ));
        $has_more = $total_comments > $initial_limit;
        
        echo '<div class="cf-comments-list" data-post-id="' . $post_id . '" data-offset="' . $initial_limit . '" data-total="' . $total_comments . '">';
        echo $this->render_comments($comments);
        echo '</div>';
        
        // Botón "Ver más" si hay más de 5 comentarios
        if ($has_more) {
            $remaining = $total_comments - $initial_limit;
            echo '<div class="cf-load-more-container" style="text-align: center; margin: 20px 0;">
                    <button id="cf-load-more-btn" class="cf-btn cf-btn-secondary" data-loading="false">
                        ' . cf_trans('load_more') . ' (' . $remaining . ' ' . cf_trans('remaining') . ')
                    </button>
                  </div>';
        }
        
        // Modal de comentario
        if ($show_form) {
            echo $this->get_modal_html($post_id);
        }
        
        // CSS y JavaScript
        echo $this->get_css_js();
        
        echo '</div>';
        
        return ob_get_clean();
    }
    
    private function render_comments($comments) {
        if (empty($comments)) {
            return '<div class="cf-no-comments"><p>' . cf_trans('no_reviews') . '</p></div>';
        }
        
        $output = '';
        foreach ($comments as $comment) {
            // Obtener la fecha formateada
            $date = new DateTime($comment->created_at);
            $formatted_date = $date->format('d / F / Y');
            
            // Obtener bandera del país
            $country_flag = $this->get_country_flag($comment->country);
            
            // Obtener el nombre actualizado del usuario si está registrado
            $display_name = $comment->author_name; // Por defecto usar el nombre almacenado
            if ($comment->user_id > 0) {
                $user_data = get_userdata($comment->user_id);
                if ($user_data) {
                    $display_name = $user_data->display_name; // Usar el nombre actual del perfil
                }
            }
            
            $output .= '<div class="cf-comment cf-comment-item" data-language="' . htmlspecialchars($comment->language) . '">
                <div class="cf-comment-layout">
                    <!-- COLUMNA IZQUIERDA: Bandera + Nombre + Calificación -->
                    <div class="cf-left-column">
                        <div class="cf-user-header">
                            <div class="cf-country-name">
                                <span class="cf-country-flag">' . $country_flag . '</span>
                                <h4 class="cf-user-name">' . htmlspecialchars($display_name) . '</h4>
                            </div>
                        </div>
                        <div class="cf-rating-section">
                            ' . $this->render_stars($comment->rating) . '
                        </div>
                    </div>
                    
                    <!-- COLUMNA CENTRAL: Título y Testimonio -->
                    <div class="cf-center-column">
                        <div class="cf-comment-content">
                            <h5 class="cf-comment-title">' . htmlspecialchars($comment->title) . '</h5>
                            <div class="cf-comment-text-wrapper">' . $this->format_comment_text($comment->content) . '</div>';
            
            // Mostrar imágenes del comentario dentro del contenido
            $images = $this->database->get_comment_images($comment->id);
            if (!empty($images)) {
                $output .= '<div class="cf-comment-images">
                    <div class="cf-images-container">';
                
                foreach ($images as $image) {
                    $output .= '<div class="cf-image-item">
                        <img src="' . esc_url($image->file_url) . '" 
                             alt="' . esc_attr($image->original_name) . '"
                             class="cf-comment-image"
                             loading="lazy">
                    </div>';
                }
                
                $output .= '</div>
                </div>';
            }
            
            
            // Respuesta del administrador (dentro de cf-center-column)
            if (!empty($comment->admin_response)) {
                $favicon_url = get_site_icon_url(16);
                $favicon_html = $favicon_url ? '<img src="' . esc_url($favicon_url) . '" alt="Admin" class="cf-admin-icon"> ' : '👨‍💼 ';
                $output .= '<div class="bodydesp">
                    <div class="test">
                        <span class="cf-admin-badge">' . $favicon_html . cf_trans('response') . '</span>
                    </div>
                    <div class="cf-admin-response-content">
                        ' . $this->truncate_text($comment->admin_response, 237) . '
                    </div>
                </div>';
            }
            
            $output .= '        </div>
                    </div>
                    
                    <!-- COLUMNA DERECHA: Fecha y Compañía de Viaje -->
                    <div class="cf-right-column">
                        <div class="cf-comment-date">
                            <span class="cf-date">' . $formatted_date . '</span>
                        </div>
                        <div class="cf-travel-companion">
                            <span class="cf-companion-text">' . cf_trans('traveled_prefix') . ' ' . $this->format_travel_companion($comment->travel_companion) . '</span>
                        </div>
                    </div>
                </div>';
            
            $output .= '</div>'; // Cerrar cf-comment
        }
        
        return $output;
    }
    
    private function render_stars($rating) {
        $output = '<div class="cf-stars cf-comment-rating">';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                // Estrella llena
                $output .= '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-star cf-star-filled cf-rating-star active" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"></path></svg>';
            } else {
                // Estrella vacía
                $output .= '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-star cf-star-empty cf-rating-star" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>';
            }
        }
        $output .= '</div>';
        return $output;
    }
    
    private function get_country_flag($country) {
        // Usar la nueva clase de países
        return ComentariosFree_Countries::get_country_flag($country);
    }
    
    private function format_travel_companion($travel_companion) {
        $companions = array(
            'solo' => 'option_solo',
            'en_pareja' => 'option_couple',
            'en_familia' => 'option_family',
            'con_amigos' => 'option_friends',
            // Soporte retrocompatible para valores antiguos
            'pareja' => 'option_couple',
            'familia' => 'option_family',
            'amigos' => 'option_friends'
        );
        
        $translation_key = isset($companions[$travel_companion]) ? $companions[$travel_companion] : null;
        return $translation_key ? cf_trans($translation_key) : $travel_companion;
    }
    
    /**
     * Truncar texto genérico con botón "Leer más"
     */
    private function truncate_text($content, $max_length = 350, $wrapper_class = 'cf-comment-text') {
        // Escapar HTML y convertir saltos de línea a <br>
        $content = nl2br(htmlspecialchars($content));
        
        // Para truncar, usar la versión sin <br> tags
        $plain_content = strip_tags($content);
        
        if (strlen($plain_content) <= $max_length) {
            return '<p class="' . $wrapper_class . '">' . $content . '</p>';
        }
        
        // Truncar en la última palabra completa antes del límite
        $truncated = substr($plain_content, 0, $max_length);
        $last_space = strrpos($truncated, ' ');
        if ($last_space !== false) {
            $truncated = substr($truncated, 0, $last_space);
        }
        $truncated = nl2br(htmlspecialchars($truncated));
        
        return '<div class="cf-text-truncated">
                    <p class="' . $wrapper_class . ' cf-text-short">' . $truncated . '...</p>
                    <p class="' . $wrapper_class . ' cf-text-full" style="display: none;">' . $content . '</p>
                    <button class="cf-read-more-btn" type="button">
                        <span class="cf-read-more-text">' . cf_trans('read_more') . '</span>
                        <span class="cf-read-less-text" style="display: none;">' . cf_trans('read_less') . '</span>
                    </button>
                </div>';
    }
    
    /**
     * Formatear texto del comentario con truncado inteligente
     */
    private function format_comment_text($content) {
        return $this->truncate_text($content, 350, 'cf-comment-text');
    }
    
    private function get_modal_html($post_id) {
        $is_logged_in = is_user_logged_in();
        
        if ($is_logged_in) {
            // Usuario logueado - formulario completo normal
            $current_user = wp_get_current_user();
            $user_name = $current_user->display_name;
            $user_email = $current_user->user_email;
            
            return '<div id="cf-comment-modal" class="cf-modal" style="display: none;">
                <div class="cf-modal-backdrop"></div>
                <div class="cf-modal-content">
                    <div class="cf-modal-header">
                        <h3>' . cf_trans('write_review') . '</h3>
                        <button class="cf-modal-close">×</button>
                    </div>
                    <form id="cf-comment-form">
                        <input type="hidden" name="action" value="comentarios_submit">
                        <input type="hidden" name="post_id" value="' . $post_id . '">
                        <input type="hidden" id="cf-rating-value" name="rating" value="0">
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('rating_label') . '</label>
                            <div class="cf-rating-input">
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="1" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="2" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="3" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="4" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="5" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                            </div>
                        </div>
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('travel_companion_label') . '</label>
                            <select name="travel_companion" required>
                                <option value="">' . cf_trans('select_option') . '</option>
                                <option value="solo">' . cf_trans('option_solo') . '</option>
                                <option value="en_pareja">' . cf_trans('option_couple') . '</option>
                                <option value="en_familia">' . cf_trans('option_family') . '</option>
                                <option value="con_amigos">' . cf_trans('option_friends') . '</option>
                            </select>
                        </div>
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('language_label') . '</label>
                            <select name="language" required>
                                <option value="">' . cf_trans('select_language') . '</option>
                                <option value="es">Español</option>
                                <option value="en">English</option>
                                <option value="de">Deutsch</option>
                                <option value="fr">Français</option>
                                <option value="pt">Português</option>
                                <option value="it">Italiano</option>
                                <option value="nl">Nederlands</option>
                                <option value="zh">中文</option>
                                <option value="ja">日本語</option>
                                <option value="ko">한국어</option>
                                <option value="he">עברית</option>
                                <option value="otros">' . cf_trans('others') . '</option>
                            </select>
                        </div>
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('title_label') . '</label>
                            <input type="text" name="title" placeholder="' . cf_trans('title_placeholder') . '" required>
                        </div>
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('content_label') . '</label>
                            <textarea name="content" id="cf-content-logged" placeholder="' . cf_trans('content_placeholder') . '" required rows="4" maxlength="2000"></textarea>
                            <small>' . cf_trans('max_characters') . '. <span id="cf-content-count-logged" style="color: #6c757d;">0</span> ' . cf_trans('characters_used') . '.</small>
                        </div>
                        
                        <div class="cf-form-group">
                            <label for="images">' . cf_trans('upload_photos') . '</label>
                            <div class="cf-custom-file-upload">
                                <input type="file" id="images" name="images[]" multiple accept="image/*" style="display: none;">
                                <button type="button" class="cf-file-button" onclick="document.getElementById(\'images\').click()">
                                    ' . cf_trans('choose_files') . '
                                </button>
                                <span class="cf-file-name">' . cf_trans('no_file_chosen') . '</span>
                            </div>
                        </div>
                        
                        <!-- Campos ocultos para usuarios logueados -->
                        <input type="hidden" name="author_name" value="' . htmlspecialchars($user_name) . '">
                        <input type="hidden" name="author_email" value="' . htmlspecialchars($user_email) . '">
                        
                        <div class="cf-form-group">
                            <label>' . cf_trans('country_label') . '</label>
                            <div class="cf-country-autocomplete-container">
                                <input type="text" 
                                       name="country" 
                                       id="cf-country-input-logged" 
                                       class="cf-country-input" 
                                       placeholder="' . cf_trans('choose_country') . '" 
                                       autocomplete="off">
                                <div class="cf-country-dropdown" id="cf-country-dropdown-logged" style="display: none;"></div>
                            </div>
                        </div>
                        
                        <div class="cf-form-group">
                            <button type="submit" id="cf-submit-btn" class="cf-btn cf-btn-primary">' . cf_trans('publish') . '</button>
                        </div>
                    </form>
                </div>
            </div>';
        } else {
            // Usuario NO logueado - Flujo de dos pasos (PRIMERO autenticación, LUEGO reseña)
            return '<div id="cf-comment-modal" class="cf-modal" style="display: none;">
                <div class="cf-modal-backdrop"></div>
                <div class="cf-modal-content">
                    <div class="cf-modal-header">
                        <h3 id="cf-modal-title">Iniciar Sesión</h3>
                        <button class="cf-modal-close">×</button>
                    </div>
                    
                    <!-- PASO 1: Autenticación -->
                    <div id="cf-step-1" class="cf-step">
                        <div class="cf-auth-info" style="background: #fff5ed; border: 1px solid #ffd9b3; border-radius: 8px; padding: 15px; margin-bottom: 20px; text-align: center;">
                            <p style="margin: 0; color: #333;">👋 Para escribir una reseña, primero debes iniciar sesión o crear una cuenta.</p>
                        </div>
                        
                        <div class="cf-auth-container">
                            ' . $this->get_registration_form() . '
                        </div>
                    </div>
                    
                    <!-- PASO 2: Formulario de reseña (después del login) -->
                    <div id="cf-step-2" class="cf-step" style="display: none;">
                        <form id="cf-review-form">
                            <input type="hidden" name="post_id" value="' . $post_id . '">
                            <input type="hidden" id="cf-rating-value" name="rating" value="0">
                            
                            <div class="cf-form-group">
                                <label>Calificación *</label>
                                <div class="cf-rating-input">
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="1" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="2" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="3" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="4" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                    <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-rating-star" data-rating="5" height="24" width="24" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>
                                </div>
                            </div>
                            
                            <div class="cf-form-group">
                                <label>¿Con quién viajaste? *</label>
                                <select name="travel_companion" required>
                                    <option value="">Selecciona una opción</option>
                                    <option value="solo">Solo</option>
                                    <option value="en_pareja">En pareja</option>
                                    <option value="en_familia">En familia</option>
                                    <option value="con_amigos">Con amigos</option>
                                </select>
                            </div>
                            
                            <div class="cf-form-group">
                                <label>Idioma de tu comentario *</label>
                                <select name="language" required>
                                    <option value="">Selecciona idioma</option>
                                    <option value="es">Español</option>
                                    <option value="en">English</option>
                                    <option value="de">Deutsch</option>
                                    <option value="fr">Français</option>
                                    <option value="pt">Português</option>
                                    <option value="it">Italiano</option>
                                    <option value="nl">Nederlands</option>
                                    <option value="zh">中文</option>
                                    <option value="ja">日本語</option>
                                    <option value="ko">한국어</option>
                                    <option value="he">עברית</option>
                                    <option value="otros">' . cf_trans('others') . '</option>
                                </select>
                            </div>
                            
                            <div class="cf-form-group">
                                <label>Título de tu comentario *</label>
                                <input type="text" name="title" placeholder="' . cf_trans('title_placeholder') . '" required>
                            </div>
                            
                            <div class="cf-form-group">
                                <label>Comentario *</label>
                                <textarea name="content" id="cf-content-guest" placeholder="Cuéntanos sobre tu experiencia..." required rows="4" maxlength="2000"></textarea>
                                <small>Máximo 2000 caracteres. <span id="cf-content-count-guest" style="color: #6c757d;">0</span> caracteres usados.</small>
                            </div>
                            
                            <div class="cf-form-group">
                                <label for="images-step1">Subir fotos (opcional)</label>
                                <div class="cf-custom-file-upload">
                                    <input type="file" id="images-step1" name="images[]" multiple accept="image/*" style="display: none;">
                                    <button type="button" class="cf-file-button" onclick="document.getElementById(\'images-step1\').click()">
                                        ' . cf_trans('choose_files') . '
                                    </button>
                                    <span class="cf-file-name">' . cf_trans('no_file_chosen') . '</span>
                                </div>
                            </div>
                            
                            <div class="cf-form-group">
                                <button type="button" id="cf-continue-btn" class="cf-btn cf-btn-primary">Continuar</button>
                            </div>
                        </form>
                    </div>
                    
                    <script>
                    // Detectar cuando el usuario inicia sesión exitosamente
                    (function() {
                        // Escuchar evento de login exitoso desde loginfree
                        window.addEventListener("cf_user_logged_in", function(e) {
                            jQuery("#cf-comment-modal").fadeOut(300);
                            setTimeout(function() {
                                location.reload();
                            }, 500);
                        });
                        
                        // También verificar periódicamente el estado de login
                        var checkLoginInterval = setInterval(function() {
                            fetch("' . admin_url('admin-ajax.php') . '", {
                                method: "POST",
                                headers: {
                                    "Content-Type": "application/x-www-form-urlencoded",
                                },
                                body: "action=check_user_login_status"
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.data.logged_in) {
                                    clearInterval(checkLoginInterval);
                                    jQuery("#cf-comment-modal").fadeOut(300);
                                    setTimeout(function() {
                                        location.reload();
                                    }, 500);
                                }
                            });
                        }, 2000); // Verificar cada 2 segundos
                        
                        // Limpiar interval al cerrar el modal
                        jQuery(".cf-modal-close, .cf-modal-backdrop").on("click", function() {
                            clearInterval(checkLoginInterval);
                        });
                    })();
                    </script>
                </div>
            </div>';
        }
    }
    
    private function get_css_js() {
        return '<!-- SweetAlert2 CDN -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.js"></script>
        <!-- Google Identity Services cargado por loginfree -->
        
        <style>
        .comentarios-free-container { margin: 20px auto; padding: 10px; font-family: nunito; }
        .cf-header {padding-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .cf-title { font-size: 24px; margin: 0; color: #333; }
        .cf-btn { padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px; text-decoration: none; display: inline-block; text-align: center; }
        .cf-btn-primary { background: #007cba; color: white; }
        .cf-btn-primary:hover { background: #005a87; }
        .cf-btn-primary:disabled { background: #6c757d; cursor: not-allowed; opacity: 0.7; }
        
        /* Loader para el botón */
        .cf-btn-loader {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #fff;
            border-radius: 50%;
            animation: cf-spin 0.6s linear infinite;
            margin-right: 8px;
            vertical-align: middle;
        }
        
        @keyframes cf-spin {
            to { transform: rotate(360deg); }
        }
        
        /* Botón Ver más */
        .cf-btn-secondary {
            background: #6c757d !important;
            color: white !important;
            padding: 12px 24px;
            font-size: 15px;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .cf-btn-secondary:hover {
            background: #5a6268 !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .cf-btn-secondary:disabled {
            background: #e9ecef !important;
            color: #6c757d !important;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .cf-load-more-container {
            animation: fadeInUp 0.5s ease;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Estilos para botón "Editar mi Reseña" */
        .cf-btn-edit { 
            background: #dc3545 !important; 
            color: white !important; 
            text-decoration: none !important;
        }
        
        .cf-btn-edit:hover { 
            background: #c82333 !important; 
            color: white !important;
            text-decoration: none !important;
        }
        
        .cf-btn-edit:visited { 
            background: #dc3545 !important; 
            color: white !important;
            text-decoration: none !important;
        }
        
        .cf-btn-edit:active { 
            background: #bd2130 !important; 
            color: white !important;
            text-decoration: none !important;
        }
        
        /* Estilos para barra de filtros y estadísticas */
        .cf-filters-stats-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #dee2e6;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 25px;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .cf-filters-section {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .cf-filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .cf-filter-group label {
            font-size: 13px;
            font-weight: 600;
            color: #495057;
            margin: 0;
        }
        
        .cf-filter-select {
            padding: 8px 12px;
            border: 2px solid #ced4da;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            color: #495057;
            min-width: 150px;
            transition: all 0.3s ease;
        }
        
        .cf-filter-select:focus {
            border-color: #007cba;
            outline: none;
            box-shadow: 0 0 0 3px rgba(0, 124, 186, 0.1);
        }
        
       .cf-total-comments
       {
         margin-right: 4px;
        }
        
        .cf-stats-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
        }
        
        .cf-stats-summary {
            text-align: center;
        }
        
        .cf-rating-summary {
            display: flex;
            align-items: baseline;
            gap: 3px;
            font-size: 16px;
        }
        
        .cf-avg-rating {
            font-size: 18px;
            font-weight: bold;
            color: #ff8110;
        }
        
        .cf-rating-separator {
            font-size: 18px;
            color: #ff8110;
            font-weight: bold;
        }
        
        .cf-max-rating {
            font-size: 18px;
            color: #ff8110;
            font-weight: bold;
        }
        
        .cf-total-comments {
            font-size: 14px;
            color: #495057;
            font-weight: bold;
        }
        
        .cf-visual-rating {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        .startsp .cf-star
        {
           width: 24px !important;
            height: 20px !important;
        }
        
        .cf-stars-display {
            display: flex;
            align-items: center;
            gap: 2px;
        }
        
        .cf-star {
            width: 16px;
            height: 16px;
            display: inline-block;
        }
        
        .cf-star-full {
            color: #ff8110;
            fill: #ff8110;
        }
        
        .cf-star-half {
            color: #fbbf24;
            fill: #fbbf24;
        }
        
        .cf-star-empty {
            color: #d1d5db;
            fill: #d1d5db;
        }
        
        .cf-rating-number {
            margin-left: 8px;
            font-size: 13px;
            color: #6c757d;
            font-weight: 500;
        }
        
        /* Responsive para dispositivos móviles */
        @media (max-width: 768px) {
            .cf-filters-stats-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .cf-filters-section {
                justify-content: center;
                width: 100%;
            }
            
            .cf-filter-group {
                align-items: center;
            }
            
            .cf-stats-section {
                width: 100%;
            }
        }
        
        .cf-modal { position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important; background: rgba(0,0,0,0.5) !important; z-index: 9999 !important; display: none; }
        .cf-modal-backdrop { position: absolute; top: 0; left: 0; width: 100%; height: 100%; }
        .cf-modal-content { position: absolute !important; top: 50% !important; left: 50% !important; transform: translate(-50%, -50%) !important; background: white !important; padding: 0 !important; border-radius: 12px !important; max-width: 500px !important; width: 90% !important; max-height: 80vh !important; overflow: hidden !important; box-shadow: 0 10px 30px rgba(0,0,0,0.3) !important; z-index: 10000 !important; }
        .cf-modal-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 20px 30px;
            background: linear-gradient(135deg, #ff8110 0%, #ff6b00 100%);
            margin: 0;
        }
        .cf-modal-header h3 {
            margin: 0;
            color: white;
            font-size: 20px;
            font-weight: 600;
        }
        .cf-modal-close { 
            background: rgba(255, 255, 255, 0.2); 
            border: none; 
            font-size: 24px; 
            cursor: pointer; 
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .cf-modal-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }
        .cf-modal-content form {
            padding: 30px;
            overflow-y: auto;
            max-height: calc(80vh - 72px);
        }
        
        /* Estilos para integrar loginfree en el modal */
        .cf-auth-container {
            margin: 20px;
        }
        
        /* Tabs de autenticación (Gmail/Email) */
        .cf-auth-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        
        .cf-auth-tab-btn {
            flex: 1;
            padding: 12px 20px;
            background: white;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #666;
            transition: all 0.3s ease;
        }
        
        .cf-auth-tab-btn:hover {
            color: #ff8110;
            background: #fff5ed;
        }
        
        .cf-auth-tab-btn.active {
            color: #ff8110;
            border-bottom-color: #ff8110;
            font-weight: 600;
        }
        
        .cf-auth-tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        
        .cf-auth-tab-content.active {
            display: block;
        }
        
        .cf-btn-link:hover {
            opacity: 0.8;
        }
        
        .cf-auth-container .arp-form-container,
        .cf-auth-container .arp-tabs {
            margin: 0;
        }
        
        .cf-auth-container .arp-tab-button {
            background: white;
            border: 1px solid #ddd;
            padding: 12px 20px;
            border-radius: 5px 5px 0 0;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .cf-auth-container .arp-tab-button.active {
            background: #ff8110;
            color: white;
            border-color: #ff8110;
        }
        
        .cf-auth-container button[type="submit"],
        .cf-auth-container .arp-submit-btn {
            background: #ff8110 !important;
            color: white !important;
            border: none !important;
            padding: 12px 24px !important;
            border-radius: 5px !important;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        
        .cf-auth-container button[type="submit"]:hover,
        .cf-auth-container .arp-submit-btn:hover {
            background: #e67310 !important;
        }
        
        .cf-auth-container input[type="text"],
        .cf-auth-container input[type="email"],
        .cf-auth-container input[type="password"] {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        
        .cf-auth-container input:focus {
            border-color: #ff8110;
            outline: none;
            box-shadow: 0 0 0 2px rgba(255, 129, 16, 0.1);
        }
        
        .cf-form-group { margin-bottom: 20px; }
        .cf-form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .cf-form-group input, .cf-form-group textarea, .cf-form-group select { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; box-sizing: border-box; }
        .cf-form-group input:focus, .cf-form-group textarea:focus, .cf-form-group select:focus { border-color: #007cba; outline: none; }
        
        /* Custom file upload */
        .cf-custom-file-upload { display: flex; align-items: center; gap: 10px; }
        .cf-file-button {
            padding: 10px 20px;
            background: #007cba;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background 0.3s;
        }
        .cf-file-button:hover { background: #005a8c; }
        .cf-file-name {
            color: #666;
            font-size: 14px;
            flex: 1;
        }
        
        .cf-rating-input { margin: 10px 0; display: flex; gap: 5px; }
        .cf-rating-star { 
            width: 24px; 
            height: 24px; 
            cursor: pointer; 
            color: #d1d5db;
            fill: #d1d5db;
            transition: all 0.2s ease;
        }
        .cf-rating-star:hover, 
        .cf-rating-star.active { 
            color: #ff8110;
            fill: #ff8110;
            transform: scale(1.1);
        }
        
        .cf-comments-list { margin-top: 20px; }
        .cf-comment { border: 1px solid #eee; padding: 10px 10px 0px 10px; margin-bottom: 20px; border-radius: 8px; background: #fafafa; }
        .cf-comment-header { display: flex; justify-content: space-between; margin-bottom: 15px; align-items: center; }
        .cf-user-info { display: flex; align-items: center; }
        .cf-user-avatar { width: 50px; height: 50px; background: #007cba; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: bold; }
        .cf-user-details h4 { margin: 0; color: #333; }
        .cf-user-meta { font-size: 12px; color: #666; margin-top: 5px; }
        .cf-travel-meta { font-size: 11px; color: #007cba; margin-top: 3px; font-weight: 500; }
        
        .cf-stars { display: flex; gap: 2px; }
        .cf-star { width: 16px; height: 16px; display: inline-block; }
        .cf-star-filled { color: #fbbf24; fill: #fbbf24; }
        .cf-star-empty { color: #d1d5db; fill: #d1d5db; }
        
        .cf-comment-content h5 { margin: 0 0 0 0; color: #333; font-family: nunito; }
        .cf-comment-content p { margin: 0; color: #666; line-height: 1.5; }
        
        .cf-no-comments { text-align: center; padding: 40px; color: #666; }
        
        /* Estilos para Respuestas del Administrador */
        .cf-admin-response {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border: 2px solid #28a745;
            border-radius: 12px;
            margin-top: 1px;
            padding: 0;
            overflow: hidden;
            animation: slideInResponse 0.5s ease-out;
        }
        
        .cf-admin-response-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 12px 15px;
            margin: 0;
            display: flex;
            align-items: center;
            font-weight: 600;
        }
        
        .cf-admin-badge {
            font-size: 1.1em;
            font-weight: 600;
            color: #333;
            font-family: nunito;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .cf-admin-icon {
            width: 16px;
            height: 16px;
            border-radius: 3px;
            vertical-align: middle;
        }
        
        .cf-admin-response-content {
            padding: 0px 7px 7px 7px;
            background: white;
            color: #333;
            line-height: 1.6;
           
        }
        
        .cf-admin-response-content p {
            margin: 0;
            color: #666;
        }
        
        @keyframes slideInResponse {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Estilos para el flujo de dos pasos */
        .cf-step { 
            transition: all 0.3s ease; 
        }
        
        #cf-step-2 h4 {
            color: #007cba;
            margin: 0 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #007cba;
        }
        
        /* Estilos para las imágenes de comentarios */
        .cf-comment-images {
            margin-top: 1px;
            padding-top: 1px;
            
        }
        
        .cf-images-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .cf-image-item {
            flex: 0 0 auto;
        }
        
        .cf-comment-image {
            width: 100px;
            height: 100px;
            max-height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .cf-comment-image:hover {
            border-color: #007cba;
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 124, 186, 0.2);
        }
        
        @media (max-width: 600px) {
            .cf-header { flex-direction: column; gap: 10px; }
            .cf-modal-content { width: 95%; padding: 20px; }
            .cf-comment-header { flex-direction: column; align-items: start; gap: 10px; }
            
            /* Imágenes más pequeñas en móviles */
            .cf-comment-image {
                width: 80px;
                max-height: 80px;
            }
        }
        
        /* Estilos para autocompletado de países */
        .cf-country-autocomplete-container {
            position: relative;
            width: 100%;
        }
        
        .cf-country-input {
            width: 100% !important;
            padding: 12px !important;
            border: 1px solid #ddd !important;
            border-radius: 4px !important;
            font-size: 14px !important;
            box-sizing: border-box !important;
            background: white !important;
        }
        
        .cf-country-input:focus {
            border-color: #007cba !important;
            outline: none !important;
            box-shadow: 0 0 0 2px rgba(0, 124, 186, 0.1) !important;
        }
        
        .cf-country-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #007cba;
            border-top: none;
            border-radius: 0 0 6px 6px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .cf-country-option {
            padding: 10px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: background-color 0.2s;
        }
        
        .cf-country-option:hover,
        .cf-country-option.cf-highlighted {
            background-color: #f8f9fa;
        }
        
        .cf-country-option:last-child {
            border-bottom: none;
        }
        
        .cf-country-flag-option {
            font-size: 16px;
            width: 20px;
            text-align: center;
        }
        .bodydesp
        {
        border: 1px solid #f4e6e6;
            border-radius: 10px;
            background-color: white;
        }
            .test
            {
                padding: 7px;
            }
        .cf-country-name-option {
            flex: 1;
            font-size: 14px;
            color: #333;
        }
        
        /* SweetAlert2 z-index fix - aparecer por encima de todo */
        .swal2-container {
            z-index: 99999 !important;
        }
        
        .swal2-popup {
            z-index: 100000 !important;
            font-family: Arial, sans-serif !important;
        }
        
        /* Personalización para que haga juego con el diseño del plugin */
        .swal2-title {
            color: #333 !important;
            font-weight: 600 !important;
        }
        
        .swal2-content {
            color: #555 !important;
        }
        
        .swal2-confirm {
            border-radius: 5px !important;
        }
        
        /* ========================================
           ESTILOS PARA LOGINFREE DENTRO DEL MODAL
           ======================================== */
        
        /* Contenedor principal del formulario de loginfree */
        #arp-registration-container {
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
            margin: 0;
        }
        
        /* Tabs de Loginfree */
        .arp-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .arp-tab-button {
            flex: 1;
            padding: 12px 16px;
            background: #f8f9fa;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 14px;
            font-weight: 500;
        }
        
        .arp-tab-button:hover {
            background: #e9ecef;
        }
        
        .arp-tab-button.active {
            background: #fff;
            border-bottom-color: #ff8110;
            color: #ff8110;
        }
        
        /* Contenido de los tabs */
        .arp-tab-content {
            display: none !important;
            animation: fadeIn 0.3s ease;
        }
        
        .arp-tab-content.active {
            display: block !important;
        }
        
        /* Formularios de loginfree */
        .arp-form-group {
            margin-bottom: 20px;
        }
        
        .arp-form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .arp-form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .arp-form-group input:focus {
            outline: none;
            border-color: #ff8110;
            box-shadow: 0 0 0 2px rgba(255, 129, 16, 0.1);
        }
        
        .arp-submit-btn {
            width: 100%;
            padding: 12px;
            background: #ff8110;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .arp-submit-btn:hover {
            background: #e06f00;
        }
        
        .arp-submit-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        /* Botón de Google */
        #gmail-signin-button {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 20px 0;
            min-height: 40px;
        }
        
        /* Botones de link para cambiar entre modos */
        .arp-link-btn {
            background: none;
            border: none;
            color: #ff8110;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            text-decoration: underline;
            padding: 0;
            transition: color 0.3s ease;
        }
        
        .arp-link-btn:hover {
            color: #e06f00;
        }
        
        /* Enlace de recuperación de contraseña */
        .arp-forgot-password {
            background: none;
            border: none;
            color: #666;
            font-size: 13px;
            text-decoration: none;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        
        .arp-forgot-password:hover {
            color: #ff8110 !important;
            text-decoration: underline !important;
        }
        
        /* Mensajes de ayuda */
        .arp-help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
            display: block;
        }
        
        /* Mensajes de resultado */
        .arp-message {
            padding: 12px;
            margin: 15px 0;
            border-radius: 4px;
            font-weight: 500;
        }
        
        .arp-message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .arp-message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Animación de carga */
        .arp-loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #ff8110;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        </style>
        
        <script>
        // ========================================
        // FUNCIÓN GLOBAL PARA TABS DE LOGINFREE
        // ========================================
        function openTab(evt, tabName) {
            var i, tabcontent, tabbuttons;
            
            // Ocultar todo el contenido de tabs
            tabcontent = document.getElementsByClassName("arp-tab-content");
            for (i = 0; i < tabcontent.length; i++) {
                tabcontent[i].classList.remove("active");
            }
            
            // Remover clase active de todos los botones
            tabbuttons = document.getElementsByClassName("arp-tab-button");
            for (i = 0; i < tabbuttons.length; i++) {
                tabbuttons[i].classList.remove("active");
            }
            
            // Mostrar el tab actual y marcar el botón como activo
            document.getElementById(tabName).classList.add("active");
            if (evt && evt.currentTarget) {
                evt.currentTarget.classList.add("active");
            }
        }
        
        jQuery(document).ready(function($) {
            var currentRating = 0;
            var reviewData = {}; // Almacenar datos del paso 1
            
            // === CUSTOM FILE UPLOAD ===
            $("#images, #images-step1").on("change", function() {
                var fileCount = this.files.length;
                var $fileNameSpan = $(this).siblings(".cf-file-name");
                
                if (fileCount > 0) {
                    $fileNameSpan.text(fileCount + " ' . cf_trans('files_selected') . '");
                } else {
                    $fileNameSpan.text("' . cf_trans('no_file_chosen') . '");
                }
            });
            
            // === TABS DE AUTENTICACIÓN ===
            // Las tabs ahora son manejadas por el shortcode de loginfree
            // que genera su propia estructura de tabs (Gmail/Email)
            
            // === GOOGLE SIGN-IN ===
            // La inicialización de Google Identity Services ahora la maneja loginfree
            // El botón de Google se genera automáticamente dentro del shortcode
            
            // === AUTENTICACIÓN VÍA LOGINFREE SHORTCODE ===
            // La autenticación ahora se maneja completamente a través del shortcode de loginfree
            // con los parámetros: no_redirect="true" y modal_mode="true"
            // Esto incluye tanto login/registro por email como Google OAuth
            
            // Configurar variables AJAX si no están definidas
            if (typeof comentarios_ajax === "undefined") {
                window.comentarios_ajax = {
                    ajax_url: "' . admin_url('admin-ajax.php') . '",
                    nonce: "comentarios_nonce"
                };
            }
            
            // Configurar SweetAlert con z-index alto
            if (typeof Swal !== "undefined") {
                // Override default SweetAlert settings
                Swal.mixin({
                    customClass: {
                        container: "swal2-container-high-z"
                    }
                });
                
                // Agregar estilos dinámicamente
                if (!document.getElementById("swal2-high-z-styles")) {
                    var style = document.createElement("style");
                    style.id = "swal2-high-z-styles";
                    style.innerHTML = `
                        .swal2-container-high-z {
                            z-index: 100001 !important;
                        }
                        .swal2-container {
                            z-index: 100001 !important;
                        }
                        .swal2-popup {
                            z-index: 100002 !important;
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
            
            // Función auxiliar para mostrar alertas con fallback
            function showAlert(config) {
                if (typeof Swal !== "undefined") {
                    return Swal.fire(config);
                } else {
                    // Fallback a alert() simple
                    var message = config.title;
                    if (config.text) {
                        message += "\n\n" + config.text;
                    }
                    alert(message);
                    
                    // Simular el comportamiento de then() para compatibilidad
                    return {
                        then: function(callback) {
                            if (callback && typeof callback === "function") {
                                callback({isConfirmed: true});
                            }
                        }
                    };
                }
            }
            
            // 🔄 SISTEMA DE RESTAURACIÓN POST-LOGIN
            // Verificar si el usuario regresó después del login y debe continuar con la reseña
            function checkAndRestoreReview() {
                // Verificar parámetro en URL
                var urlParams = new URLSearchParams(window.location.search);
                var continueReview = urlParams.get("cf_continue_review");
                
                // Verificar datos guardados en sessionStorage
                var savedData = sessionStorage.getItem("cf_review_data");
                var savedState = sessionStorage.getItem("cf_modal_state");
                
                if (continueReview === "1" && savedData && savedState === "step-2") {
                    // Parsear datos guardados
                    reviewData = JSON.parse(savedData);
                    
                    // Abrir modal automáticamente
                    $("#cf-comment-modal").fadeIn(300);
                    $("body").css("overflow", "hidden");
                    
                    // Restaurar datos del paso 1
                    $("#cf-rating-value").val(reviewData.rating);
                    $("select[name=\"travel_companion\"]").val(reviewData.travel_companion);
                    $("input[name=\"title\"]").val(reviewData.title);
                    $("textarea[name=\"content\"]").val(reviewData.content);
                    
                    // Restaurar rating visual
                    currentRating = parseInt(reviewData.rating);
                    
                    // SVG de estrella llena y vacía
                    var starFilled = \'<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"></path>\';
                    var starEmpty = \'<path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path>\';
                    
                    $(".cf-rating-star").each(function() {
                        var starRating = parseInt($(this).data("rating"));
                        if (starRating <= currentRating) {
                            $(this).html(starFilled).addClass("active");
                        } else {
                            $(this).html(starEmpty).removeClass("active");
                        }
                    });
                    
                    // Ir directamente al paso 2 (datos personales) ya que el usuario está logueado
                    $("#cf-step-1").hide();
                    $("#cf-step-2").show();
                    $("#cf-registration-section").hide();
                    $("#cf-personal-data").show();
                    $("#cf-modal-title").text("👤 Información Personal");
                    
                    // Pre-llenar datos del usuario desde el servidor
                    setTimeout(fillUserDataFromServer, 100);
                    
                    // Limpiar URL y storage
                    window.history.replaceState({}, document.title, window.location.pathname);
                    sessionStorage.setItem("cf_modal_state", "step-3");
                }
            }
            
            // Ejecutar verificación de restauración
            checkAndRestoreReview();
            
            // 🎯 LISTENER PARA LOGIN SIN REFRESH (desde LoginFree)
            window.addEventListener("cf_user_logged_in", function(event) {
                
                // Obtener datos del usuario si están disponibles
                var userData = event.detail.user_data;
                
                // Pre-llenar formulario con datos del usuario si están disponibles
                if (userData) {
                    var displayName = userData.name || (userData.first_name + " " + userData.last_name).trim();
                    
                    if (displayName && displayName !== " " && displayName !== "") {
                        $("#cf-personal-data input[name=\"author_name\"]").val(displayName);
                    } else {
                    }
                    
                    if (userData.email) {
                        $("#cf-personal-data input[name=\"author_email\"]").val(userData.email);
                    } else {
                    }
                } else {
                    
                    // Si no hay datos, intentar obtenerlos del servidor como respaldo
                    setTimeout(function() {
                        fillUserDataFromServer();
                    }, 500);
                }
                
                // Ocultar sección de registro y mostrar datos personales
                $("#cf-registration-section").fadeOut(200, function() {
                    $("#cf-personal-data").fadeIn(200);
                    $("#cf-modal-title").text("👤 Información Personal");
                });
                
                // Limpiar estado de sessionStorage ya que el usuario está logueado
                sessionStorage.setItem("cf_modal_state", "step-3");
            });
            
            // 🔄 FUNCIÓN AUXILIAR: Pre-llenar datos del usuario logueado
            function fillUserDataFromServer() {
                // Hacer AJAX para obtener datos del usuario actual
                $.ajax({
                    url: comentarios_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "check_user_login_status"
                    },
                    success: function(response) {
                        if (response.success && response.data.logged_in && response.data.user) {
                            var user = response.data.user;
                            $("#cf-personal-data input[name=\"author_name\"]").val(user.name);
                            $("#cf-personal-data input[name=\"author_email\"]").val(user.email);
                        }
                    }
                });
            }
            
            // === LISTENER PARA EVENTO DE LOGIN EXITOSO DESDE LOGINFREE ===
            window.addEventListener("cf_user_logged_in", function(event) {
                
                if (event.detail && event.detail.success) {
                    
                    // Dar un momento para que WordPress actualice la sesión
                    setTimeout(function() {
                        // Recargar la página para actualizar el estado de autenticación
                        location.reload();
                    }, 1000);
                }
            });
            
            // Abrir modal
            $(document).on("click", "#cf-add-comment-btn", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Verificar que el modal existe
                if ($("#cf-comment-modal").length === 0) {
                    location.reload();
                    return;
                }
                
                $("#cf-comment-modal").fadeIn(300);
                $("body").css("overflow", "hidden");
                
                // Resetear al paso correcto según el estado de login
                if ($("#cf-step-1").length > 0) {
                    // Usuario NO logueado - mostrar paso 1 (login)
                    $("#cf-step-1").show();
                    $("#cf-step-2").hide();
                    $("#cf-modal-title").text("Iniciar Sesión");
                    
                    // Reinicializar botón de Google
                    setTimeout(function() {
                        if (typeof google !== "undefined" && google.accounts && typeof arp_ajax !== "undefined" && arp_ajax.google_client_id) {
                            var googleButtonContainer = document.getElementById("gmail-signin-button");
                            if (googleButtonContainer) {
                                googleButtonContainer.innerHTML = "";
                                try {
                                    google.accounts.id.renderButton(googleButtonContainer, {
                                        type: "standard",
                                        theme: "outline",
                                        size: "large",
                                        text: "signup_with",
                                        locale: "es",
                                        shape: "pill"
                                    });
                                } catch (error) {
                                    console.error("Error al renderizar botón de Google:", error);
                                }
                            }
                        }
                    }, 300);
                } else {
                    // Usuario YA logueado - mostrar directamente el formulario
                    $("#cf-modal-title").text(cfTranslations.write_review);
                    
                    // Inicializar autocompletado de países para usuario logueado
                    setTimeout(function() {
                        initializeCountryAutocomplete();
                    }, 100);
                }
            });
            
            // Cerrar modal
            $(document).on("click", ".cf-modal-close, .cf-modal-backdrop", function(e) {
                e.preventDefault();
                // Limpiar datos guardados si se cierra manualmente
                if (sessionStorage.getItem("cf_review_data")) {
                    var confirmClose = confirm("¿Estás seguro de cerrar? Se perderán los datos de tu reseña.");
                    if (!confirmClose) return;
                    
                    sessionStorage.removeItem("cf_review_data");
                    sessionStorage.removeItem("cf_modal_state");
                }
                
                $("#cf-comment-modal").fadeOut(300);
                $("body").css("overflow", "auto");
                
                // Reset modal al estado inicial
                if ($("#cf-step-1").length > 0) {
                    $("#cf-step-1").show();
                    $("#cf-step-2").hide();
                }
                
                // Reset form data
                reviewData = {};
                currentRating = 0;
            });
            
            // Rating stars
            $(document).on("click", ".cf-rating-star", function() {
                currentRating = parseInt($(this).data("rating"));
                $("#cf-rating-value").val(currentRating);
                
                // SVG de estrella llena
                var starFilled = \'<path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"></path>\';
                
                // SVG de estrella vacía
                var starEmpty = \'<path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path>\';
                
                // Actualizar todas las estrellas
                $(".cf-rating-star").each(function() {
                    var starRating = parseInt($(this).data("rating"));
                    if (starRating <= currentRating) {
                        // Estrella llena
                        $(this).html(starFilled).addClass("active");
                    } else {
                        // Estrella vacía
                        $(this).html(starEmpty).removeClass("active");
                    }
                });
            });
            
            // Botón Continuar (Paso 1 -> Paso 2)
            $(document).on("click", "#cf-continue-btn", function(e) {
                e.preventDefault();
                
                // Validar campos del paso 1
                var rating = $("#cf-rating-value").val();
                var companion = $("select[name=\"travel_companion\"]").val();
                var language = $("select[name=\"language\"]").val();
                var title = $("input[name=\"title\"]").val();
                var content = $("textarea[name=\"content\"]").val();
                
                if (!rating || rating == "0") {
                    showAlert({
                        icon: "warning",
                        title: "Campo requerido",
                        text: "Por favor selecciona una calificación",
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#007cba"
                    });
                    return;
                }
                
                if (!companion) {
                    showAlert({
                        icon: "warning",
                        title: "Campo requerido",
                        text: "Por favor selecciona con quién viajaste",
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#007cba"
                    });
                    return;
                }
                
                if (!language) {
                    showAlert({
                        icon: "warning",
                        title: "Campo requerido",
                        text: "Por favor selecciona el idioma de tu comentario",
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#007cba"
                    });
                    return;
                }
                
                if (!title.trim()) {
                    showAlert({
                        icon: "warning",
                        title: "Campo requerido",
                        text: "Por favor ingresa un título",
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#007cba"
                    });
                    return;
                }
                
                if (!content.trim()) {
                    showAlert({
                        icon: "warning",
                        title: "Campo requerido",
                        text: "Por favor ingresa tu comentario",
                        confirmButtonText: "Entendido",
                        confirmButtonColor: "#007cba"
                    });
                    return;
                }
                
                // Guardar datos del paso 1 en memoria Y en sessionStorage para persistencia
                reviewData = {
                    rating: rating,
                    travel_companion: companion,
                    language: language,
                    title: title,
                    content: content,
                    post_id: $("input[name=\"post_id\"]").val()
                };
                
                // Guardar en sessionStorage para persistir a través de refreshes
                sessionStorage.setItem("cf_review_data", JSON.stringify(reviewData));
                sessionStorage.setItem("cf_modal_state", "step-2");
                
                // Cambiar al paso 2
                $("#cf-step-1").fadeOut(200, function() {
                    $("#cf-step-2").fadeIn(200);
                    $("#cf-modal-title").text("Completar Registro");
                });
            });
            
            // Botón "Registrarse"
            $(document).on("click", "#cf-show-registration", function() {
                // Simular que se completó el registro
                $("#cf-registration-section").fadeOut(200, function() {
                    $("#cf-personal-data").fadeIn(200);
                    $("#cf-modal-title").text("👤 Información Personal");
                });
            });
            
            // Enviar formulario final (usuarios logueados)
            $(document).on("submit", "#cf-comment-form", function(e) {
                e.preventDefault();
                
                // Validar imágenes antes de enviar
                var fileInput = document.getElementById("images");
                if (fileInput && fileInput.files.length > 0) {
                    var maxSize = 5 * 1024 * 1024; // 5MB
                    var maxFiles = 5;
                    var allowedTypes = ["image/jpeg", "image/jpg", "image/png", "image/gif", "image/webp"];
                    var errors = [];
                    
                    if (fileInput.files.length > maxFiles) {
                        errors.push("Máximo " + maxFiles + " imágenes permitidas");
                    }
                    
                    for (var i = 0; i < fileInput.files.length; i++) {
                        var file = fileInput.files[i];
                        var sizeMB = (file.size / 1024 / 1024).toFixed(2);
                        
                        // Validar tamaño
                        if (file.size > maxSize) {
                            errors.push(file.name + ": Muy grande (" + sizeMB + "MB, máximo 5MB)");
                        }
                        
                        // Validar tipo
                        if (!allowedTypes.includes(file.type)) {
                            errors.push(file.name + ": Formato no permitido (usar JPG, PNG, GIF o WebP)");
                        }
                    }
                    
                    if (errors.length > 0) {
                        showAlert({
                            title: "Error en las imágenes",
                            text: errors.join("\\n"),
                            icon: "error",
                            confirmButtonText: "Entendido",
                            confirmButtonColor: "#d33"
                        });
                        return false;
                    }
                }
                
                // Mostrar loader en el botón
                var $submitBtn = $("#cf-submit-btn");
                var originalText = $submitBtn.html();
                var publishingText = cfTranslations.publishing || "Publicando...";
                $submitBtn.prop("disabled", true).html("<span class=\"cf-btn-loader\"></span>" + publishingText);
                
                var formData = new FormData(this);
                formData.set("action", "comentarios_submit");
                
                // Obtener código del país seleccionado
                var countryInput = $("#cf-country-input-logged");
                var countryCode = countryInput.data("country-code") || "";
                formData.set("country", countryCode);
                
                $.ajax({
                    url: comentarios_ajax.ajax_url,
                    type: "POST",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        // Restaurar botón
                        $submitBtn.prop("disabled", false).html(originalText);
                        
                        if (response.success) {
                            showAlert({
                                title: cfTranslations.js_success_title,
                                text: cfTranslations.js_success_text,
                                icon: "success",
                                confirmButtonText: cfTranslations.js_view_review,
                                confirmButtonColor: "#007cba"
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    location.reload();
                                }
                            });
                        } else {
                            // Verificar si es un comentario duplicado - redirigir automáticamente
                            if (response.data && response.data.duplicate_detected && response.data.redirect_to) {
                                window.location.href = response.data.redirect_to;
                            } else {
                                showAlert({
                                    title: "Oops...",
                                    text: response.data.message || "Ha ocurrido un error inesperado",
                                    icon: "error",
                                    confirmButtonText: "Intentar de nuevo",
                                    confirmButtonColor: "#d33"
                                });
                            }
                        }
                    },
                    error: function() {
                        // Restaurar botón en caso de error
                        $submitBtn.prop("disabled", false).html(originalText);
                        
                        showAlert({
                            title: "Error de conexión",
                            text: "No se pudo conectar con el servidor. Por favor, verifica tu conexión e inténtalo de nuevo.",
                            icon: "error",
                            confirmButtonText: "Reintentar",
                            confirmButtonColor: "#d33"
                        });
                    }
                });
            });
            
            // Enviar formulario final (usuarios no logueados - paso 2)
            $(document).on("submit", "#cf-personal-form", function(e) {
                e.preventDefault();
                
                // Combinar datos del paso 1 y paso 2
                var countryInput = $("#cf-country-input-guest");
                var countryCode = countryInput.data("country-code") || "";
                
                var finalData = {
                    action: "comentarios_submit",
                    post_id: reviewData.post_id,
                    rating: reviewData.rating,
                    travel_companion: reviewData.travel_companion,
                    language: reviewData.language,
                    title: reviewData.title,
                    content: reviewData.content,
                    author_name: $("input[name=\"author_name\"]").val(),
                    author_email: $("input[name=\"author_email\"]").val(),
                    country: countryCode
                };
                
                $.ajax({
                    url: comentarios_ajax.ajax_url,
                    type: "POST",
                    data: finalData,
                    success: function(response) {
                        if (response.success) {
                            // Limpiar sessionStorage al completar exitosamente
                            sessionStorage.removeItem("cf_review_data");
                            sessionStorage.removeItem("cf_modal_state");
                            
                            showAlert({
                                title: "¡Reseña Publicada!",
                                text: "Gracias por compartir tu experiencia. Tu reseña ya está visible para otros viajeros.",
                                icon: "success",
                                confirmButtonText: "¡Genial!",
                                confirmButtonColor: "#007cba",
                                timer: 3000,
                                timerProgressBar: true
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            // Verificar si es un comentario duplicado - redirigir automáticamente
                            if (response.data && response.data.duplicate_detected && response.data.redirect_to) {
                                window.location.href = response.data.redirect_to;
                            } else {
                                showAlert({
                                    title: "No se pudo publicar",
                                    text: response.data.message || "Ha ocurrido un error. Por favor inténtalo de nuevo.",
                                    icon: "warning",
                                    confirmButtonText: "Entendido",
                                    confirmButtonColor: "#f39c12"
                                });
                            }
                        }
                    },
                    error: function() {
                        showAlert({
                            title: "Error de conexión", 
                            text: "Parece que hay un problema con la conexión. Verifica tu internet e inténtalo nuevamente.",
                            icon: "error",
                            confirmButtonText: "Reintentar",
                            confirmButtonColor: "#e74c3c"
                        });
                    }
                });
            });
            
            // 🖼️ GALERÍA DE IMÁGENES CON NAVEGACIÓN
            var currentImageIndex = 0;
            var galleryImages = [];
            
            $(document).on("click", ".cf-comment-image", function(e) {
                e.preventDefault();
                
                // Obtener todas las imágenes del mismo comentario
                var $commentImages = $(this).closest(".cf-comment-images").find(".cf-comment-image");
                galleryImages = [];
                
                $commentImages.each(function(index) {
                    galleryImages.push({
                        url: $(this).attr("src"),
                        alt: $(this).attr("alt")
                    });
                    
                    // Detectar cuál imagen fue clickeada
                    if (this === e.target) {
                        currentImageIndex = index;
                    }
                });
                
                // Crear galería lightbox
                showGallery();
            });
            
            function showGallery() {
                var hasMultiple = galleryImages.length > 1;
                
                var lightboxHTML = "<div class=\"cf-image-lightbox\" style=\"position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.95); z-index: 99999; display: flex; align-items: center; justify-content: center;\">";
                
                // Botón cerrar
                lightboxHTML += "<button class=\"cf-lightbox-close\" style=\"position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 40px; height: 40px; font-size: 24px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.5); z-index: 100001; transition: all 0.3s;\">&times;</button>";
                
                // Botón anterior (solo si hay múltiples imágenes)
                if (hasMultiple) {
                    lightboxHTML += "<button class=\"cf-lightbox-prev\" style=\"position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 24px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.5); z-index: 100001; transition: all 0.3s;\">‹</button>";
                }
                
                // Botón siguiente (solo si hay múltiples imágenes)
                if (hasMultiple) {
                    lightboxHTML += "<button class=\"cf-lightbox-next\" style=\"position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(255,255,255,0.9); border: none; border-radius: 50%; width: 50px; height: 50px; font-size: 24px; font-weight: bold; cursor: pointer; box-shadow: 0 2px 10px rgba(0,0,0,0.5); z-index: 100001; transition: all 0.3s;\">›</button>";
                }
                
                // Contenedor de la imagen
                lightboxHTML += "<div class=\"cf-lightbox-content\" style=\"position: relative; max-width: 85%; max-height: 85vh; display: flex; flex-direction: column; align-items: center;\">";
                lightboxHTML += "<img class=\"cf-lightbox-image\" src=\"" + galleryImages[currentImageIndex].url + "\" alt=\"" + galleryImages[currentImageIndex].alt + "\" style=\"max-width: 100%; max-height: 80vh; object-fit: contain; border-radius: 8px; box-shadow: 0 8px 30px rgba(0,0,0,0.7); transition: opacity 0.3s ease;\">";
                
                // Contador (solo si hay múltiples imágenes)
                if (hasMultiple) {
                    lightboxHTML += "<div class=\"cf-lightbox-counter\" style=\"margin-top: 15px; color: white; font-size: 16px; text-shadow: 0 2px 4px rgba(0,0,0,0.5);\">" + (currentImageIndex + 1) + " / " + galleryImages.length + "</div>";
                }
                
                lightboxHTML += "</div></div>";
                
                var lightbox = $(lightboxHTML);
                
                // Agregar al body
                $("body").append(lightbox);
                $("body").css("overflow", "hidden");
                
                // Cerrar al hacer click en el fondo
                lightbox.on("click", function(e) {
                    if ($(e.target).hasClass("cf-image-lightbox")) {
                        closeLightbox();
                    }
                });
                
                // Cerrar con botón X
                $(".cf-lightbox-close").on("click", function(e) {
                    e.stopPropagation();
                    closeLightbox();
                }).hover(
                    function() { $(this).css({"background": "white", "transform": "scale(1.1)"}); },
                    function() { $(this).css({"background": "rgba(255,255,255,0.9)", "transform": "scale(1)"}); }
                );
                
                // Navegación anterior
                $(".cf-lightbox-prev").on("click", function(e) {
                    e.stopPropagation();
                    currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                    updateGalleryImage();
                }).hover(
                    function() { $(this).css({"background": "white", "transform": "translateY(-50%) scale(1.1)"}); },
                    function() { $(this).css({"background": "rgba(255,255,255,0.9)", "transform": "translateY(-50%) scale(1)"}); }
                );
                
                // Navegación siguiente
                $(".cf-lightbox-next").on("click", function(e) {
                    e.stopPropagation();
                    currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                    updateGalleryImage();
                }).hover(
                    function() { $(this).css({"background": "white", "transform": "translateY(-50%) scale(1.1)"}); },
                    function() { $(this).css({"background": "rgba(255,255,255,0.9)", "transform": "translateY(-50%) scale(1)"}); }
                );
                
                // Navegación con teclado
                $(document).on("keydown.lightbox", function(e) {
                    if (e.keyCode === 27) { // ESC
                        closeLightbox();
                    } else if (e.keyCode === 37 && hasMultiple) { // Flecha izquierda
                        currentImageIndex = (currentImageIndex - 1 + galleryImages.length) % galleryImages.length;
                        updateGalleryImage();
                    } else if (e.keyCode === 39 && hasMultiple) { // Flecha derecha
                        currentImageIndex = (currentImageIndex + 1) % galleryImages.length;
                        updateGalleryImage();
                    }
                });
            }
            
            function updateGalleryImage() {
                var $lightbox = $(".cf-image-lightbox");
                var $img = $lightbox.find(".cf-lightbox-image");
                var $counter = $lightbox.find(".cf-lightbox-counter");
                
                // Fade out
                $img.css("opacity", "0");
                
                setTimeout(function() {
                    $img.attr("src", galleryImages[currentImageIndex].url);
                    $img.attr("alt", galleryImages[currentImageIndex].alt);
                    $counter.text((currentImageIndex + 1) + " / " + galleryImages.length);
                    
                    // Fade in
                    $img.css("opacity", "1");
                }, 150);
            }
            
            function closeLightbox() {
                $(".cf-image-lightbox").remove();
                $("body").css("overflow", "auto");
                $(document).off("keydown.lightbox");
            }
            
            // ================================
            // FILTROS FRONTEND - CALIFICACIÓN E IDIOMA
            // ================================
            
            // Función para aplicar filtros
            function applyFilters() {
                var rating = $("#cf-filter-rating").val();
                var language = $("#cf-filter-language").val();
                
                // Si no hay filtros activos, usar filtrado local
                if ((!rating || rating === "all") && (!language || language === "all")) {
                    $(".cf-comment-item").show();
                    var totalComments = $(".cf-comment-item").length;
                    updateFilterStats(totalComments, totalComments);
                    return;
                }
                
                // Filtrado local para mejor rendimiento
                $(".cf-comment-item").each(function() {
                    var showComment = true;
                    
                    // Aplicar filtro por calificación
                    if (rating && rating !== "all") {
                        var commentRating = $(this).find(".cf-comment-rating .cf-rating-star.active").length;
                        if (commentRating != parseInt(rating)) {
                            showComment = false;
                        }
                    }
                    
                    // Aplicar filtro por idioma
                    if (language && language !== "all") {
                        var commentLanguage = $(this).data("language") || "";
                        if (commentLanguage !== language) {
                            showComment = false;
                        }
                    }
                    
                    if (showComment) {
                        $(this).show();
                    } else {
                        $(this).hide();
                    }
                });
                
                // Actualizar contador de comentarios visibles
                var visibleComments = $(".cf-comment-item:visible").length;
                var totalComments = $(".cf-comment-item").length;
                
                // Actualizar estadísticas
                updateFilterStats(visibleComments, totalComments);
            }
            
            // Función para actualizar estadísticas de filtros
            function updateFilterStats(visible, total) {
                // Esta función podría usarse para actualizar dinámicamente las estadísticas
                // Por ahora solo actualiza el conteo visual si existe
                if ($(".cf-stats-total").length) {
                    $(".cf-stats-total").text("(" + visible + " de " + total + " comentarios)");
                }
            }
            
            // Event listeners para filtros - se aplican automáticamente al cambiar
            $("#cf-filter-rating, #cf-filter-language").on("change", function() {
                applyFilters();
            });
            
            // Inicializar filtros al cargar la página
            $(document).ready(function() {
                // Agregar atributo data-language a comentarios existentes si no lo tienen
                $(".cf-comment-item").each(function() {
                    if (!$(this).data("language")) {
                        // Buscar el idioma en el contenido del comentario si existe
                        var languageText = $(this).find(".cf-comment-language").text();
                        if (languageText) {
                            var language = languageText.replace("Idioma: ", "").trim();
                            $(this).attr("data-language", language);
                        }
                    }
                });
            });
            
            // ================================
            // AUTOCOMPLETADO DE PAÍSES
            // ================================
            
            // Lista de países con códigos y banderas (traducidos)
            var currentLang = "' . ComentariosFree_Translations::get_current_language() . '";
            var countriesData = ' . ComentariosFree_Countries::get_countries_json(ComentariosFree_Translations::get_current_language()) . ';
            var selectedCountryCode = "";
            
            // Traducciones JavaScript
            var cfTranslations = ' . json_encode(ComentariosFree_Translations::get_js_strings(), JSON_UNESCAPED_UNICODE) . ';
            
            // Función para inicializar autocompletado de países
            function initializeCountryAutocomplete() {
                $(".cf-country-input").each(function() {
                    var input = $(this);
                    var dropdownId = input.attr("id") + "-dropdown";
                    var dropdown = $("#" + dropdownId.replace("-input", "-dropdown"));
                    
                    if (dropdown.length === 0) {
                        dropdown = input.siblings(".cf-country-dropdown");
                    }
                    
                    // Manejar input en el campo
                    input.on("input focus", function() {
                        var query = $(this).val().toLowerCase().trim();
                        showCountryDropdown(input, dropdown, query);
                    });
                    
                    // Ocultar dropdown al hacer clic fuera
                    $(document).on("click", function(e) {
                        if (!input.is(e.target) && !dropdown.is(e.target) && dropdown.has(e.target).length === 0) {
                            dropdown.hide();
                        }
                    });
                    
                    // Manejar teclas
                    input.on("keydown", function(e) {
                        var highlighted = dropdown.find(".cf-country-option.cf-highlighted");
                        var options = dropdown.find(".cf-country-option");
                        
                        if (e.keyCode === 40) { // Flecha abajo
                            e.preventDefault();
                            if (highlighted.length === 0) {
                                options.first().addClass("cf-highlighted");
                            } else {
                                highlighted.removeClass("cf-highlighted");
                                var next = highlighted.next(".cf-country-option");
                                if (next.length > 0) {
                                    next.addClass("cf-highlighted");
                                } else {
                                    options.first().addClass("cf-highlighted");
                                }
                            }
                        } else if (e.keyCode === 38) { // Flecha arriba
                            e.preventDefault();
                            if (highlighted.length === 0) {
                                options.last().addClass("cf-highlighted");
                            } else {
                                highlighted.removeClass("cf-highlighted");
                                var prev = highlighted.prev(".cf-country-option");
                                if (prev.length > 0) {
                                    prev.addClass("cf-highlighted");
                                } else {
                                    options.last().addClass("cf-highlighted");
                                }
                            }
                        } else if (e.keyCode === 13) { // Enter
                            e.preventDefault();
                            if (highlighted.length > 0) {
                                selectCountry(input, highlighted.data("code"), highlighted.find(".cf-country-name-option").text());
                                dropdown.hide();
                            }
                        } else if (e.keyCode === 27) { // Escape
                            dropdown.hide();
                        }
                    });
                });
            }
            
            // Mostrar dropdown con países filtrados
            function showCountryDropdown(input, dropdown, query) {
                var html = "";
                var matchCount = 0;
                
                Object.keys(countriesData).forEach(function(code) {
                    if (code === "") return; // Saltar la opción vacía
                    
                    var country = countriesData[code];
                    var name = country.name.toLowerCase();
                    
                    if (query === "" || name.includes(query)) {
                        html += `<div class="cf-country-option" data-code="${code}">
                            <span class="cf-country-flag-option">${country.flag}</span>
                            <span class="cf-country-name-option">${country.name}</span>
                        </div>`;
                        matchCount++;
                    }
                });
                
                if (matchCount === 0) {
                    html = `<div class="cf-country-option" style="color: #999; cursor: default;">
                        <span class="cf-country-name-option">No se encontraron países</span>
                    </div>`;
                }
                
                dropdown.html(html).show();
                
                // Agregar eventos de click
                dropdown.find(".cf-country-option[data-code]").on("click", function() {
                    var code = $(this).data("code");
                    var name = $(this).find(".cf-country-name-option").text();
                    selectCountry(input, code, name);
                    dropdown.hide();
                });
                
                // Agregar eventos de hover
                dropdown.find(".cf-country-option").on("mouseenter", function() {
                    dropdown.find(".cf-country-option").removeClass("cf-highlighted");
                    $(this).addClass("cf-highlighted");
                });
            }
            
            // Seleccionar un país
            function selectCountry(input, code, name) {
                input.val(name);
                input.data("country-code", code);
                selectedCountryCode = code;
            }
            
            // Limpiar autocompletado al cerrar modal
            $(document).on("click", ".cf-modal-close, .cf-modal-backdrop", function() {
                $(".cf-country-dropdown").hide();
                selectedCountryCode = "";
            });
            
            // ================================
            // CONTADOR DE CARACTERES
            // ================================
            
            // Contador para usuario logueado
            $("#cf-content-logged").on("input", function() {
                var charCount = $(this).val().length;
                $("#cf-content-count-logged").text(charCount);
            });
            
            // Contador para usuario no logueado (invitado)
            $("#cf-content-guest").on("input", function() {
                var charCount = $(this).val().length;
                $("#cf-content-count-guest").text(charCount);
            });
            
            // ================================
            // CARGAR MÁS COMENTARIOS
            // ================================
            
            $(document).on("click", "#cf-load-more-btn", function(e) {
                e.preventDefault();
                
                var $btn = $(this);
                var $container = $(".cf-comments-list");
                var postId = $container.data("post-id");
                var offset = $container.data("offset");
                var isLoading = $btn.data("loading");
                
                if (isLoading) return;
                
                // Mostrar loading
                var originalText = $btn.html();
                var loadingText = cfTranslations.js_loading || "Cargando...";
                $btn.html("⏳ " + loadingText).prop("disabled", true).data("loading", true);
                
                $.ajax({
                    url: comentarios_ajax.ajax_url,
                    type: "POST",
                    data: {
                        action: "comentarios_load_more",
                        post_id: postId,
                        offset: offset
                    },
                    success: function(response) {
                        if (response.success && response.data.html) {
                            // Agregar nuevos comentarios con animación
                            var $newComments = $(response.data.html);
                            $newComments.hide();
                            $container.append($newComments);
                            $newComments.fadeIn(500);
                            
                            // Actualizar offset
                            var newOffset = offset + response.data.loaded;
                            $container.data("offset", newOffset);
                            
                            // Verificar si hay más comentarios
                            var total = $container.data("total");
                            var remaining = total - newOffset;
                            
                            if (remaining > 0) {
                                $btn.html("Ver más comentarios (" + remaining + " restantes)").prop("disabled", false).data("loading", false);
                            } else {
                                // No hay más comentarios, ocultar botón
                                $(".cf-load-more-container").fadeOut(300);
                            }
                        } else {
                            showAlert({
                                icon: "error",
                                title: "Error",
                                text: "No se pudieron cargar más comentarios",
                                confirmButtonColor: "#007cba"
                            });
                            $btn.html(originalText).prop("disabled", false).data("loading", false);
                        }
                    },
                    error: function() {
                        showAlert({
                            icon: "error",
                            title: "Error",
                            text: "Error de conexión al cargar comentarios",
                            confirmButtonColor: "#007cba"
                        });
                        $btn.html(originalText).prop("disabled", false).data("loading", false);
                    }
                });
            });
        });
        </script>';
    }
    
    /**
     * Obtener estadísticas de comentarios para un post
     */
    private function get_comments_stats($post_id) {
        // Usar el método centralizado de la clase Database para consistencia
        $rating_stats = $this->database->get_rating_stats($post_id);
        
        // Si no hay estadísticas, devolver valores por defecto
        if (!$rating_stats) {
            return array(
                'total' => 0,
                'avg_rating' => 0,
                'rating_distribution' => array(
                    5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0
                )
            );
        }
        
        return array(
            'total' => intval($rating_stats->total_reviews),
            'avg_rating' => floatval($rating_stats->average_rating),
            'rating_distribution' => array(
                5 => intval($rating_stats->rating_5),
                4 => intval($rating_stats->rating_4),
                3 => intval($rating_stats->rating_3),
                2 => intval($rating_stats->rating_2),
                1 => intval($rating_stats->rating_1)
            )
        );
    }
    
    /**
     * Mostrar estrellas visuales para un rating
     */
    private function display_rating_stars($rating, $show_number = false) {
        $rating = floatval($rating);
        $full_stars = floor($rating);
        $half_star = ($rating - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
        
        echo '<div class="cf-stars-display startsp" data-rating="' . $rating . '">';
        
        // Estrellas llenas
        for ($i = 0; $i < $full_stars; $i++) {
            echo '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-star cf-star-full" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M3.612 15.443c-.386.198-.824-.149-.746-.592l.83-4.73L.173 6.765c-.329-.314-.158-.888.283-.95l4.898-.696L7.538.792c.197-.39.73-.39.927 0l2.184 4.327 4.898.696c.441.062.612.636.282.95l-3.522 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256z"></path></svg>';
        }
        
        // Media estrella
        if ($half_star) {
            echo '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-star cf-star-half" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M5.354 5.119 7.538.792A.52.52 0 0 1 8 .5c.183 0 .366.097.465.292l2.184 4.327 4.898.696A.54.54 0 0 1 16 6.32a.55.55 0 0 1-.17.445l-3.523 3.356.83 4.73c.078.443-.36.79-.746.592L8 13.187l-4.389 2.256a.5.5 0 0 1-.146.05c-.342.06-.668-.254-.6-.642l.83-4.73L.173 6.765a.55.55 0 0 1-.172-.403.6.6 0 0 1 .085-.302.51.51 0 0 1 .37-.245zM8 12.027a.5.5 0 0 1 .232.056l3.686 1.894-.694-3.957a.56.56 0 0 1 .162-.505l2.907-2.77-4.052-.576a.53.53 0 0 1-.393-.288L8.001 2.223 8 2.226z"></path></svg>';
        }
        
        // Estrellas vacías
        for ($i = 0; $i < $empty_stars; $i++) {
            echo '<svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 16 16" class="cf-star cf-star-empty" height="16" width="16" xmlns="http://www.w3.org/2000/svg"><path d="M2.866 14.85c-.078.444.36.791.746.593l4.39-2.256 4.389 2.256c.386.198.824-.149.746-.592l-.83-4.73 3.522-3.356c.33-.314.16-.888-.282-.95l-4.898-.696L8.465.792a.513.513 0 0 0-.927 0L5.354 5.12l-4.898.696c-.441.062-.612.636-.283.95l3.523 3.356-.83 4.73zm4.905-2.767-3.686 1.894.694-3.957a.56.56 0 0 0-.163-.505L1.71 6.745l4.052-.576a.53.53 0 0 0 .393-.288L8 2.223l1.847 3.658a.53.53 0 0 0 .393.288l4.052.575-2.906 2.77a.56.56 0 0 0-.163.506l.694 3.957-3.686-1.894a.5.5 0 0 0-.461 0z"></path></svg>';
        }
        
        // Mostrar número si se solicita
        if ($show_number) {
            echo '<span class="cf-rating-number">(' . number_format($rating, 1) . ')</span>';
        }
        
        echo '</div>';
    }
    
    /**
     * Genera el formulario de registro usando el shortcode específico para comentarios
     */
    private function get_registration_form() {
        // Verificar si el shortcode de loginfree está disponible
        if (function_exists('do_shortcode')) {
            // Usar shortcode específico para comentarios (evita conflictos con el modal del header)
            $registration_form = do_shortcode('[advanced_registration_form_comments]');
            
            // Verificar si el shortcode se ejecutó correctamente (no debe contener corchetes)
            if (!empty($registration_form) && strpos($registration_form, '[advanced_registration_form_comments') === false) {
                return $registration_form;
            }
            
            // Fallback al shortcode normal si el específico no existe
            $registration_form = do_shortcode('[advanced_registration_form no_redirect="true" modal_mode="true"]');
            if (!empty($registration_form) && strpos($registration_form, '[advanced_registration_form') === false) {
                return $registration_form;
            }
            
            // Si ningún shortcode funciona, mostrar mensaje
            return '<div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 8px; text-align: center;">
                        <p><strong>⚠️ Plugin LoginFree no está disponible</strong></p>
                        <p>El formulario de autenticación no se pudo cargar. Por favor, verifica que el plugin LoginFree esté activo.</p>
                    </div>';
        }
        
        // Si do_shortcode no existe, mostrar mensaje de error
        return '<div style="background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 8px; text-align: center;">
                    <p><strong>❌ Error de configuración</strong></p>
                    <p>No se puede cargar el sistema de registro.</p>
                </div>';
    }
    
    /**
     * Verificar si el usuario actual ya tiene un comentario en el post especificado
     */
    private function user_has_existing_comment($post_id) {
        // Verificar si el usuario está logueado
        if (!is_user_logged_in()) {
            return false;
        }
        
        try {
            $current_user = wp_get_current_user();
            
            // Verificar que el usuario tenga email
            if (empty($current_user->user_email)) {
                return false;
            }
            
            $user_email = $current_user->user_email;
            
            // Buscar comentario existente del usuario en este post
            $existing_comment = $this->database->get_user_comment_for_post($post_id, $user_email);
            
            return !empty($existing_comment);
        } catch (Exception $e) {
            // Si hay algún error, asumir que no tiene comentario y permitir que escriba
            error_log('Error checking existing comment: ' . $e->getMessage());
            return false;
        }
    }
}
?>