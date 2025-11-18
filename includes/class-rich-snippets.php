<?php
/**
 * Clase Rich Snippets para el plugin Comentarios Free
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Rich_Snippets {
    
    private $database;
    
    public function __construct() {
        $this->database = new ComentariosFree_Database();
        
        add_action('wp_head', array($this, 'add_structured_data'));
        add_filter('the_content', array($this, 'add_microdata_to_content'));
    }
    
    /**
     * Añadir datos estructurados JSON-LD al head
     */
    public function add_structured_data() {
        // Prevenir duplicación - solo ejecutar una vez por página
        static $already_added = false;
        if ($already_added) {
            return;
        }
        
        if (!is_single() && !is_page()) {
            return;
        }
        
        global $post;
        
        // Obtener comentarios con rating para este post
        $comments = $this->database->get_comments(array(
            'post_id' => $post->ID,
            'status' => 'approved'
        ));
        
        if (empty($comments)) {
            return;
        }
        
        // Calcular estadísticas de rating
        $rating_stats = $this->database->get_rating_stats($post->ID);
        
        if (!$rating_stats || $rating_stats->total_reviews == 0) {
            return;
        }
        
        // CREAR UN SOLO OBJETO PRODUCT CON TODAS LAS REVIEWS DENTRO
        $structured_data = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',  // SIEMPRE Product para fragmentos de productos
            'name' => get_the_title($post->ID),
            'description' => $this->get_post_description($post),
            'url' => get_permalink($post->ID),
            'image' => $this->get_post_image($post),
            'brand' => array(
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            ),
            'aggregateRating' => array(
                '@type' => 'AggregateRating',
                'ratingValue' => round($rating_stats->average_rating, 2),
                'reviewCount' => intval($rating_stats->total_reviews),
                'ratingCount' => intval($rating_stats->total_reviews),
                'bestRating' => 5,
                'worstRating' => 1
            ),
            'review' => array()
        );
        
        // Añadir reseñas individuales (máximo 15 para más visibilidad)
        $featured_comments = array_slice($comments, 0, 15);
        
        foreach ($featured_comments as $comment) {
            if ($comment->rating > 0) {
                $review = array(
                    '@type' => 'Review',
                    'itemReviewed' => array(
                        '@type' => 'Product',
                        'name' => get_the_title($post->ID)
                    ),
                    'author' => array(
                        '@type' => 'Person',
                        'name' => $comment->author_name
                    ),
                    'datePublished' => date('Y-m-d', strtotime($comment->created_at)),
                    'reviewBody' => trim($comment->content),
                    'headline' => !empty($comment->title) ? trim($comment->title) : substr(trim($comment->content), 0, 50) . '...',
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => intval($comment->rating),
                        'bestRating' => 5,
                        'worstRating' => 1
                    )
                );
                
                // Añadir país si está disponible
                if (!empty($comment->country)) {
                    $review['author']['address'] = array(
                        '@type' => 'PostalAddress',
                        'addressCountry' => $comment->country
                    );
                }
                
                $structured_data['review'][] = $review;
            }
        }
        
        // Si es un producto, añadir información adicional
        if ($this->is_product_post($post)) {
            $structured_data = $this->enhance_product_schema($structured_data, $post);
        }
        
        // Marcar como ya añadido para evitar duplicación
        $already_added = true;
        
        echo '<script type="application/ld+json">' . json_encode($structured_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Añadir microdatos al contenido
     */
    public function add_microdata_to_content($content) {
        if (!is_single() && !is_page()) {
            return $content;
        }
        
        global $post;
        
        // Verificar si hay comentarios con rating
        $rating_stats = $this->database->get_rating_stats($post->ID);
        
        if (!$rating_stats || $rating_stats->total_reviews == 0) {
            return $content;
        }
        
        // Añadir microdatos básicos al contenido
        $microdata = '<div itemscope itemtype="' . $this->get_microdata_type($post) . '">';
        $microdata .= '<meta itemprop="name" content="' . esc_attr(get_the_title($post->ID)) . '">';
        $microdata .= '<meta itemprop="url" content="' . esc_url(get_permalink($post->ID)) . '">';
        
        // Añadir rating agregado
        $microdata .= '<div itemprop="aggregateRating" itemscope itemtype="https://schema.org/AggregateRating">';
        $microdata .= '<meta itemprop="ratingValue" content="' . $rating_stats->average_rating . '">';
        $microdata .= '<meta itemprop="reviewCount" content="' . $rating_stats->total_reviews . '">';
        $microdata .= '<meta itemprop="bestRating" content="5">';
        $microdata .= '<meta itemprop="worstRating" content="1">';
        $microdata .= '</div>';
        
        $microdata .= '</div>';
        
        return $microdata . $content;
    }
    
    /**
     * Determinar el tipo de contenido para Schema.org
     */
    private function get_content_type($post) {
        // Verificar si es un producto de WooCommerce
        if ($this->is_product_post($post)) {
            return 'Product';
        }
        
        // Verificar otras taxonomías o tipos de post
        $post_type = get_post_type($post);
        
        switch ($post_type) {
            case 'product':
                return 'Product';
            case 'service':
                return 'Service';
            case 'recipe':
                return 'Recipe';
            case 'book':
                return 'Book';
            case 'movie':
                return 'Movie';
            case 'event':
                return 'Event';
            default:
                // SIEMPRE DEVOLVER 'Product' PARA PERMITIR RESEÑAS
                // Esto evita errores de Google Rich Results con 'Article'
                return 'Product';
        }
    }
    
    /**
     * Obtener tipo de microdatos
     */
    private function get_microdata_type($post) {
        $schema_type = $this->get_content_type($post);
        return 'https://schema.org/' . $schema_type;
    }
    
    /**
     * Verificar si es un producto
     */
    private function is_product_post($post) {
        return get_post_type($post) === 'product' || 
               (function_exists('wc_get_product') && wc_get_product($post->ID));
    }
    
    /**
     * Mejorar schema para productos
     */
    private function enhance_product_schema($schema, $post) {
        if (function_exists('wc_get_product')) {
            $product = wc_get_product($post->ID);
            
            if ($product) {
                $schema['@type'] = 'Product';
                $schema['sku'] = $product->get_sku();
                $schema['brand'] = array(
                    '@type' => 'Brand',
                    'name' => get_bloginfo('name')
                );
                
                // Precio del producto
                if ($product->get_price()) {
                    $schema['offers'] = array(
                        '@type' => 'Offer',
                        'price' => $product->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability' => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url' => get_permalink($post->ID)
                    );
                }
            }
        } else {
            // Para productos no WooCommerce, añadir datos básicos
            $schema['@type'] = 'Product';
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => get_bloginfo('name')
            );
        }
        
        return $schema;
    }
    
    /**
     * Obtener descripción del post
     */
    private function get_post_description($post) {
        // Intentar obtener meta descripción
        if (function_exists('get_post_meta')) {
            $meta_desc = get_post_meta($post->ID, '_yoast_wpseo_metadesc', true);
            if ($meta_desc) {
                return $meta_desc;
            }
        }
        
        // Usar excerpt si está disponible
        if ($post->post_excerpt) {
            return wp_strip_all_tags($post->post_excerpt);
        }
        
        // Usar las primeras 160 caracteres del contenido
        $content = wp_strip_all_tags($post->post_content);
        return mb_substr($content, 0, 160) . (mb_strlen($content) > 160 ? '...' : '');
    }
    
    /**
     * Obtener imagen del post
     */
    private function get_post_image($post) {
        // Imagen destacada
        if (has_post_thumbnail($post->ID)) {
            return get_the_post_thumbnail_url($post->ID, 'large');
        }
        
        // Primera imagen del contenido
        preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post->post_content, $matches);
        if (!empty($matches[1])) {
            return $matches[1];
        }
        
        // Logo del sitio como fallback
        if (function_exists('get_custom_logo')) {
            $custom_logo_id = get_theme_mod('custom_logo');
            if ($custom_logo_id) {
                return wp_get_attachment_image_url($custom_logo_id, 'large');
            }
        }
        
        return null;
    }
    
    /**
     * Generar breadcrumbs para rich snippets
     */
    public function generate_breadcrumbs_schema() {
        if (!is_single() && !is_page()) {
            return;
        }
        
        global $post;
        
        $breadcrumbs = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        $position = 1;
        
        // Página de inicio
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position++,
            'name' => get_bloginfo('name'),
            'item' => home_url('/')
        );
        
        // Categorías (para posts)
        if (is_single() && get_post_type() === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $category = $categories[0];
                $breadcrumbs['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id)
                );
            }
        }
        
        // Página/Post actual
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title($post->ID),
            'item' => get_permalink($post->ID)
        );
        
        echo '<script type="application/ld+json">' . json_encode($breadcrumbs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
    }
    
    /**
     * Generar schema FAQ desde comentarios frecuentes
     */
    public function generate_faq_schema($post_id) {
        $comments = $this->database->get_comments(array(
            'post_id' => $post_id,
            'status' => 'approved',
            'limit' => 5
        ));
        
        if (empty($comments)) {
            return;
        }
        
        $faq_schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array()
        );
        
        foreach ($comments as $comment) {
            if (!empty($comment->title) && !empty($comment->content)) {
                $faq_schema['mainEntity'][] = array(
                    '@type' => 'Question',
                    'name' => $comment->title,
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $comment->content,
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $comment->author_name
                        )
                    )
                );
            }
        }
        
        if (!empty($faq_schema['mainEntity'])) {
            echo '<script type="application/ld+json">' . json_encode($faq_schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>' . "\n";
        }
    }
}