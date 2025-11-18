<?php
/**
 * Clase Admin para el plugin Comentarios Free
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Admin {
    
    private $database;
    
    public function __construct() {
        $this->database = new ComentariosFree_Database();
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        // Menú deshabilitado para evitar duplicados
        // Solo se mantiene el menú principal desde admin-dashboard.php
    }
    
    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('comentarios_free_settings', 'comentarios_free_show_auto');
        register_setting('comentarios_free_settings', 'comentarios_free_auto_approve');
        register_setting('comentarios_free_settings', 'comentarios_free_require_login');
        register_setting('comentarios_free_settings', 'comentarios_free_max_images');
        register_setting('comentarios_free_settings', 'comentarios_free_max_file_size');
        register_setting('comentarios_free_settings', 'comentarios_free_allowed_file_types');
        register_setting('comentarios_free_settings', 'comentarios_free_notification_email');
        register_setting('comentarios_free_settings', 'comentarios_free_enable_rich_snippets');
    }
    
    /**
     * Mostrar notificaciones de admin
     */
    public function admin_notices() {
        if (isset($_GET['page']) && strpos($_GET['page'], 'comentarios-free') !== false) {
            if (isset($_GET['message']) && $_GET['message'] == 'updated') {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __('Configuración guardada correctamente.', 'comentarios-free') . '</p>';
                echo '</div>';
            }
        }
    }
    
    /**
     * Página principal de administración
     */
    public function admin_page_main() {
        // Procesar acciones
        if (isset($_POST['action']) && wp_verify_nonce($_POST['comentarios_nonce'], 'comentarios_admin_nonce')) {
            $this->process_admin_actions();
        }
        
        // Obtener comentarios con paginación
        $per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $per_page;
        
        // Filtros
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        $post_filter = isset($_GET['post_id']) ? intval($_GET['post_id']) : 0;
        
        $filter_args = array(
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        if (!empty($status_filter)) {
            $filter_args['status'] = $status_filter;
        }
        
        if (!empty($post_filter)) {
            $filter_args['post_id'] = $post_filter;
        }
        
        $comments = $this->database->get_comments($filter_args);
        $total_comments = $this->database->count_comments($filter_args);
        $total_pages = ceil($total_comments / $per_page);
        
        ?>
        <div class="wrap">
            <h1><?php _e('Gestión de Comentarios', 'comentarios-free'); ?></h1>
            
            <!-- Filtros -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="comentarios-free">
                        
                        <select name="status">
                            <option value=""><?php _e('Todos los estados', 'comentarios-free'); ?></option>
                            <option value="approved" <?php selected($status_filter, 'approved'); ?>><?php _e('Aprobados', 'comentarios-free'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pendientes', 'comentarios-free'); ?></option>
                            <option value="spam" <?php selected($status_filter, 'spam'); ?>><?php _e('Spam', 'comentarios-free'); ?></option>
                        </select>
                        
                        <input type="submit" class="button" value="<?php _e('Filtrar', 'comentarios-free'); ?>">
                    </form>
                </div>
                
                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                <div class="tablenav-pages">
                    <?php
                    $page_links = paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo; Anterior'),
                        'next_text' => __('Siguiente &raquo;'),
                        'total' => $total_pages,
                        'current' => $current_page
                    ));
                    echo $page_links;
                    ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Tabla de comentarios -->
            <form method="post">
                <?php wp_nonce_field('comentarios_admin_nonce', 'comentarios_nonce'); ?>
                
                <table class="wp-list-table widefat fixed striped comments">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" />
                            </td>
                            <th class="manage-column"><?php _e('Autor', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('Comentario', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('Rating', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('En respuesta a', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('Fecha', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('Estado', 'comentarios-free'); ?></th>
                            <th class="manage-column"><?php _e('Acciones', 'comentarios-free'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($comments)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 20px;">
                                <?php _e('No se encontraron comentarios.', 'comentarios-free'); ?>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                        <tr>
                            <th class="check-column">
                                <input type="checkbox" name="comment_ids[]" value="<?php echo $comment->id; ?>" />
                            </th>
                            <td class="author column-author">
                                <strong><?php echo esc_html($comment->author_name); ?></strong><br>
                                <a href="mailto:<?php echo esc_attr($comment->author_email); ?>"><?php echo esc_html($comment->author_email); ?></a><br>
                                <small><?php echo esc_html($comment->country); ?> | <?php echo strtoupper($comment->language); ?></small>
                            </td>
                            <td class="comment column-comment">
                                <div class="comment-title">
                                    <strong><?php echo esc_html($comment->title); ?></strong>
                                </div>
                                <div class="comment-content">
                                    <?php echo wp_trim_words(esc_html($comment->content), 20); ?>
                                </div>
                                <?php 
                                $images = $this->database->get_comment_images($comment->id);
                                if (!empty($images)): 
                                ?>
                                <div class="comment-images">
                                    <small><?php echo sprintf(_n('%d imagen', '%d imágenes', count($images), 'comentarios-free'), count($images)); ?></small>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="rating column-rating">
                                <?php $this->display_admin_stars($comment->rating); ?>
                            </td>
                            <td class="response column-response">
                                <?php
                                $post = get_post($comment->post_id);
                                if ($post) {
                                    echo '<a href="' . get_permalink($post->ID) . '" target="_blank">';
                                    echo esc_html($post->post_title);
                                    echo '</a>';
                                } else {
                                    echo '<em>' . __('Post eliminado', 'comentarios-free') . '</em>';
                                }
                                ?>
                            </td>
                            <td class="date column-date">
                                <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($comment->created_at)); ?>
                            </td>
                            <td class="status column-status">
                                <span class="status-<?php echo $comment->status; ?>">
                                    <?php
                                    switch ($comment->status) {
                                        case 'approved':
                                            _e('Aprobado', 'comentarios-free');
                                            break;
                                        case 'pending':
                                            _e('Pendiente', 'comentarios-free');
                                            break;
                                        case 'spam':
                                            _e('Spam', 'comentarios-free');
                                            break;
                                        default:
                                            echo esc_html($comment->status);
                                    }
                                    ?>
                                </span>
                            </td>
                            <td class="actions column-actions">
                                <div class="row-actions">
                                    <?php if ($comment->status !== 'approved'): ?>
                                    <span class="approve">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=comentarios-free&action=approve&comment_id=' . $comment->id), 'approve_comment_' . $comment->id); ?>">
                                            <?php _e('Aprobar', 'comentarios-free'); ?>
                                        </a> |
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($comment->status !== 'spam'): ?>
                                    <span class="spam">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=comentarios-free&action=spam&comment_id=' . $comment->id), 'spam_comment_' . $comment->id); ?>">
                                            <?php _e('Spam', 'comentarios-free'); ?>
                                        </a> |
                                    </span>
                                    <?php endif; ?>
                                    
                                    <span class="delete">
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=comentarios-free&action=delete&comment_id=' . $comment->id), 'delete_comment_' . $comment->id); ?>" 
                                           onclick="return confirm('<?php _e('¿Estás seguro de que deseas eliminar este comentario?', 'comentarios-free'); ?>')">
                                            <?php _e('Eliminar', 'comentarios-free'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Acciones en lote -->
                <div class="tablenav bottom">
                    <div class="alignleft actions bulkactions">
                        <select name="action">
                            <option value=""><?php _e('Acciones en lote', 'comentarios-free'); ?></option>
                            <option value="approve"><?php _e('Aprobar', 'comentarios-free'); ?></option>
                            <option value="spam"><?php _e('Marcar como spam', 'comentarios-free'); ?></option>
                            <option value="delete"><?php _e('Eliminar', 'comentarios-free'); ?></option>
                        </select>
                        <input type="submit" class="button" value="<?php _e('Aplicar', 'comentarios-free'); ?>">
                    </div>
                </div>
            </form>
        </div>
        <?php
    }
    
    /**
     * Página de configuración
     */
    public function admin_page_settings() {
        if (isset($_POST['submit'])) {
            update_option('comentarios_free_show_auto', isset($_POST['show_auto']) ? '1' : '0');
            update_option('comentarios_free_auto_approve', isset($_POST['auto_approve']) ? '1' : '0');
            update_option('comentarios_free_require_login', isset($_POST['require_login']) ? '1' : '0');
            update_option('comentarios_free_max_images', intval($_POST['max_images']));
            update_option('comentarios_free_max_file_size', intval($_POST['max_file_size']));
            update_option('comentarios_free_allowed_file_types', sanitize_text_field($_POST['allowed_file_types']));
            update_option('comentarios_free_notification_email', sanitize_email($_POST['notification_email']));
            update_option('comentarios_free_enable_rich_snippets', isset($_POST['enable_rich_snippets']) ? '1' : '0');
            
            wp_redirect(admin_url('admin.php?page=comentarios-free-settings&message=updated'));
            exit;
        }
        
        $show_auto = get_option('comentarios_free_show_auto', '1');
        $auto_approve = get_option('comentarios_free_auto_approve', '1');
        $require_login = get_option('comentarios_free_require_login', '1');
        $max_images = get_option('comentarios_free_max_images', '5');
        $max_file_size = get_option('comentarios_free_max_file_size', '2');
        $allowed_file_types = get_option('comentarios_free_allowed_file_types', 'jpg,jpeg,png,gif,webp');
        $notification_email = get_option('comentarios_free_notification_email', get_option('admin_email'));
        $enable_rich_snippets = get_option('comentarios_free_enable_rich_snippets', '1');
        
        ?>
        <div class="wrap">
            <h1><?php _e('Configuración de Comentarios Free', 'comentarios-free'); ?></h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Mostrar automáticamente', 'comentarios-free'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="show_auto" value="1" <?php checked($show_auto, '1'); ?>>
                                <?php _e('Mostrar comentarios automáticamente en posts y páginas', 'comentarios-free'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Aprobación automática', 'comentarios-free'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_approve" value="1" <?php checked($auto_approve, '1'); ?>>
                                <?php _e('Aprobar comentarios automáticamente', 'comentarios-free'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Requerir login', 'comentarios-free'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="require_login" value="1" <?php checked($require_login, '1'); ?>>
                                <?php _e('Solo usuarios registrados pueden comentar', 'comentarios-free'); ?>
                            </label>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Máximo de imágenes', 'comentarios-free'); ?></th>
                        <td>
                            <input type="number" name="max_images" value="<?php echo esc_attr($max_images); ?>" min="1" max="10">
                            <p class="description"><?php _e('Número máximo de imágenes por comentario', 'comentarios-free'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Tamaño máximo de archivo', 'comentarios-free'); ?></th>
                        <td>
                            <input type="number" name="max_file_size" value="<?php echo esc_attr($max_file_size); ?>" min="1" max="10"> MB
                            <p class="description"><?php _e('Tamaño máximo por imagen en megabytes', 'comentarios-free'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Tipos de archivo permitidos', 'comentarios-free'); ?></th>
                        <td>
                            <input type="text" name="allowed_file_types" value="<?php echo esc_attr($allowed_file_types); ?>" class="regular-text">
                            <p class="description"><?php _e('Extensiones permitidas separadas por comas (ej: jpg,png,gif)', 'comentarios-free'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Email de notificaciones', 'comentarios-free'); ?></th>
                        <td>
                            <input type="email" name="notification_email" value="<?php echo esc_attr($notification_email); ?>" class="regular-text">
                            <p class="description"><?php _e('Email para recibir notificaciones de nuevos comentarios', 'comentarios-free'); ?></p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row"><?php _e('Rich Snippets', 'comentarios-free'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_rich_snippets" value="1" <?php checked($enable_rich_snippets, '1'); ?>>
                                <?php _e('Habilitar datos estructurados (Schema.org)', 'comentarios-free'); ?>
                            </label>
                            <p class="description"><?php _e('Mejora el SEO añadiendo datos estructurados para reseñas', 'comentarios-free'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Página de estadísticas
     */
    public function admin_page_stats() {
        // Estadísticas generales
        $total_comments = $this->database->count_comments();
        $approved_comments = $this->database->count_comments(array('status' => 'approved'));
        $pending_comments = $this->database->count_comments(array('status' => 'pending'));
        
        // Promedio de rating
        global $wpdb;
        $table_comments = $this->database->get_comments_table();
        
        $avg_rating = $wpdb->get_var("SELECT AVG(rating) FROM {$table_comments} WHERE status = 'approved' AND rating > 0");
        $avg_rating = $avg_rating ? round($avg_rating, 1) : 0;
        
        // Top posts con más comentarios
        $top_posts = $wpdb->get_results("
            SELECT post_id, COUNT(*) as comment_count 
            FROM {$table_comments} 
            WHERE status = 'approved' 
            GROUP BY post_id 
            ORDER BY comment_count DESC 
            LIMIT 10
        ");
        
        ?>
        <div class="wrap">
            <h1><?php _e('Estadísticas de Comentarios', 'comentarios-free'); ?></h1>
            
            <div class="comentarios-stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format_i18n($total_comments); ?></div>
                    <div class="stat-label"><?php _e('Total Comentarios', 'comentarios-free'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format_i18n($approved_comments); ?></div>
                    <div class="stat-label"><?php _e('Aprobados', 'comentarios-free'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format_i18n($pending_comments); ?></div>
                    <div class="stat-label"><?php _e('Pendientes', 'comentarios-free'); ?></div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-number"><?php echo $avg_rating; ?></div>
                    <div class="stat-label"><?php _e('Rating Promedio', 'comentarios-free'); ?></div>
                </div>
            </div>
            
            <?php if (!empty($top_posts)): ?>
            <h2><?php _e('Posts más comentados', 'comentarios-free'); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Post', 'comentarios-free'); ?></th>
                        <th><?php _e('Comentarios', 'comentarios-free'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_posts as $top_post): ?>
                    <tr>
                        <td>
                            <?php 
                            $post = get_post($top_post->post_id);
                            if ($post) {
                                echo '<a href="' . get_permalink($post->ID) . '" target="_blank">' . esc_html($post->post_title) . '</a>';
                            } else {
                                echo '<em>' . __('Post eliminado', 'comentarios-free') . '</em>';
                            }
                            ?>
                        </td>
                        <td><?php echo number_format_i18n($top_post->comment_count); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Procesar acciones de administración
     */
    private function process_admin_actions() {
        $action = sanitize_text_field($_POST['action']);
        
        if (empty($action) || !isset($_POST['comment_ids'])) {
            return;
        }
        
        $comment_ids = array_map('intval', $_POST['comment_ids']);
        
        foreach ($comment_ids as $comment_id) {
            switch ($action) {
                case 'approve':
                    $this->database->update_comment($comment_id, array('status' => 'approved'));
                    break;
                case 'spam':
                    $this->database->update_comment($comment_id, array('status' => 'spam'));
                    break;
                case 'delete':
                    $this->database->delete_comment($comment_id);
                    break;
            }
        }
        
        wp_redirect(admin_url('admin.php?page=comentarios-free&message=updated'));
        exit;
    }
    
    /**
     * Mostrar estrellas en el admin
     */
    private function display_admin_stars($rating) {
        $rating = intval($rating);
        
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $rating) {
                echo '<span class="star filled">★</span>';
            } else {
                echo '<span class="star empty">☆</span>';
            }
        }
    }
}