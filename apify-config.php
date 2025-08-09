<?php
/**
 * Configuración Apify - Nombres Estándar
 * 
 * Define mapeos consistentes para evitar inconsistencias
 * entre reviewPlatforms, platforms, y flags enableX
 */

class ApifyConfig
{
    /**
     * Mapeo estándar plataformas -> flags Apify
     * 
     * IMPORTANTE: Usar estos nombres exactos que coinciden
     * con el actor multi-OTAs oficial de Apify
     */
    public static $PLATFORM_FLAGS = [
        "booking" => "enableBooking",
        "googlemaps" => "enableGoogleMaps", 
        "tripadvisor" => "enableTripadvisor",
        "expedia" => "enableExpedia",
        "agoda" => "enableAgoda",
        "hotels.com" => "enableHotelsCom",     // ← Correcto con mayúscula C
        "hotelscom" => "enableHotelsCom"       // ← Aliás también corregido
    ];
    
    /**
     * Nombre estándar para configuración
     * 
     * USAR SIEMPRE: "platforms" (no "reviewPlatforms")
     * Para consistencia en toda la aplicación
     */
    public static $CONFIG_KEY = "platforms";
    
    /**
     * Convertir plataformas a flags Apify
     */
    public static function platformsToFlags($platforms) 
    {
        $flags = [];
        
        // Inicializar todos en false
        foreach (self::$PLATFORM_FLAGS as $flag) {
            $flags[$flag] = false;
        }
        
        // Activar solo las seleccionadas
        if (is_array($platforms)) {
            foreach ($platforms as $platform) {
                $key = strtolower(trim($platform));
                if (isset(self::$PLATFORM_FLAGS[$key])) {
                    $flags[self::$PLATFORM_FLAGS[$key]] = true;
                }
            }
        }
        
        return $flags;
    }
    
    /**
     * Validar nombres de flags contra actor oficial
     */
    public static function validateFlags($flags) 
    {
        $validFlags = array_values(self::$PLATFORM_FLAGS);
        $errors = [];
        
        foreach ($flags as $flag => $value) {
            if (strpos($flag, "enable") === 0 && !in_array($flag, $validFlags)) {
                $errors[] = "Flag desconocido: $flag (puede ser ignorado por Apify)";
            }
        }
        
        return $errors;
    }
}

// Tests de verificación
if (basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"])) {
    echo "🧪 TESTING APIFY CONFIG\n";
    echo str_repeat("=", 40) . "\n";
    
    $testPlatforms = ["booking", "hotels.com", "tripadvisor"];
    $flags = ApifyConfig::platformsToFlags($testPlatforms);
    
    echo "Plataformas: " . implode(", ", $testPlatforms) . "\n";
    echo "Flags generados:\n";
    foreach ($flags as $flag => $enabled) {
        $status = $enabled ? "✅" : "❌";
        echo "  $status $flag\n";
    }
    
    $errors = ApifyConfig::validateFlags($flags);
    if (empty($errors)) {
        echo "\n✅ Todos los flags son válidos\n";
    } else {
        echo "\n⚠️  Errores encontrados:\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
}
?>