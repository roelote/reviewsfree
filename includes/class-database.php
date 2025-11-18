<?php
/**
 * Clase para manejo de base de datos del plugin Comentarios Free
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Database {
    
    private $table_comments;
    private $table_images;
    
    public function __construct() {
        global $wpdb;
        $this->table_comments = $wpdb->prefix . 'comentarios_free';
        $this->table_images = $wpdb->prefix . 'comentarios_free_images';
        
        add_action('init', array($this, 'init'));
    }
    
    public function init() {
        // Verificar si las tablas existen, crearlas si es necesario
        if (get_option('comentarios_free_db_version') != COMENTARIOS_FREE_VERSION) {
            $this->create_tables();
        }
        
        // IMPORTANTE: Siempre verificar columnas en cada carga para asegurar que la migración de status se ejecute
        // Esto se ejecutará solo una vez después del fix, y luego quedará en caché
        $status_migration = get_option('comentarios_free_status_migration_v2', '0');
        if ($status_migration != '1') {
            $this->ensure_edit_count_column();
            update_option('comentarios_free_status_migration_v2', '1');
        }
        
        // Solo verificar columnas si es necesario (optimización de rendimiento)
        $columns_checked = get_option('comentarios_free_columns_checked', '0');
        if ($columns_checked != COMENTARIOS_FREE_VERSION) {
            $this->ensure_edit_count_column();
            update_option('comentarios_free_columns_checked', COMENTARIOS_FREE_VERSION);
        }
    }
    
    public function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Tabla de comentarios
        $sql_comments = "CREATE TABLE {$this->table_comments} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id bigint(20) UNSIGNED NOT NULL,
            user_id bigint(20) UNSIGNED NOT NULL,
            author_name varchar(255) NOT NULL,
            author_email varchar(255) NOT NULL,
            country varchar(100) NOT NULL,
            language varchar(10) NOT NULL,
            rating tinyint(1) NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'approved',
            edit_count tinyint(1) NOT NULL DEFAULT 0,
            ip_address varchar(45) DEFAULT NULL,
            user_agent text DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY status (status),
            KEY rating (rating),
            KEY language (language),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Tabla de imágenes
        $sql_images = "CREATE TABLE {$this->table_images} (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) UNSIGNED NOT NULL,
            filename varchar(255) NOT NULL,
            original_name varchar(255) NOT NULL,
            file_path varchar(500) NOT NULL,
            file_url varchar(500) NOT NULL,
            file_size bigint(20) UNSIGNED NOT NULL,
            mime_type varchar(100) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comment_id (comment_id),
            FOREIGN KEY (comment_id) REFERENCES {$this->table_comments}(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_comments);
        dbDelta($sql_images);
        
        // Actualizar versión de la base de datos
        update_option('comentarios_free_db_version', COMENTARIOS_FREE_VERSION);
    }
    
    /**
     * Insertar un nuevo comentario
     */
    public function insert_comment($data) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => 0,
            'user_id' => 0,
            'author_name' => '',
            'author_email' => '',
            'country' => '',
            'language' => 'es',
            'travel_companion' => '',
            'rating' => 0,
            'title' => '',
            'content' => '',
            'status' => 'approved',
            'ip_address' => $this->get_user_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $this->table_comments,
            $data,
            array(
                '%d', // post_id
                '%d', // user_id
                '%s', // author_name
                '%s', // author_email
                '%s', // country
                '%s', // language
                '%s', // travel_companion
                '%d', // rating
                '%s', // title
                '%s', // content
                '%s', // status
                '%s', // ip_address
                '%s'  // user_agent
            )
        );
        
        if ($result === false) {
            return false;
        }
        
        // Limpiar cachés relacionados
        $this->clear_comment_caches();
        
        return $wpdb->insert_id;
    }
    
    /**
     * Insertar imagen de comentario
     */
    public function insert_comment_image($comment_id, $image_data) {
        global $wpdb;
        
        $image_data['comment_id'] = $comment_id;
        
        error_log('INSERT IMAGE DEBUG - Comment ID: ' . $comment_id);
        error_log('INSERT IMAGE DEBUG - Data: ' . json_encode($image_data));
        
        $result = $wpdb->insert(
            $this->table_images,
            $image_data,
            array(
                '%d', // comment_id
                '%s', // filename
                '%s', // original_name
                '%s', // file_path
                '%s', // file_url
                '%d', // file_size
                '%s'  // mime_type
            )
        );
        
        if ($result === false) {
            error_log('INSERT IMAGE ERROR - MySQL Error: ' . $wpdb->last_error);
        } else {
            error_log('INSERT IMAGE SUCCESS - Insert ID: ' . $wpdb->insert_id);
        }
        
        return $result;
    }
    
    /**
     * Obtener comentarios
     */
    public function get_comments($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => 0,
            'user_id' => 0,
            'status' => '',
            'rating' => '',
            'language' => '',
            'has_admin_response' => null,
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Crear clave de caché basada en los argumentos
        $cache_key = 'cf_comments_' . md5(serialize($args));
        $cached_comments = wp_cache_get($cache_key);
        
        if (false !== $cached_comments) {
            return $cached_comments;
        }
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['post_id'])) {
            $where_clauses[] = 'post_id = %d';
            $where_values[] = $args['post_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        // Solo filtrar por status si se especifica explícitamente
        if (!empty($args['status']) && $args['status'] !== '') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['rating'])) {
            $where_clauses[] = 'rating = %d';
            $where_values[] = $args['rating'];
        }
        
        if (!empty($args['language'])) {
            $where_clauses[] = 'language = %s';
            $where_values[] = $args['language'];
        }
        
        // Filtro especial para respuestas de admin
        if (isset($args['has_admin_response'])) {
            if ($args['has_admin_response'] === true) {
                $where_clauses[] = "(admin_response IS NOT NULL AND admin_response != '')";
            } elseif ($args['has_admin_response'] === false) {
                $where_clauses[] = "(admin_response IS NULL OR admin_response = '')";
            }
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $order_sql = sprintf('ORDER BY %s %s', 
            sanitize_sql_orderby($args['orderby']), 
            $args['order'] === 'ASC' ? 'ASC' : 'DESC'
        );
        
        $limit_sql = '';
        if ($args['limit'] > 0) {
            $limit_sql = sprintf('LIMIT %d, %d', intval($args['offset']), intval($args['limit']));
        }
        
        $sql = "SELECT * FROM {$this->table_comments} {$where_sql} {$order_sql} {$limit_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $results = $wpdb->get_results($sql);
        
        // Guardar en caché por 2 minutos
        wp_cache_set($cache_key, $results, '', 120);
        
        return $results;
    }
    
    /**
     * Contar comentarios con filtros (optimizado para paginación)
     */
    public function get_comments_count($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => 0,
            'user_id' => 0,
            'status' => '',
            'rating' => '',
            'language' => '',
            'has_admin_response' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Crear clave de caché para el conteo
        $cache_key = 'cf_comments_count_' . md5(serialize($args));
        $cached_count = wp_cache_get($cache_key);
        
        if (false !== $cached_count) {
            return $cached_count;
        }
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['post_id'])) {
            $where_clauses[] = 'post_id = %d';
            $where_values[] = $args['post_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if (!empty($args['status']) && $args['status'] !== '') {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['rating'])) {
            $where_clauses[] = 'rating = %d';
            $where_values[] = $args['rating'];
        }
        
        if (!empty($args['language'])) {
            $where_clauses[] = 'language = %s';
            $where_values[] = $args['language'];
        }
        
        if (isset($args['has_admin_response'])) {
            if ($args['has_admin_response'] === true) {
                $where_clauses[] = "(admin_response IS NOT NULL AND admin_response != '')";
            } elseif ($args['has_admin_response'] === false) {
                $where_clauses[] = "(admin_response IS NULL OR admin_response = '')";
            }
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table_comments} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        $count = $wpdb->get_var($sql);
        
        // Guardar en caché por 3 minutos
        wp_cache_set($cache_key, $count, '', 180);
        
        return intval($count);
    }
    
    /**
     * Obtener un comentario por ID
     */
    public function get_comment($id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_comments} WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Actualizar comentario
     */
    public function update_comment($id, $data) {
        global $wpdb;
        
        $data['updated_at'] = current_time('mysql');
        
        // Preparar tipos de datos según los campos
        $data_format = array();
        $allowed_fields = array(
            'post_id' => '%d',
            'user_id' => '%d', 
            'author_name' => '%s',
            'author_email' => '%s',
            'country' => '%s',
            'language' => '%s',
            'travel_companion' => '%s',
            'rating' => '%d',
            'title' => '%s',
            'content' => '%s',
            'status' => '%s',
            'edit_count' => '%d',
            'admin_response' => '%s',
            'updated_at' => '%s'
        );
        
        // Filtrar y formatear datos
        foreach ($data as $field => $value) {
            if (array_key_exists($field, $allowed_fields)) {
                $data_format[] = $allowed_fields[$field];
            } else {
                // Remover campos no permitidos
                unset($data[$field]);
            }
        }
        
        $result = $wpdb->update(
            $this->table_comments,
            $data,
            array('id' => $id),
            $data_format,
            array('%d')
        );
        
        // Limpiar cachés si la actualización fue exitosa
        if ($result !== false) {
            $this->clear_comment_caches();
        }
        
        return $result;
    }
    
    /**
     * Eliminar comentario
     */
    public function delete_comment($id) {
        global $wpdb;
        
        // Eliminar imágenes asociadas
        $this->delete_comment_images($id);
        
        $result = $wpdb->delete(
            $this->table_comments,
            array('id' => $id),
            array('%d')
        );
        
        // Limpiar cachés si la eliminación fue exitosa
        if ($result !== false) {
            $this->clear_comment_caches();
        }
        
        return $result;
    }
    
    /**
     * Obtener imágenes de un comentario
     */
    public function get_comment_images($comment_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->table_images} WHERE comment_id = %d ORDER BY id ASC",
            $comment_id
        ));
    }
    
    /**
     * Eliminar imágenes de un comentario
     */
    public function delete_comment_images($comment_id) {
        global $wpdb;
        
        // Obtener imágenes para eliminar archivos físicos
        $images = $this->get_comment_images($comment_id);
        
        foreach ($images as $image) {
            if (file_exists($image->file_path)) {
                unlink($image->file_path);
            }
        }
        
        return $wpdb->delete(
            $this->table_images,
            array('comment_id' => $comment_id),
            array('%d')
        );
    }
    
    /**
     * Contar comentarios
     */
    public function count_comments($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'post_id' => 0,
            'user_id' => 0,
            'status' => 'approved',
            'rating' => '',
            'language' => ''
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where_clauses = array();
        $where_values = array();
        
        if (!empty($args['post_id'])) {
            $where_clauses[] = 'post_id = %d';
            $where_values[] = $args['post_id'];
        }
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['rating'])) {
            $where_clauses[] = 'rating = %d';
            $where_values[] = $args['rating'];
        }
        
        if (!empty($args['language'])) {
            $where_clauses[] = 'language = %s';
            $where_values[] = $args['language'];
        }
        
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table_comments} {$where_sql}";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_var($sql);
    }
    
    /**
     * Obtener estadísticas de rating para un post
     */
    public function get_rating_stats($post_id) {
        global $wpdb;
        
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as rating_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as rating_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as rating_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as rating_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as rating_1
            FROM {$this->table_comments} 
            WHERE post_id = %d AND status = 'approved' AND rating > 0",
            $post_id
        ));
        
        if ($result) {
            $result->average_rating = round($result->average_rating, 1);
        }
        
        return $result;
    }
    
    /**
     * Obtener IP del usuario
     */
    private function get_user_ip() {
        $ip_keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    }
    
    /**
     * Obtener nombre de las tablas
     */
    public function get_comments_table() {
        return $this->table_comments;
    }
    
    public function get_images_table() {
        return $this->table_images;
    }
    
    /**
     * Verificar y agregar columnas necesarias si no existen
     */
    public function ensure_edit_count_column() {
        global $wpdb;
        
        // PRIMERO: Verificar y corregir el DEFAULT de la columna status
        $status_column = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_comments} LIKE %s",
            'status'
        ));
        
        if (!empty($status_column)) {
            $current_default = $status_column[0]->Default;
            // Si el default no es 'approved', corregirlo
            if ($current_default !== 'approved') {
                $wpdb->query("ALTER TABLE {$this->table_comments} MODIFY COLUMN status varchar(20) NOT NULL DEFAULT 'approved'");
                error_log('STATUS FIX - Columna status actualizada: DEFAULT cambiado a approved (era: ' . $current_default . ')');
            }
        }
        
        // Verificar si la columna edit_count existe
        $column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_comments} LIKE %s",
            'edit_count'
        ));
        
        // Si no existe, agregarla
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_comments} ADD COLUMN edit_count tinyint(1) NOT NULL DEFAULT 0 AFTER status");
            error_log('EDIT DEBUG - Columna edit_count agregada a la tabla');
        }
        
        // Verificar y agregar columna parent_id para respuestas anidadas
        $parent_column_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_comments} LIKE %s",
            'parent_id'
        ));
        
        if (empty($parent_column_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_comments} ADD COLUMN parent_id bigint(20) UNSIGNED NULL DEFAULT NULL AFTER user_id");
            $wpdb->query("ALTER TABLE {$this->table_comments} ADD KEY parent_id (parent_id)");
            error_log('REPLY DEBUG - Columna parent_id agregada para respuestas anidadas');
        }
        
        // Verificar y agregar columna admin_response para respuestas en línea
        $admin_response_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_comments} LIKE %s",
            'admin_response'
        ));
        
        if (empty($admin_response_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_comments} ADD COLUMN admin_response text NULL DEFAULT NULL AFTER content");
            error_log('ADMIN RESPONSE DEBUG - Columna admin_response agregada para respuestas inline');
        }
        
        // Verificar y agregar columna travel_companion para tipo de viaje
        $travel_companion_exists = $wpdb->get_results($wpdb->prepare(
            "SHOW COLUMNS FROM {$this->table_comments} LIKE %s",
            'travel_companion'
        ));
        
        if (empty($travel_companion_exists)) {
            $wpdb->query("ALTER TABLE {$this->table_comments} ADD COLUMN travel_companion varchar(50) NULL DEFAULT NULL AFTER language");
            error_log('TRAVEL DEBUG - Columna travel_companion agregada para tipo de viaje');
        }
        
        // NUEVO: Actualizar comentarios existentes con valores por defecto si están vacíos
        $this->update_empty_fields_with_defaults();
    }
    
    /**
     * Actualizar comentarios existentes que tengan campos vacíos con valores por defecto
     */
    private function update_empty_fields_with_defaults() {
        global $wpdb;
        
        // Actualizar country NULL con valor vacío (no forzar España)
        $country_updated = $wpdb->query("
            UPDATE {$this->table_comments} 
            SET country = '' 
            WHERE (country IS NULL OR country = 'null')
        ");
        
        // Actualizar language vacíos o NULL con valor por defecto
        $language_updated = $wpdb->query("
            UPDATE {$this->table_comments} 
            SET language = 'es' 
            WHERE (language IS NULL OR language = '' OR language = 'null')
        ");
        
        // Actualizar travel_companion vacíos o NULL con valor por defecto
        $travel_updated = $wpdb->query("
            UPDATE {$this->table_comments} 
            SET travel_companion = 'solo' 
            WHERE (travel_companion IS NULL OR travel_companion = '' OR travel_companion = 'null')
        ");
        
        // Actualizar rating 0 o NULL con valor por defecto
        $rating_updated = $wpdb->query("
            UPDATE {$this->table_comments} 
            SET rating = 5 
            WHERE (rating IS NULL OR rating = 0)
        ");
        
        if ($country_updated || $language_updated || $travel_updated || $rating_updated) {
            error_log("FIELDS UPDATE DEBUG - Actualizados: country=$country_updated, language=$language_updated, travel_companion=$travel_updated, rating=$rating_updated");
        }
    }
    
    /**
     * Obtener comentario de un usuario para un post específico
     */
    public function get_user_comment_for_post($post_id, $user_email) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT * FROM {$this->table_comments} 
            WHERE post_id = %d AND author_email = %s 
            ORDER BY created_at DESC 
            LIMIT 1
        ", $post_id, $user_email);
        
        return $wpdb->get_row($sql);
    }
    
    /**
     * Limpiar todas las cachés relacionadas con comentarios
     */
    private function clear_comment_caches() {
        // Limpiar caché de estadísticas admin
        wp_cache_delete('cf_admin_stats');
        wp_cache_delete('cf_admin_products');
        
        // Limpiar cachés de comentarios con diferentes patrones comunes
        $cache_keys_to_clear = array(
            'cf_comments_', // Patrón base para comentarios
            'cf_comments_count_', // Conteos de comentarios
            'cf_frontend_comments_', // Comentarios del frontend
            'cf_user_comments_' // Comentarios de usuarios
        );
        
        foreach ($cache_keys_to_clear as $pattern) {
            // WordPress no tiene una función nativa para limpiar por patrón,
            // pero podemos limpiar algunas claves comunes
            for ($i = 0; $i < 100; $i++) {
                wp_cache_delete($pattern . $i);
            }
        }
        
        // También limpiar cachés por hash MD5 más comunes
        $common_args = array(
            array('limit' => 10, 'orderby' => 'created_at', 'order' => 'DESC'),
            array('limit' => 20, 'orderby' => 'created_at', 'order' => 'DESC'),
            array('status' => 'approved', 'limit' => 10),
            array('status' => 'pending', 'limit' => 10)
        );
        
        foreach ($common_args as $args) {
            $cache_key = 'cf_comments_' . md5(serialize($args));
            wp_cache_delete($cache_key);
        }
    }
}