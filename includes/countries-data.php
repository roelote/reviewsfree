<?php
/**
 * Lista completa de países con códigos ISO y banderas
 * Para uso en el formulario de comentarios con autocompletado
 */

if (!defined('ABSPATH')) {
    exit;
}

class ComentariosFree_Countries {
    
    /**
     * Obtener array completo de países con códigos y banderas
     * @return array Array de países en formato [código => [nombre, bandera]]
     */
    public static function get_countries_list() {
        return array(
            '' => array('name' => 'Elegir país', 'flag' => '🌍'),
            'AD' => array('name' => 'Andorra', 'flag' => '🇦🇩'),
            'AE' => array('name' => 'Emiratos Árabes Unidos', 'flag' => '🇦🇪'),
            'AF' => array('name' => 'Afganistán', 'flag' => '🇦🇫'),
            'AG' => array('name' => 'Antigua y Barbuda', 'flag' => '🇦🇬'),
            'AI' => array('name' => 'Anguila', 'flag' => '🇦🇮'),
            'AL' => array('name' => 'Albania', 'flag' => '🇦🇱'),
            'AM' => array('name' => 'Armenia', 'flag' => '🇦🇲'),
            'AO' => array('name' => 'Angola', 'flag' => '🇦🇴'),
            'AQ' => array('name' => 'Antártida', 'flag' => '🇦🇶'),
            'AR' => array('name' => 'Argentina', 'flag' => '🇦🇷'),
            'AS' => array('name' => 'Samoa Americana', 'flag' => '🇦🇸'),
            'AT' => array('name' => 'Austria', 'flag' => '🇦🇹'),
            'AU' => array('name' => 'Australia', 'flag' => '🇦🇺'),
            'AW' => array('name' => 'Aruba', 'flag' => '🇦🇼'),
            'AX' => array('name' => 'Islas Åland', 'flag' => '🇦🇽'),
            'AZ' => array('name' => 'Azerbaiyán', 'flag' => '🇦🇿'),
            'BA' => array('name' => 'Bosnia y Herzegovina', 'flag' => '🇧🇦'),
            'BB' => array('name' => 'Barbados', 'flag' => '🇧🇧'),
            'BD' => array('name' => 'Bangladesh', 'flag' => '🇧🇩'),
            'BE' => array('name' => 'Bélgica', 'flag' => '🇧🇪'),
            'BF' => array('name' => 'Burkina Faso', 'flag' => '🇧🇫'),
            'BG' => array('name' => 'Bulgaria', 'flag' => '🇧🇬'),
            'BH' => array('name' => 'Baréin', 'flag' => '🇧🇭'),
            'BI' => array('name' => 'Burundi', 'flag' => '🇧🇮'),
            'BJ' => array('name' => 'Benín', 'flag' => '🇧🇯'),
            'BL' => array('name' => 'San Bartolomé', 'flag' => '🇧🇱'),
            'BM' => array('name' => 'Bermudas', 'flag' => '🇧🇲'),
            'BN' => array('name' => 'Brunéi', 'flag' => '🇧🇳'),
            'BO' => array('name' => 'Bolivia', 'flag' => '🇧🇴'),
            'BQ' => array('name' => 'Bonaire, San Eustaquio y Saba', 'flag' => '🇧🇶'),
            'BR' => array('name' => 'Brasil', 'flag' => '🇧🇷'),
            'BS' => array('name' => 'Bahamas', 'flag' => '🇧🇸'),
            'BT' => array('name' => 'Bután', 'flag' => '🇧🇹'),
            'BV' => array('name' => 'Isla Bouvet', 'flag' => '🇧🇻'),
            'BW' => array('name' => 'Botsuana', 'flag' => '🇧🇼'),
            'BY' => array('name' => 'Bielorrusia', 'flag' => '🇧🇾'),
            'BZ' => array('name' => 'Belice', 'flag' => '🇧🇿'),
            'CA' => array('name' => 'Canadá', 'flag' => '🇨🇦'),
            'CC' => array('name' => 'Islas Cocos', 'flag' => '🇨🇨'),
            'CD' => array('name' => 'República Democrática del Congo', 'flag' => '🇨🇩'),
            'CF' => array('name' => 'República Centroafricana', 'flag' => '🇨🇫'),
            'CG' => array('name' => 'República del Congo', 'flag' => '🇨🇬'),
            'CH' => array('name' => 'Suiza', 'flag' => '🇨🇭'),
            'CI' => array('name' => 'Costa de Marfil', 'flag' => '🇨🇮'),
            'CK' => array('name' => 'Islas Cook', 'flag' => '🇨🇰'),
            'CL' => array('name' => 'Chile', 'flag' => '🇨🇱'),
            'CM' => array('name' => 'Camerún', 'flag' => '🇨🇲'),
            'CN' => array('name' => 'China', 'flag' => '🇨🇳'),
            'CO' => array('name' => 'Colombia', 'flag' => '🇨🇴'),
            'CR' => array('name' => 'Costa Rica', 'flag' => '🇨🇷'),
            'CU' => array('name' => 'Cuba', 'flag' => '🇨🇺'),
            'CV' => array('name' => 'Cabo Verde', 'flag' => '🇨🇻'),
            'CW' => array('name' => 'Curazao', 'flag' => '🇨🇼'),
            'CX' => array('name' => 'Isla de Navidad', 'flag' => '🇨🇽'),
            'CY' => array('name' => 'Chipre', 'flag' => '🇨🇾'),
            'CZ' => array('name' => 'República Checa', 'flag' => '🇨🇿'),
            'DE' => array('name' => 'Alemania', 'flag' => '🇩🇪'),
            'DJ' => array('name' => 'Yibuti', 'flag' => '🇩🇯'),
            'DK' => array('name' => 'Dinamarca', 'flag' => '🇩🇰'),
            'DM' => array('name' => 'Dominica', 'flag' => '🇩🇲'),
            'DO' => array('name' => 'República Dominicana', 'flag' => '🇩🇴'),
            'DZ' => array('name' => 'Argelia', 'flag' => '🇩🇿'),
            'EC' => array('name' => 'Ecuador', 'flag' => '🇪🇨'),
            'EE' => array('name' => 'Estonia', 'flag' => '🇪🇪'),
            'EG' => array('name' => 'Egipto', 'flag' => '🇪🇬'),
            'EH' => array('name' => 'Sahara Occidental', 'flag' => '🇪🇭'),
            'ER' => array('name' => 'Eritrea', 'flag' => '🇪🇷'),
            'ES' => array('name' => 'España', 'flag' => '🇪🇸'),
            'ET' => array('name' => 'Etiopía', 'flag' => '🇪🇹'),
            'FI' => array('name' => 'Finlandia', 'flag' => '🇫🇮'),
            'FJ' => array('name' => 'Fiyi', 'flag' => '🇫🇯'),
            'FK' => array('name' => 'Islas Malvinas', 'flag' => '🇫🇰'),
            'FM' => array('name' => 'Estados Federados de Micronesia', 'flag' => '🇫🇲'),
            'FO' => array('name' => 'Islas Feroe', 'flag' => '🇫🇴'),
            'FR' => array('name' => 'Francia', 'flag' => '🇫🇷'),
            'GA' => array('name' => 'Gabón', 'flag' => '🇬🇦'),
            'GB' => array('name' => 'Reino Unido', 'flag' => '🇬🇧'),
            'GD' => array('name' => 'Granada', 'flag' => '🇬🇩'),
            'GE' => array('name' => 'Georgia', 'flag' => '🇬🇪'),
            'GF' => array('name' => 'Guayana Francesa', 'flag' => '🇬🇫'),
            'GG' => array('name' => 'Guernsey', 'flag' => '🇬🇬'),
            'GH' => array('name' => 'Ghana', 'flag' => '🇬🇭'),
            'GI' => array('name' => 'Gibraltar', 'flag' => '🇬🇮'),
            'GL' => array('name' => 'Groenlandia', 'flag' => '🇬🇱'),
            'GM' => array('name' => 'Gambia', 'flag' => '🇬🇲'),
            'GN' => array('name' => 'Guinea', 'flag' => '🇬🇳'),
            'GP' => array('name' => 'Guadalupe', 'flag' => '🇬🇵'),
            'GQ' => array('name' => 'Guinea Ecuatorial', 'flag' => '🇬🇶'),
            'GR' => array('name' => 'Grecia', 'flag' => '🇬🇷'),
            'GS' => array('name' => 'Islas Georgias del Sur y Sandwich del Sur', 'flag' => '🇬🇸'),
            'GT' => array('name' => 'Guatemala', 'flag' => '🇬🇹'),
            'GU' => array('name' => 'Guam', 'flag' => '🇬🇺'),
            'GW' => array('name' => 'Guinea-Bisáu', 'flag' => '🇬🇼'),
            'GY' => array('name' => 'Guyana', 'flag' => '🇬🇾'),
            'HK' => array('name' => 'Hong Kong', 'flag' => '🇭🇰'),
            'HM' => array('name' => 'Islas Heard y McDonald', 'flag' => '🇭🇲'),
            'HN' => array('name' => 'Honduras', 'flag' => '🇭🇳'),
            'HR' => array('name' => 'Croacia', 'flag' => '🇭🇷'),
            'HT' => array('name' => 'Haití', 'flag' => '🇭🇹'),
            'HU' => array('name' => 'Hungría', 'flag' => '🇭🇺'),
            'ID' => array('name' => 'Indonesia', 'flag' => '🇮🇩'),
            'IE' => array('name' => 'Irlanda', 'flag' => '🇮🇪'),
            'IL' => array('name' => 'Israel', 'flag' => '🇮🇱'),
            'IM' => array('name' => 'Isla de Man', 'flag' => '🇮🇲'),
            'IN' => array('name' => 'India', 'flag' => '🇮🇳'),
            'IO' => array('name' => 'Territorio Británico del Océano Índico', 'flag' => '🇮🇴'),
            'IQ' => array('name' => 'Irak', 'flag' => '🇮🇶'),
            'IR' => array('name' => 'Irán', 'flag' => '🇮🇷'),
            'IS' => array('name' => 'Islandia', 'flag' => '🇮🇸'),
            'IT' => array('name' => 'Italia', 'flag' => '🇮🇹'),
            'JE' => array('name' => 'Jersey', 'flag' => '🇯🇪'),
            'JM' => array('name' => 'Jamaica', 'flag' => '🇯🇲'),
            'JO' => array('name' => 'Jordania', 'flag' => '🇯🇴'),
            'JP' => array('name' => 'Japón', 'flag' => '🇯🇵'),
            'KE' => array('name' => 'Kenia', 'flag' => '🇰🇪'),
            'KG' => array('name' => 'Kirguistán', 'flag' => '🇰🇬'),
            'KH' => array('name' => 'Camboya', 'flag' => '🇰🇭'),
            'KI' => array('name' => 'Kiribati', 'flag' => '🇰🇮'),
            'KM' => array('name' => 'Comoras', 'flag' => '🇰🇲'),
            'KN' => array('name' => 'San Cristóbal y Nieves', 'flag' => '🇰🇳'),
            'KP' => array('name' => 'Corea del Norte', 'flag' => '🇰🇵'),
            'KR' => array('name' => 'Corea del Sur', 'flag' => '🇰🇷'),
            'KW' => array('name' => 'Kuwait', 'flag' => '🇰🇼'),
            'KY' => array('name' => 'Islas Caimán', 'flag' => '🇰🇾'),
            'KZ' => array('name' => 'Kazajistán', 'flag' => '🇰🇿'),
            'LA' => array('name' => 'Laos', 'flag' => '🇱🇦'),
            'LB' => array('name' => 'Líbano', 'flag' => '🇱🇧'),
            'LC' => array('name' => 'Santa Lucía', 'flag' => '🇱🇨'),
            'LI' => array('name' => 'Liechtenstein', 'flag' => '🇱🇮'),
            'LK' => array('name' => 'Sri Lanka', 'flag' => '🇱🇰'),
            'LR' => array('name' => 'Liberia', 'flag' => '🇱🇷'),
            'LS' => array('name' => 'Lesoto', 'flag' => '🇱🇸'),
            'LT' => array('name' => 'Lituania', 'flag' => '🇱🇹'),
            'LU' => array('name' => 'Luxemburgo', 'flag' => '🇱🇺'),
            'LV' => array('name' => 'Letonia', 'flag' => '🇱🇻'),
            'LY' => array('name' => 'Libia', 'flag' => '🇱🇾'),
            'MA' => array('name' => 'Marruecos', 'flag' => '🇲🇦'),
            'MC' => array('name' => 'Mónaco', 'flag' => '🇲🇨'),
            'MD' => array('name' => 'Moldavia', 'flag' => '🇲🇩'),
            'ME' => array('name' => 'Montenegro', 'flag' => '🇲🇪'),
            'MF' => array('name' => 'San Martín', 'flag' => '🇲🇫'),
            'MG' => array('name' => 'Madagascar', 'flag' => '🇲🇬'),
            'MH' => array('name' => 'Islas Marshall', 'flag' => '🇲🇭'),
            'MK' => array('name' => 'Macedonia del Norte', 'flag' => '🇲🇰'),
            'ML' => array('name' => 'Malí', 'flag' => '🇲🇱'),
            'MM' => array('name' => 'Myanmar', 'flag' => '🇲🇲'),
            'MN' => array('name' => 'Mongolia', 'flag' => '🇲🇳'),
            'MO' => array('name' => 'Macao', 'flag' => '🇲🇴'),
            'MP' => array('name' => 'Islas Marianas del Norte', 'flag' => '🇲🇵'),
            'MQ' => array('name' => 'Martinica', 'flag' => '🇲🇶'),
            'MR' => array('name' => 'Mauritania', 'flag' => '🇲🇷'),
            'MS' => array('name' => 'Montserrat', 'flag' => '🇲🇸'),
            'MT' => array('name' => 'Malta', 'flag' => '🇲🇹'),
            'MU' => array('name' => 'Mauricio', 'flag' => '🇲🇺'),
            'MV' => array('name' => 'Maldivas', 'flag' => '🇲🇻'),
            'MW' => array('name' => 'Malaui', 'flag' => '🇲🇼'),
            'MX' => array('name' => 'México', 'flag' => '🇲🇽'),
            'MY' => array('name' => 'Malasia', 'flag' => '🇲🇾'),
            'MZ' => array('name' => 'Mozambique', 'flag' => '🇲🇿'),
            'NA' => array('name' => 'Namibia', 'flag' => '🇳🇦'),
            'NC' => array('name' => 'Nueva Caledonia', 'flag' => '🇳🇨'),
            'NE' => array('name' => 'Níger', 'flag' => '🇳🇪'),
            'NF' => array('name' => 'Isla Norfolk', 'flag' => '🇳🇫'),
            'NG' => array('name' => 'Nigeria', 'flag' => '🇳🇬'),
            'NI' => array('name' => 'Nicaragua', 'flag' => '🇳🇮'),
            'NL' => array('name' => 'Países Bajos', 'flag' => '🇳🇱'),
            'NO' => array('name' => 'Noruega', 'flag' => '🇳🇴'),
            'NP' => array('name' => 'Nepal', 'flag' => '🇳🇵'),
            'NR' => array('name' => 'Nauru', 'flag' => '🇳🇷'),
            'NU' => array('name' => 'Niue', 'flag' => '🇳🇺'),
            'NZ' => array('name' => 'Nueva Zelanda', 'flag' => '🇳🇿'),
            'OM' => array('name' => 'Omán', 'flag' => '🇴🇲'),
            'PA' => array('name' => 'Panamá', 'flag' => '🇵🇦'),
            'PE' => array('name' => 'Perú', 'flag' => '🇵🇪'),
            'PF' => array('name' => 'Polinesia Francesa', 'flag' => '🇵🇫'),
            'PG' => array('name' => 'Papúa Nueva Guinea', 'flag' => '🇵🇬'),
            'PH' => array('name' => 'Filipinas', 'flag' => '🇵🇭'),
            'PK' => array('name' => 'Pakistán', 'flag' => '🇵🇰'),
            'PL' => array('name' => 'Polonia', 'flag' => '🇵🇱'),
            'PM' => array('name' => 'San Pedro y Miquelón', 'flag' => '🇵🇲'),
            'PN' => array('name' => 'Islas Pitcairn', 'flag' => '🇵🇳'),
            'PR' => array('name' => 'Puerto Rico', 'flag' => '🇵🇷'),
            'PS' => array('name' => 'Palestina', 'flag' => '🇵🇸'),
            'PT' => array('name' => 'Portugal', 'flag' => '🇵🇹'),
            'PW' => array('name' => 'Palaos', 'flag' => '🇵🇼'),
            'PY' => array('name' => 'Paraguay', 'flag' => '🇵🇾'),
            'QA' => array('name' => 'Catar', 'flag' => '🇶🇦'),
            'RE' => array('name' => 'Reunión', 'flag' => '🇷🇪'),
            'RO' => array('name' => 'Rumania', 'flag' => '🇷🇴'),
            'RS' => array('name' => 'Serbia', 'flag' => '🇷🇸'),
            'RU' => array('name' => 'Rusia', 'flag' => '🇷🇺'),
            'RW' => array('name' => 'Ruanda', 'flag' => '🇷🇼'),
            'SA' => array('name' => 'Arabia Saudí', 'flag' => '🇸🇦'),
            'SB' => array('name' => 'Islas Salomón', 'flag' => '🇸🇧'),
            'SC' => array('name' => 'Seychelles', 'flag' => '🇸🇨'),
            'SD' => array('name' => 'Sudán', 'flag' => '🇸🇩'),
            'SE' => array('name' => 'Suecia', 'flag' => '🇸🇪'),
            'SG' => array('name' => 'Singapur', 'flag' => '🇸🇬'),
            'SH' => array('name' => 'Santa Elena', 'flag' => '🇸🇭'),
            'SI' => array('name' => 'Eslovenia', 'flag' => '🇸🇮'),
            'SJ' => array('name' => 'Svalbard y Jan Mayen', 'flag' => '🇸🇯'),
            'SK' => array('name' => 'Eslovaquia', 'flag' => '🇸🇰'),
            'SL' => array('name' => 'Sierra Leona', 'flag' => '🇸🇱'),
            'SM' => array('name' => 'San Marino', 'flag' => '🇸🇲'),
            'SN' => array('name' => 'Senegal', 'flag' => '🇸🇳'),
            'SO' => array('name' => 'Somalia', 'flag' => '🇸🇴'),
            'SR' => array('name' => 'Surinam', 'flag' => '🇸🇷'),
            'SS' => array('name' => 'Sudán del Sur', 'flag' => '🇸🇸'),
            'ST' => array('name' => 'Santo Tomé y Príncipe', 'flag' => '🇸🇹'),
            'SV' => array('name' => 'El Salvador', 'flag' => '🇸🇻'),
            'SX' => array('name' => 'Sint Maarten', 'flag' => '🇸🇽'),
            'SY' => array('name' => 'Siria', 'flag' => '🇸🇾'),
            'SZ' => array('name' => 'Esuatini', 'flag' => '🇸🇿'),
            'TC' => array('name' => 'Islas Turcas y Caicos', 'flag' => '🇹🇨'),
            'TD' => array('name' => 'Chad', 'flag' => '🇹🇩'),
            'TF' => array('name' => 'Territorios Australes Franceses', 'flag' => '🇹🇫'),
            'TG' => array('name' => 'Togo', 'flag' => '🇹🇬'),
            'TH' => array('name' => 'Tailandia', 'flag' => '🇹🇭'),
            'TJ' => array('name' => 'Tayikistán', 'flag' => '🇹🇯'),
            'TK' => array('name' => 'Tokelau', 'flag' => '🇹🇰'),
            'TL' => array('name' => 'Timor Oriental', 'flag' => '🇹🇱'),
            'TM' => array('name' => 'Turkmenistán', 'flag' => '🇹🇲'),
            'TN' => array('name' => 'Túnez', 'flag' => '🇹🇳'),
            'TO' => array('name' => 'Tonga', 'flag' => '🇹🇴'),
            'TR' => array('name' => 'Turquía', 'flag' => '🇹🇷'),
            'TT' => array('name' => 'Trinidad y Tobago', 'flag' => '🇹🇹'),
            'TV' => array('name' => 'Tuvalu', 'flag' => '🇹🇻'),
            'TW' => array('name' => 'Taiwán', 'flag' => '🇹🇼'),
            'TZ' => array('name' => 'Tanzania', 'flag' => '🇹🇿'),
            'UA' => array('name' => 'Ucrania', 'flag' => '🇺🇦'),
            'UG' => array('name' => 'Uganda', 'flag' => '🇺🇬'),
            'UM' => array('name' => 'Islas Ultramarinas de Estados Unidos', 'flag' => '🇺🇲'),
            'US' => array('name' => 'Estados Unidos', 'flag' => '🇺🇸'),
            'UY' => array('name' => 'Uruguay', 'flag' => '🇺🇾'),
            'UZ' => array('name' => 'Uzbekistán', 'flag' => '🇺🇿'),
            'VA' => array('name' => 'Ciudad del Vaticano', 'flag' => '🇻🇦'),
            'VC' => array('name' => 'San Vicente y las Granadinas', 'flag' => '🇻🇨'),
            'VE' => array('name' => 'Venezuela', 'flag' => '🇻🇪'),
            'VG' => array('name' => 'Islas Vírgenes Británicas', 'flag' => '🇻🇬'),
            'VI' => array('name' => 'Islas Vírgenes de los Estados Unidos', 'flag' => '🇻🇮'),
            'VN' => array('name' => 'Vietnam', 'flag' => '🇻🇳'),
            'VU' => array('name' => 'Vanuatu', 'flag' => '🇻🇺'),
            'WF' => array('name' => 'Wallis y Futuna', 'flag' => '🇼🇫'),
            'WS' => array('name' => 'Samoa', 'flag' => '🇼🇸'),
            'YE' => array('name' => 'Yemen', 'flag' => '🇾🇪'),
            'YT' => array('name' => 'Mayotte', 'flag' => '🇾🇹'),
            'ZA' => array('name' => 'Sudáfrica', 'flag' => '🇿🇦'),
            'ZM' => array('name' => 'Zambia', 'flag' => '🇿🇲'),
            'ZW' => array('name' => 'Zimbabue', 'flag' => '🇿🇼')
        );
    }
    
    /**
     * Obtener lista de países en formato JSON para JavaScript
     * @param string $lang Idioma (es, en, pt-br, fr, it)
     * @return string JSON string
     */
    public static function get_countries_json($lang = 'es') {
        $countries = self::get_countries_list();
        
        // Traducir nombres de países
        foreach ($countries as $code => $data) {
            if ($code !== '') { // Saltar la opción vacía
                $countries[$code]['name'] = self::get_country_name($code, $lang);
            }
        }
        
        return json_encode($countries, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Obtener bandera de un país por código o nombre
     * @param string $country_identifier Código del país (ej: 'ES') o nombre completo (ej: 'España')
     * @return string Bandera emoji
     */
    public static function get_country_flag($country_identifier) {
        $countries = self::get_countries_list();
        
        if (empty($country_identifier)) {
            return '🌍'; // Icono de mundo por defecto
        }
        
        // Limpiar espacios y normalizar
        $country_identifier = trim($country_identifier);
        
        // Primero intentar buscar por código ISO (más eficiente)
        if (isset($countries[$country_identifier])) {
            return $countries[$country_identifier]['flag'];
        }
        
        // Si no se encuentra, buscar por nombre completo (compatibilidad hacia atrás)
        $name_to_code_map = array(
            'Argentina' => 'AR',
            'Bolivia' => 'BO',
            'Brasil' => 'BR',
            'Brazil' => 'BR',
            'Canadá' => 'CA',
            'Canada' => 'CA',
            'Chile' => 'CL',
            'Colombia' => 'CO',
            'Costa Rica' => 'CR',
            'Cuba' => 'CU',
            'Alemania' => 'DE',
            'Germany' => 'DE',
            'República Dominicana' => 'DO',
            'Ecuador' => 'EC',
            'España' => 'ES',
            'Spain' => 'ES',
            'Francia' => 'FR',
            'France' => 'FR',
            'Reino Unido' => 'GB',
            'United Kingdom' => 'GB',
            'UK' => 'GB',
            'Guatemala' => 'GT',
            'Honduras' => 'HN',
            'Italia' => 'IT',
            'Italy' => 'IT',
            'México' => 'MX',
            'Mexico' => 'MX',
            'Nicaragua' => 'NI',
            'Panamá' => 'PA',
            'Panama' => 'PA',
            'Perú' => 'PE',
            'Peru' => 'PE',
            'Paraguay' => 'PY',
            'El Salvador' => 'SV',
            'Estados Unidos' => 'US',
            'United States' => 'US',
            'USA' => 'US',
            'Uruguay' => 'UY',
            'Venezuela' => 'VE'
        );
        
        if (isset($name_to_code_map[$country_identifier])) {
            $code = $name_to_code_map[$country_identifier];
            if (isset($countries[$code])) {
                return $countries[$code]['flag'];
            }
        }
        
        return '🌍'; // Icono de mundo por defecto si no se encuentra
    }
    
    /**
     * Obtener nombre de un país por código o nombre
     * @param string $country_identifier Código del país (ej: 'ES') o nombre completo (ej: 'España')
     * @return string Nombre del país
     */
    /**
     * Obtener nombre de país con soporte multiidioma
     * @param string $country_identifier Código ISO o nombre del país
     * @param string $lang Idioma (es, en, pt-br, fr, it) - opcional
     * @return string Nombre del país traducido
     */
    public static function get_country_name($country_identifier, $lang = 'es') {
        $countries = self::get_countries_list();
        
        if (empty($country_identifier)) {
            $default_names = array(
                'es' => 'Internacional',
                'en' => 'International',
                'pt-br' => 'Internacional',
                'fr' => 'International',
                'it' => 'Internazionale'
            );
            return isset($default_names[$lang]) ? $default_names[$lang] : 'Internacional';
        }
        
        // Limpiar espacios y normalizar
        $country_identifier = trim($country_identifier);
        $country_code = $country_identifier;
        
        // Si no es un código ISO, intentar convertir nombre a código
        if (strlen($country_identifier) > 2) {
            $name_to_code_map = array(
                'Argentina' => 'AR', 'Bolivia' => 'BO', 'Brasil' => 'BR', 'Brazil' => 'BR',
                'Canadá' => 'CA', 'Canada' => 'CA', 'Chile' => 'CL', 'Colombia' => 'CO',
                'Costa Rica' => 'CR', 'Cuba' => 'CU', 'Alemania' => 'DE', 'Germany' => 'DE',
                'República Dominicana' => 'DO', 'Ecuador' => 'EC', 'España' => 'ES', 'Spain' => 'ES',
                'Francia' => 'FR', 'France' => 'FR', 'Reino Unido' => 'GB', 'United Kingdom' => 'GB',
                'UK' => 'GB', 'Guatemala' => 'GT', 'Honduras' => 'HN', 'Italia' => 'IT', 'Italy' => 'IT',
                'México' => 'MX', 'Mexico' => 'MX', 'Nicaragua' => 'NI', 'Panamá' => 'PA',
                'Panama' => 'PA', 'Perú' => 'PE', 'Peru' => 'PE', 'Paraguay' => 'PY',
                'El Salvador' => 'SV', 'Estados Unidos' => 'US', 'United States' => 'US', 'USA' => 'US',
                'Uruguay' => 'UY', 'Venezuela' => 'VE'
            );
            
            if (isset($name_to_code_map[$country_identifier])) {
                $country_code = $name_to_code_map[$country_identifier];
            }
        }
        
        // Array con traducciones de países principales
        $translations = array(
            'ES' => array('es' => 'España', 'en' => 'Spain', 'pt-br' => 'Espanha', 'fr' => 'Espagne', 'it' => 'Spagna'),
            'US' => array('es' => 'Estados Unidos', 'en' => 'United States', 'pt-br' => 'Estados Unidos', 'fr' => 'États-Unis', 'it' => 'Stati Uniti'),
            'GB' => array('es' => 'Reino Unido', 'en' => 'United Kingdom', 'pt-br' => 'Reino Unido', 'fr' => 'Royaume-Uni', 'it' => 'Regno Unito'),
            'FR' => array('es' => 'Francia', 'en' => 'France', 'pt-br' => 'França', 'fr' => 'France', 'it' => 'Francia'),
            'DE' => array('es' => 'Alemania', 'en' => 'Germany', 'pt-br' => 'Alemanha', 'fr' => 'Allemagne', 'it' => 'Germania'),
            'IT' => array('es' => 'Italia', 'en' => 'Italy', 'pt-br' => 'Itália', 'fr' => 'Italie', 'it' => 'Italia'),
            'BR' => array('es' => 'Brasil', 'en' => 'Brazil', 'pt-br' => 'Brasil', 'fr' => 'Brésil', 'it' => 'Brasile'),
            'AR' => array('es' => 'Argentina', 'en' => 'Argentina', 'pt-br' => 'Argentina', 'fr' => 'Argentine', 'it' => 'Argentina'),
            'MX' => array('es' => 'México', 'en' => 'Mexico', 'pt-br' => 'México', 'fr' => 'Mexique', 'it' => 'Messico'),
            'CO' => array('es' => 'Colombia', 'en' => 'Colombia', 'pt-br' => 'Colômbia', 'fr' => 'Colombie', 'it' => 'Colombia'),
            'CL' => array('es' => 'Chile', 'en' => 'Chile', 'pt-br' => 'Chile', 'fr' => 'Chili', 'it' => 'Cile'),
            'PE' => array('es' => 'Perú', 'en' => 'Peru', 'pt-br' => 'Peru', 'fr' => 'Pérou', 'it' => 'Perù'),
            'VE' => array('es' => 'Venezuela', 'en' => 'Venezuela', 'pt-br' => 'Venezuela', 'fr' => 'Venezuela', 'it' => 'Venezuela'),
            'UY' => array('es' => 'Uruguay', 'en' => 'Uruguay', 'pt-br' => 'Uruguai', 'fr' => 'Uruguay', 'it' => 'Uruguay'),
            'EC' => array('es' => 'Ecuador', 'en' => 'Ecuador', 'pt-br' => 'Equador', 'fr' => 'Équateur', 'it' => 'Ecuador'),
            'BO' => array('es' => 'Bolivia', 'en' => 'Bolivia', 'pt-br' => 'Bolívia', 'fr' => 'Bolivie', 'it' => 'Bolivia'),
            'PY' => array('es' => 'Paraguay', 'en' => 'Paraguay', 'pt-br' => 'Paraguai', 'fr' => 'Paraguay', 'it' => 'Paraguay'),
            'CR' => array('es' => 'Costa Rica', 'en' => 'Costa Rica', 'pt-br' => 'Costa Rica', 'fr' => 'Costa Rica', 'it' => 'Costa Rica'),
            'PA' => array('es' => 'Panamá', 'en' => 'Panama', 'pt-br' => 'Panamá', 'fr' => 'Panama', 'it' => 'Panama'),
            'CU' => array('es' => 'Cuba', 'en' => 'Cuba', 'pt-br' => 'Cuba', 'fr' => 'Cuba', 'it' => 'Cuba'),
            'DO' => array('es' => 'República Dominicana', 'en' => 'Dominican Republic', 'pt-br' => 'República Dominicana', 'fr' => 'République Dominicaine', 'it' => 'Repubblica Dominicana'),
            'GT' => array('es' => 'Guatemala', 'en' => 'Guatemala', 'pt-br' => 'Guatemala', 'fr' => 'Guatemala', 'it' => 'Guatemala'),
            'HN' => array('es' => 'Honduras', 'en' => 'Honduras', 'pt-br' => 'Honduras', 'fr' => 'Honduras', 'it' => 'Honduras'),
            'NI' => array('es' => 'Nicaragua', 'en' => 'Nicaragua', 'pt-br' => 'Nicarágua', 'fr' => 'Nicaragua', 'it' => 'Nicaragua'),
            'SV' => array('es' => 'El Salvador', 'en' => 'El Salvador', 'pt-br' => 'El Salvador', 'fr' => 'Salvador', 'it' => 'El Salvador'),
            'CA' => array('es' => 'Canadá', 'en' => 'Canada', 'pt-br' => 'Canadá', 'fr' => 'Canada', 'it' => 'Canada'),
            'PT' => array('es' => 'Portugal', 'en' => 'Portugal', 'pt-br' => 'Portugal', 'fr' => 'Portugal', 'it' => 'Portogallo'),
            'NL' => array('es' => 'Países Bajos', 'en' => 'Netherlands', 'pt-br' => 'Países Baixos', 'fr' => 'Pays-Bas', 'it' => 'Paesi Bassi'),
            'BE' => array('es' => 'Bélgica', 'en' => 'Belgium', 'pt-br' => 'Bélgica', 'fr' => 'Belgique', 'it' => 'Belgio'),
            'CH' => array('es' => 'Suiza', 'en' => 'Switzerland', 'pt-br' => 'Suíça', 'fr' => 'Suisse', 'it' => 'Svizzera'),
            'AT' => array('es' => 'Austria', 'en' => 'Austria', 'pt-br' => 'Áustria', 'fr' => 'Autriche', 'it' => 'Austria'),
            'GR' => array('es' => 'Grecia', 'en' => 'Greece', 'pt-br' => 'Grécia', 'fr' => 'Grèce', 'it' => 'Grecia'),
            'PL' => array('es' => 'Polonia', 'en' => 'Poland', 'pt-br' => 'Polônia', 'fr' => 'Pologne', 'it' => 'Polonia'),
            'RU' => array('es' => 'Rusia', 'en' => 'Russia', 'pt-br' => 'Rússia', 'fr' => 'Russie', 'it' => 'Russia'),
            'CN' => array('es' => 'China', 'en' => 'China', 'pt-br' => 'China', 'fr' => 'Chine', 'it' => 'Cina'),
            'JP' => array('es' => 'Japón', 'en' => 'Japan', 'pt-br' => 'Japão', 'fr' => 'Japon', 'it' => 'Giappone'),
            'KR' => array('es' => 'Corea del Sur', 'en' => 'South Korea', 'pt-br' => 'Coreia do Sul', 'fr' => 'Corée du Sud', 'it' => 'Corea del Sud'),
            'IN' => array('es' => 'India', 'en' => 'India', 'pt-br' => 'Índia', 'fr' => 'Inde', 'it' => 'India'),
            'AU' => array('es' => 'Australia', 'en' => 'Australia', 'pt-br' => 'Austrália', 'fr' => 'Australie', 'it' => 'Australia'),
            'NZ' => array('es' => 'Nueva Zelanda', 'en' => 'New Zealand', 'pt-br' => 'Nova Zelândia', 'fr' => 'Nouvelle-Zélande', 'it' => 'Nuova Zelanda'),
            'ZA' => array('es' => 'Sudáfrica', 'en' => 'South Africa', 'pt-br' => 'África do Sul', 'fr' => 'Afrique du Sud', 'it' => 'Sudafrica'),
            'EG' => array('es' => 'Egipto', 'en' => 'Egypt', 'pt-br' => 'Egito', 'fr' => 'Égypte', 'it' => 'Egitto'),
            'MA' => array('es' => 'Marruecos', 'en' => 'Morocco', 'pt-br' => 'Marrocos', 'fr' => 'Maroc', 'it' => 'Marocco'),
            'TR' => array('es' => 'Turquía', 'en' => 'Turkey', 'pt-br' => 'Turquia', 'fr' => 'Turquie', 'it' => 'Turchia'),
            'IL' => array('es' => 'Israel', 'en' => 'Israel', 'pt-br' => 'Israel', 'fr' => 'Israël', 'it' => 'Israele'),
            'TH' => array('es' => 'Tailandia', 'en' => 'Thailand', 'pt-br' => 'Tailândia', 'fr' => 'Thaïlande', 'it' => 'Tailandia'),
            'VN' => array('es' => 'Vietnam', 'en' => 'Vietnam', 'pt-br' => 'Vietnã', 'fr' => 'Vietnam', 'it' => 'Vietnam'),
            'ID' => array('es' => 'Indonesia', 'en' => 'Indonesia', 'pt-br' => 'Indonésia', 'fr' => 'Indonésie', 'it' => 'Indonesia'),
            'MY' => array('es' => 'Malasia', 'en' => 'Malaysia', 'pt-br' => 'Malásia', 'fr' => 'Malaisie', 'it' => 'Malesia'),
            'SG' => array('es' => 'Singapur', 'en' => 'Singapore', 'pt-br' => 'Singapura', 'fr' => 'Singapour', 'it' => 'Singapore'),
            'PH' => array('es' => 'Filipinas', 'en' => 'Philippines', 'pt-br' => 'Filipinas', 'fr' => 'Philippines', 'it' => 'Filippine'),
        );
        
        // Si existe traducción para este país y idioma
        if (isset($translations[$country_code][$lang])) {
            return $translations[$country_code][$lang];
        }
        
        // Fallback a español si existe traducción
        if (isset($translations[$country_code]['es'])) {
            return $translations[$country_code]['es'];
        }
        
        // Si no hay traducción, usar el nombre en español del array original
        if (isset($countries[$country_code])) {
            return $countries[$country_code]['name'];
        }
        
        // Último fallback: devolver el valor original
        return $country_identifier;
    }
    
}