<?php
/**
 * Sistema de traducciones para Comentarios Free
 * Soporta: ES (Español), EN (English), PT-BR (Português), FR (Français), IT (Italiano)
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Translations {
    
    private static $frontend = null;
    private static $forms = null;
    private static $messages = null;
    private static $js = null;
    private static $current_lang = null;
    
    /**
     * Inicializar arrays de traducciones (lazy loading)
     */
    private static function init() {
        if (self::$frontend === null) {
            self::$frontend = require_once __DIR__ . '/strings-frontend.php';
            self::$forms = require_once __DIR__ . '/strings-forms.php';
            self::$messages = require_once __DIR__ . '/strings-messages.php';
            self::$js = require_once __DIR__ . '/strings-js.php';
        }
    }
    
    /**
     * Obtener idioma actual
     * @return string Código de idioma (es, en, pt-br, fr, it)
     */
    public static function get_current_language() {
        if (self::$current_lang !== null) {
            return self::$current_lang;
        }
        
        // Prioridad 1: WPML
        if (defined('ICL_LANGUAGE_CODE')) {
            $lang = ICL_LANGUAGE_CODE;
            
            // Normalizar códigos de WPML
            if ($lang === 'pt-pt') {
                $lang = 'pt-br';
            }
            
            self::$current_lang = $lang;
            return $lang;
        }
        
        // Prioridad 2: Detectar por URL
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/en/') !== false) {
            self::$current_lang = 'en';
            return 'en';
        } elseif (strpos($uri, '/pt-br/') !== false || strpos($uri, '/pt/') !== false) {
            self::$current_lang = 'pt-br';
            return 'pt-br';
        } elseif (strpos($uri, '/fr/') !== false) {
            self::$current_lang = 'fr';
            return 'fr';
        } elseif (strpos($uri, '/it/') !== false) {
            self::$current_lang = 'it';
            return 'it';
        }
        
        // Fallback: español
        self::$current_lang = 'es';
        return 'es';
    }
    
    /**
     * Obtener traducción de una cadena
     * @param string $key Clave de la cadena
     * @param string|null $lang Idioma específico (opcional)
     * @return string Cadena traducida
     */
    public static function get($key, $lang = null) {
        self::init();
        
        if ($lang === null) {
            $lang = self::get_current_language();
        }
        
        // Buscar en todos los arrays
        $all_strings = array_merge(
            self::$frontend,
            self::$forms,
            self::$messages,
            self::$js
        );
        
        // Si existe la traducción para el idioma solicitado
        if (isset($all_strings[$key][$lang])) {
            return $all_strings[$key][$lang];
        }
        
        // Fallback a español
        if (isset($all_strings[$key]['es'])) {
            return $all_strings[$key]['es'];
        }
        
        // Si no existe la clave, retornar la clave misma
        return $key;
    }
    
    /**
     * Obtener todas las traducciones JavaScript
     * @param string|null $lang Idioma específico (opcional)
     * @return array Array de traducciones para JavaScript
     */
    public static function get_js_strings($lang = null) {
        self::init();
        
        if ($lang === null) {
            $lang = self::get_current_language();
        }
        
        $result = array();
        foreach (self::$js as $key => $translations) {
            $result[$key] = isset($translations[$lang]) ? $translations[$lang] : $translations['es'];
        }
        
        return $result;
    }
    
    /**
     * Obtener nombre de país traducido
     * @param string $country_code Código ISO del país
     * @param string|null $lang Idioma específico (opcional)
     * @return string Nombre del país traducido
     */
    public static function get_country($country_code, $lang = null) {
        if ($lang === null) {
            $lang = self::get_current_language();
        }
        
        return ComentariosFree_Countries::get_country_name($country_code, $lang);
    }
}

/**
 * Función helper global para traducciones
 * @param string $key Clave de la cadena
 * @return string Cadena traducida
 */
function cf_trans($key) {
    return ComentariosFree_Translations::get($key);
}
