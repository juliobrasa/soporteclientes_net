<?php
/**
 * Configuraci�n Apify - Mapeo de Plataformas y Flags
 * Versi�n corregida con nombres estandardizados
 */

class ApifyConfig 
{
    /**
     * Mapeo correcto de plataformas a flags del actor
     * NOTA: Nombres verificados con actor multi-OTAs real
     */
    public static $PLATFORM_FLAGS = [
        "booking" => "enableBooking",
        "googlemaps" => "enableGoogleMaps", 
        "tripadvisor" => "enableTripadvisor",
        "expedia" => "enableExpedia",
        "agoda" => "enableAgoda",
        "hotels.com" => "enableHotelsCom",  // CORRECCI�N: enableHotelsCom (no enableHotelscom)
        "hotelscom" => "enableHotelsCom"    // Alias para compatibilidad
    ];
    
    /**
     * Configuraci�n est�ndar - usar 'platforms' consistentemente
     * CORRECCI�N: Ya no 'reviewPlatforms', sino 'platforms'
     */
    public static $CONFIG_KEY = "platforms";
    
    /**
     * Actor IDs oficiales verificados
     */
    public static $ACTOR_IDS = [
        "multi_otas" => "dSCLg0C3YEZ83HzYX",
        "booking_scraper" => "dtrungdt/booking-scraper", 
        "google_maps" => "nwua9Gu5YrADL7ZDj",
        "tripadvisor" => "maxkopacz/tripadvisor-scraper"
    ];
    
    /**
     * URLs base para diferentes entornos
     */
    public static $API_URLS = [
        "production" => "https://api.apify.com/v2",
        "development" => "https://api.apify.com/v2"
    ];
    
    /**
     * Configuraci�n de timeouts y reintentos
     */
    public static $TIMEOUT_CONFIG = [
        "run_timeout" => 1800,      // 30 minutos
        "wait_timeout" => 300,      // 5 minutos entre checks
        "max_retries" => 3,
        "retry_delay" => 60         // 1 minuto entre reintentos
    ];
    
    /**
     * Obtener flag correcto para una plataforma
     */
    public static function getPlatformFlag($platform) 
    {
        $platform = strtolower($platform);
        return self::$PLATFORM_FLAGS[$platform] ?? null;
    }
    
    /**
     * Validar que una plataforma es soportada
     */
    public static function isPlatformSupported($platform) 
    {
        return array_key_exists(strtolower($platform), self::$PLATFORM_FLAGS);
    }
    
    /**
     * Obtener todas las plataformas soportadas
     */
    public static function getSupportedPlatforms() 
    {
        return array_keys(self::$PLATFORM_FLAGS);
    }
    
    /**
     * Generar input correcto para buildExtractionInput
     */
    public static function generateExtractionInput($selectedPlatforms) 
    {
        // Inicializar todos los flags como false
        $input = [
            'enableBooking' => false,
            'enableGoogleMaps' => false,
            'enableTripadvisor' => false,
            'enableExpedia' => false,
            'enableAgoda' => false,
            'enableHotelsCom' => false,  // Nombre correcto
        ];
        
        // Activar solo las plataformas seleccionadas
        foreach ($selectedPlatforms as $platform) {
            $flag = self::getPlatformFlag($platform);
            if ($flag && array_key_exists($flag, $input)) {
                $input[$flag] = true;
            }
        }
        
        return $input;
    }
    
    /**
     * Validar configuraci�n antes de enviar a Apify
     */
    public static function validateConfig($config) 
    {
        $errors = [];
        
        // Verificar que hay al menos una plataforma seleccionada
        if (empty($config[self::$CONFIG_KEY])) {
            $errors[] = "Debe seleccionar al menos una plataforma";
        }
        
        // Verificar que las plataformas son v�lidas
        foreach ($config[self::$CONFIG_KEY] as $platform) {
            if (!self::isPlatformSupported($platform)) {
                $errors[] = "Plataforma no soportada: $platform";
            }
        }
        
        // Verificar datos requeridos
        $required = ['hotel_name', 'location'];
        foreach ($required as $field) {
            if (empty($config[$field])) {
                $errors[] = "Campo requerido: $field";
            }
        }
        
        return $errors;
    }
    
    /**
     * Demo de configuraci�n corregida para stats
     */
    public static function getStatsConfig() 
    {
        return [
            self::$CONFIG_KEY => ['booking', 'googlemaps', 'tripadvisor'], // CORRECCI�N: platforms no reviewPlatforms
            'hotel_name' => 'Hotel Demo',
            'location' => 'Madrid, Spain',
            'max_reviews' => 100
        ];
    }
    
    /**
     * Obtener headers CORS correctos para APIs
     */
    public static function getCorsHeaders() 
    {
        return [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-Admin-Session' // CORRECCI�N: Headers completos
        ];
    }
    
    /**
     * Configuraci�n de logging mejorada
     */
    public static function getLoggingConfig() 
    {
        return [
            'enabled' => true,
            'level' => 'INFO',
            'max_file_size' => '10MB',
            'retention_days' => 30,
            'channels' => ['apify', 'extraction', 'errors']
        ];
    }
}

/**
 * FUNCIONES DE COMPATIBILIDAD LEGACY
 * Mantener compatibilidad con c�digo existente
 */

function getPlatformFlag($platform) {
    return ApifyConfig::getPlatformFlag($platform);
}

function buildExtractionInput($config) {
    $platforms = $config[ApifyConfig::$CONFIG_KEY] ?? [];
    return ApifyConfig::generateExtractionInput($platforms);
}

function validateApifyConfig($config) {
    return ApifyConfig::validateConfig($config);
}

// Log de carga del archivo
error_log(" apify-config.php cargado correctamente con configuraci�n estandardizada");

?>