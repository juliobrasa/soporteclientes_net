<?php
/**
 * Utilidades para Extracción - buildExtractionInput
 * 
 * Funciones para construir input correcto de extracción
 * que respeta la selección de plataformas del usuario
 */

class ExtractionInputBuilder 
{
    /**
     * Construir input de extracción respetando plataformas seleccionadas
     * 
     * @param array $userConfig Configuración del usuario
     * @return array Input para Apify con flags enableX correctos
     */
    public static function buildExtractionInput($userConfig) 
    {
        // Configuración por defecto (TODAS DESHABILITADAS)
        $extractionInput = [
            'enableBooking' => false,
            'enableGoogleMaps' => false,
            'enableTripadvisor' => false,
            'enableExpedia' => false,
            'enableAgoda' => false,
            'enableHotelsCom' => false,
        ];
        
        // Mapeo de plataformas a flags
        $platformMapping = [
            'booking' => 'enableBooking',
            'booking.com' => 'enableBooking',
            'googlemaps' => 'enableGoogleMaps',
            'google maps' => 'enableGoogleMaps',
            'tripadvisor' => 'enableTripadvisor',
            'trip advisor' => 'enableTripadvisor',
            'expedia' => 'enableExpedia',
            'agoda' => 'enableAgoda',
            'hotels.com' => 'enableHotelsCom',
            'hotelscom' => 'enableHotelsCom',
        ];
        
        // Aplicar solo las plataformas seleccionadas por el usuario
        if (!empty($userConfig['platforms']) && is_array($userConfig['platforms'])) {
            foreach ($userConfig['platforms'] as $platform) {
                $platformKey = strtolower(trim($platform));
                
                if (isset($platformMapping[$platformKey])) {
                    $flagName = $platformMapping[$platformKey];
                    $extractionInput[$flagName] = true;
                    
                    error_log("buildExtractionInput: Habilitada $flagName para plataforma '$platform'");
                } else {
                    error_log("buildExtractionInput: Plataforma desconocida '$platform' - ignorada");
                }
            }
        } else {
            error_log("buildExtractionInput: Sin plataformas especificadas - todas deshabilitadas");
        }
        
        // Agregar otros parámetros de configuración
        $extractionInput = array_merge($extractionInput, [
            'hotelId' => $userConfig['hotel_id'] ?? null,
            'maxReviewsPerPlatform' => intval($userConfig['max_reviews'] ?? 100),
            'includePhotos' => boolval($userConfig['include_photos'] ?? false),
            'includeDetails' => boolval($userConfig['include_details'] ?? true),
            'language' => $userConfig['language'] ?? 'es',
            'timeout' => intval($userConfig['timeout'] ?? 60000)
        ]);
        
        // Log de configuración final
        $enabledPlatforms = array_keys(array_filter($extractionInput, function($value, $key) {
            return strpos($key, 'enable') === 0 && $value === true;
        }, ARRAY_FILTER_USE_BOTH));
        
        error_log("buildExtractionInput: Configuración final - Plataformas habilitadas: " . 
                 implode(', ', $enabledPlatforms) . 
                 " | Max reviews: {$extractionInput['maxReviewsPerPlatform']}");
        
        return $extractionInput;
    }
    
    /**
     * Validar configuración de usuario antes de construir input
     * 
     * @param array $userConfig
     * @return array ['valid' => bool, 'errors' => array]
     */
    public static function validateUserConfig($userConfig) 
    {
        $errors = [];
        
        // Validar hotel_id
        if (empty($userConfig['hotel_id']) || !is_numeric($userConfig['hotel_id'])) {
            $errors[] = 'hotel_id es requerido y debe ser numérico';
        }
        
        // Validar platforms
        if (empty($userConfig['platforms']) || !is_array($userConfig['platforms'])) {
            $errors[] = 'Debe seleccionar al menos una plataforma';
        }
        
        // Validar max_reviews
        if (isset($userConfig['max_reviews']) && (!is_numeric($userConfig['max_reviews']) || intval($userConfig['max_reviews']) <= 0)) {
            $errors[] = 'max_reviews debe ser un número positivo';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Estimar coste basado en plataformas y número de reviews
     * 
     * @param array $extractionInput Input construido
     * @return array ['estimated_cost' => float, 'platform_count' => int]
     */
    public static function estimateCost($extractionInput) 
    {
        // Costes aproximados por plataforma por review
        $platformCosts = [
            'enableBooking' => 0.001,       // $0.001 por review
            'enableGoogleMaps' => 0.002,    // Google Maps más caro
            'enableTripadvisor' => 0.0015,  // TripAdvisor medio
            'enableExpedia' => 0.001,
            'enableAgoda' => 0.001,
            'enableHotelsCom' => 0.001
        ];
        
        $enabledPlatforms = 0;
        $totalBaseCost = 0;
        
        foreach ($platformCosts as $platform => $costPerReview) {
            if (!empty($extractionInput[$platform])) {
                $enabledPlatforms++;
                $maxReviews = intval($extractionInput['maxReviewsPerPlatform'] ?? 100);
                $totalBaseCost += $costPerReview * $maxReviews;
            }
        }
        
        // Coste base + overhead de setup
        $setupCost = $enabledPlatforms * 0.05; // $0.05 setup por plataforma
        $estimatedCost = $totalBaseCost + $setupCost;
        
        return [
            'estimated_cost' => round($estimatedCost, 4),
            'platform_count' => $enabledPlatforms,
            'setup_cost' => $setupCost,
            'extraction_cost' => $totalBaseCost
        ];
    }
    
    /**
     * Convertir configuración legacy a nuevo formato
     * 
     * @param array $legacyConfig Configuración antigua
     * @return array Nueva configuración
     */
    public static function convertLegacyConfig($legacyConfig) 
    {
        $newConfig = [];
        
        // Mapear campos legacy
        $fieldMapping = [
            'hotelId' => 'hotel_id',
            'hotelID' => 'hotel_id',
            'hotel_id' => 'hotel_id',
            'maxReviews' => 'max_reviews',
            'max_reviews_per_platform' => 'max_reviews',
            'includePhotos' => 'include_photos',
            'includeDetails' => 'include_details'
        ];
        
        foreach ($fieldMapping as $oldKey => $newKey) {
            if (isset($legacyConfig[$oldKey])) {
                $newConfig[$newKey] = $legacyConfig[$oldKey];
            }
        }
        
        // Convertir plataformas desde diferentes formatos legacy
        $platforms = [];
        
        // Formato 1: Array directo de plataformas
        if (!empty($legacyConfig['platforms']) && is_array($legacyConfig['platforms'])) {
            $platforms = $legacyConfig['platforms'];
        }
        
        // Formato 2: Flags enableX individuales
        $enableFlags = [
            'enableBooking' => 'booking',
            'enableGoogleMaps' => 'googlemaps',
            'enableTripadvisor' => 'tripadvisor',
            'enableExpedia' => 'expedia',
            'enableAgoda' => 'agoda',
            'enableHotelsCom' => 'hotels.com'
        ];
        
        foreach ($enableFlags as $flag => $platform) {
            if (!empty($legacyConfig[$flag])) {
                $platforms[] = $platform;
            }
        }
        
        $newConfig['platforms'] = array_unique($platforms);
        
        return $newConfig;
    }
}

/**
 * Función wrapper para compatibilidad
 */
function buildExtractionInput($userConfig) 
{
    return ExtractionInputBuilder::buildExtractionInput($userConfig);
}

// Test si se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🧪 TESTING buildExtractionInput\n";
    echo str_repeat("=", 50) . "\n\n";
    
    // Caso 1: Solo Booking
    echo "📋 Caso 1: Solo Booking.com\n";
    $config1 = [
        'hotel_id' => 123,
        'platforms' => ['booking'],
        'max_reviews' => 50
    ];
    
    $result1 = ExtractionInputBuilder::buildExtractionInput($config1);
    echo "Input generado:\n";
    print_r($result1);
    
    $cost1 = ExtractionInputBuilder::estimateCost($result1);
    echo "Coste estimado: $" . $cost1['estimated_cost'] . " para {$cost1['platform_count']} plataforma(s)\n\n";
    
    // Caso 2: Múltiples plataformas
    echo "📋 Caso 2: Múltiples plataformas\n";
    $config2 = [
        'hotel_id' => 456,
        'platforms' => ['booking', 'tripadvisor', 'googlemaps'],
        'max_reviews' => 100,
        'include_photos' => true
    ];
    
    $result2 = ExtractionInputBuilder::buildExtractionInput($config2);
    echo "Input generado:\n";
    print_r($result2);
    
    $cost2 = ExtractionInputBuilder::estimateCost($result2);
    echo "Coste estimado: $" . $cost2['estimated_cost'] . " para {$cost2['platform_count']} plataforma(s)\n\n";
    
    // Caso 3: Sin plataformas (error esperado)
    echo "📋 Caso 3: Sin plataformas especificadas\n";
    $config3 = [
        'hotel_id' => 789,
        'max_reviews' => 25
    ];
    
    $validation3 = ExtractionInputBuilder::validateUserConfig($config3);
    if (!$validation3['valid']) {
        echo "❌ Errores de validación:\n";
        foreach ($validation3['errors'] as $error) {
            echo "  - $error\n";
        }
    }
    
    $result3 = ExtractionInputBuilder::buildExtractionInput($config3);
    echo "Input generado (todas deshabilitadas):\n";
    print_r($result3);
    
    echo "\n✅ Tests completados\n";
}
?>