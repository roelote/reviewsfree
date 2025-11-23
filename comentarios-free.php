<?php
/**
 * Plugin Name: Comentarios Free - Sistema de Testimonios Personalizado
 * Plugin URI: https://tudominio.com
 * Description: Plugin de comentarios/testimonios personalizado con integración completa, rich snippets y panel de usuario.
 * Version: 1.0.0
 * Author: Tu Nombre
 * Author URI: https://tudominio.com
 * Text Domain: comentarios-free
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevenir acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('COMENTARIOS_FREE_VERSION', '1.1.0');
define('COMENTARIOS_FREE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('COMENTARIOS_FREE_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('COMENTARIOS_FREE_PLUGIN_FILE', __FILE__);

/**
 * Clase principal del plugin
 */
class ComentariosFree {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    public function init() {
        // Cargar archivos de clases
        $this->load_dependencies();
        
        // Inicializar hooks
        $this->init_hooks();
        
        // Cargar textdomain para traducción
        load_plugin_textdomain('comentarios-free', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
    
    private function load_dependencies() {
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-database.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/countries-data.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/translations/class-translations.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-admin.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-frontend-twostep.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-ajax.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-rich-snippets.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-user-panel.php';
        require_once COMENTARIOS_FREE_PLUGIN_PATH . 'includes/class-admin-dashboard.php';
    }
    
    private function init_hooks() {
        // Hooks de activación y desactivación
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Cargar estilos y scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Inicializar clases
        new ComentariosFree_Database();
        new ComentariosFree_Admin();
        new ComentariosFree_Frontend_TwoStep();
        new ComentariosFree_Ajax();
        new ComentariosFree_Rich_Snippets();
        new ComentariosFree_User_Panel();
        new ComentariosFree_Admin_Dashboard();
        
        // Shortcode para mostrar comentarios
        add_shortcode('comentarios_free', array($this, 'comentarios_shortcode'));
        
        // Shortcode para panel de usuario
        add_shortcode('comentarios_user_panel', array($this, 'user_panel_shortcode'));
        
        // Shortcode para mostrar resumen de calificaciones
        add_shortcode('comentarios_resumen', array($this, 'comentarios_resumen_shortcode'));
        
        // Hook para mostrar automáticamente en páginas/posts (DESHABILITADO para usar shortcode manual)
        // add_filter('the_content', array($this, 'add_comments_to_content'));
    }
    
    public function activate() {
        // Crear tablas de base de datos
        $database = new ComentariosFree_Database();
        $database->create_tables();
        
        // Crear página de usuario si no existe
        $this->create_user_page();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    private function create_user_page() {
        $page_slug = 'mis-comentarios';
        $page_exists = get_page_by_path($page_slug);
        
        if (!$page_exists) {
            $page_data = array(
                'post_title'    => 'Mis Comentarios',
                'post_content'  => '[comentarios_user_panel]',
                'post_status'   => 'publish',
                'post_type'     => 'page',
                'post_name'     => $page_slug,
                'post_author'   => 1
            );
            wp_insert_post($page_data);
        }
    }
    
    public function enqueue_public_assets() {
        // Solo cargar en páginas públicas, NO en admin
        if (is_admin()) {
            return;
        }
        
        wp_enqueue_style(
            'comentarios-free-style',
            COMENTARIOS_FREE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            COMENTARIOS_FREE_VERSION
        );
        
        // Cargar JavaScript independiente (DESHABILITADO - usando frontend.js principal)
        /*
        $standalone_js = COMENTARIOS_FREE_PLUGIN_PATH . 'assets/js/frontend-standalone.js';
        if (file_exists($standalone_js)) {
            wp_enqueue_script(
                'comentarios-free-standalone-script',
                COMENTARIOS_FREE_PLUGIN_URL . 'assets/js/frontend-standalone.js',
                array('jquery'),
                COMENTARIOS_FREE_VERSION . '.' . time(), // Force refresh
                true
            );
        }
        */
        
        // Cargar SweetAlert2 de forma centralizada
        $this->enqueue_sweetalert2();
        
        // Cargar frontend.js principal SOLO en frontend
        wp_enqueue_script(
            'comentarios-free-frontend-script',
            COMENTARIOS_FREE_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'sweetalert2'),
            COMENTARIOS_FREE_VERSION,
            true
        );
        
        // Localizar script
        $script_handle = 'comentarios-free-frontend-script';
        
        wp_localize_script($script_handle, 'comentarios_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comentarios_free_nonce'),
            'strings' => array(
                'error' => __('Error al procesar la solicitud', 'comentarios-free'),
                'success' => __('Comentario enviado correctamente', 'comentarios-free'),
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar este comentario?', 'comentarios-free')
            )
        ));
    }
    
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'comentarios-free') !== false) {
            // Cargar SweetAlert2 de forma centralizada en admin
            $this->enqueue_sweetalert2();
            
            // Cargar CSS de admin
            wp_enqueue_style(
                'comentarios-free-admin-style',
                COMENTARIOS_FREE_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                COMENTARIOS_FREE_VERSION
            );
            
            // Cargar CSS del dashboard de admin
            wp_enqueue_style(
                'comentarios-free-admin-dashboard-style',
                COMENTARIOS_FREE_PLUGIN_URL . 'assets/css/admin-dashboard.css',
                array(),
                COMENTARIOS_FREE_VERSION
            );
            
            // Cargar también el CSS del frontend para que el modal se vea bien
            wp_enqueue_style(
                'comentarios-free-frontend-style',
                COMENTARIOS_FREE_PLUGIN_URL . 'assets/css/frontend.css',
                array(),
                COMENTARIOS_FREE_VERSION
            );
            
            wp_enqueue_script(
                'comentarios-free-admin-script',
                COMENTARIOS_FREE_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'sweetalert2'),
                COMENTARIOS_FREE_VERSION,
                true
            );
            
            // NO cargar user-panel.js en admin - causa conflictos con admin-dashboard.js
            // El admin-dashboard.js ya tiene toda la funcionalidad necesaria
            
            // Localizar script para AJAX en admin.js
            wp_localize_script('comentarios-free-admin-script', 'comentarios_ajax', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('comentarios_free_nonce')
            ));
        }
    }
    
    public function comentarios_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID(),
            'limit' => 10,
            'show_form' => 'true'
        ), $atts);
        
        $frontend = new ComentariosFree_Frontend_TwoStep();
        return $frontend->display_comments($atts);
    }
    
    public function user_panel_shortcode($atts) {
        // Cargar assets específicos del panel de usuario
        $this->enqueue_user_panel_assets();
        
        $user_panel = new ComentariosFree_User_Panel();
        return $user_panel->display_user_panel();
    }
    
    /**
     * Shortcode para mostrar resumen de calificaciones
     * Uso: [comentarios_resumen post_id="123"]
     * Si no se especifica post_id, usa el post actual
     */
    public function comentarios_resumen_shortcode($atts) {
        $atts = shortcode_atts(array(
            'post_id' => get_the_ID()
        ), $atts);
        
        $post_id = intval($atts['post_id']);
        
        if (!$post_id) {
            return '';
        }
        
        // Si WPML está activo, obtener el ID del post original (idioma por defecto)
        // Los comentarios se guardan con el post original, no con las traducciones
        if (function_exists('icl_object_id')) {
            // Obtener el post original (idioma por defecto)
            $default_lang = apply_filters('wpml_default_language', NULL);
            $original_post_id = apply_filters('wpml_object_id', $post_id, get_post_type($post_id), false, $default_lang);
            if ($original_post_id) {
                $post_id = $original_post_id;
            }
        }
        
        // Obtener estadísticas del post
        $database = new ComentariosFree_Database();
        $stats = $database->get_rating_stats($post_id);
        
        if (!$stats || $stats->total_reviews == 0) {
            return '';
        }
        
        $total = $stats->total_reviews;
        $average = round($stats->average_rating, 1);
        $full_stars = floor($average);
        $has_half_star = ($average - $full_stars) >= 0.5;
        $empty_stars = 5 - $full_stars - ($has_half_star ? 1 : 0);
        
        // Generar HTML del resumen
        ob_start();
        ?>
        <div class="comentarios-resumen-widget" style="display: inline-flex; align-items: center; gap: 8px;">
            <div class="comentarios-stars" style="display: flex; gap: 2px; color: #ffa500;">
                <?php
                // Estrellas llenas
                for ($i = 0; $i < $full_stars; $i++) {
                    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                }
                // Media estrella
                if ($has_half_star) {
                    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><defs><linearGradient id="half-star"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="#e0e0e0"/></linearGradient></defs><path fill="url(#half-star)" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                }
                // Estrellas vacías
                for ($i = 0; $i < $empty_stars; $i++) {
                    echo '<svg width="20" height="20" viewBox="0 0 24 24" fill="#e0e0e0"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>';
                }
                ?>
            </div>
            <span class="comentarios-average" style="font-weight: 600; font-size: 16px; color: #333;">
                <?php echo number_format($average, 1); ?>
            </span>
            <span class="comentarios-total" style="font-size: 14px; color: #666;">
                (<?php echo $total; ?> <?php echo $total == 1 ? ComentariosFree_Translations::get('review') : ComentariosFree_Translations::get('reviews'); ?>)
            </span>
        </div>
        <?php
        return ob_get_clean();
    }
    
    public function enqueue_user_panel_assets() {
        // Cargar SweetAlert2 de forma centralizada
        $this->enqueue_sweetalert2();
        
        // Cargar CSS del panel de usuario
        wp_enqueue_style(
            'comentarios-free-user-panel-style',
            COMENTARIOS_FREE_PLUGIN_URL . 'assets/css/frontend.css',
            array('sweetalert2'),
            COMENTARIOS_FREE_VERSION
        );
        
        // Cargar JavaScript del panel de usuario
        wp_enqueue_script(
            'comentarios-free-user-panel-script',
            COMENTARIOS_FREE_PLUGIN_URL . 'assets/js/user-panel.js',
            array('jquery', 'sweetalert2'),
            COMENTARIOS_FREE_VERSION,
            true
        );
        
        // Localizar script para AJAX
        wp_localize_script('comentarios-free-user-panel-script', 'comentarios_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('comentarios_free_nonce'),
            'strings' => array(
                'error' => __('Error al procesar la solicitud', 'comentarios-free'),
                'success' => __('Comentario actualizado correctamente', 'comentarios-free'),
                'confirm_delete' => __('¿Estás seguro de que deseas eliminar este comentario?', 'comentarios-free')
            )
        ));
    }
    
    public function add_comments_to_content($content) {
        if (is_single() || is_page()) {
            $show_auto = get_option('comentarios_free_show_auto', '1');
            if ($show_auto == '1') {
                $content .= do_shortcode('[comentarios_free]');
            }
        }
        return $content;
    }
    
    /**
     * Función centralizada para cargar SweetAlert2 una sola vez
     * Previene conflictos de múltiples cargas
     */
    private function enqueue_sweetalert2() {
        // Solo cargar si no está ya cargado
        if (!wp_script_is('sweetalert2', 'enqueued')) {
            wp_enqueue_script('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11', array(), '11.0.0', true);
        }
        if (!wp_style_is('sweetalert2', 'enqueued')) {
            wp_enqueue_style('sweetalert2', 'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css', array(), '11.0.0');
        }
    }
}

// Inicializar el plugin
ComentariosFree::get_instance();

// Función helper para obtener la instancia
function comentarios_free() {
    return ComentariosFree::get_instance();
}