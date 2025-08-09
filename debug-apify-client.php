<?php
/**
 * Debug script para verificar ApifyClient
 */

echo "🔍 DEBUG: VERIFICACIÓN DE APIFY CLIENT\n\n";

try {
    echo "1. Incluyendo archivos necesarios...\n";
    require_once 'env-loader.php';
    echo "   ✅ env-loader.php incluido\n";
    
    require_once 'apify-config.php';
    echo "   ✅ apify-config.php incluido\n";
    
    require_once 'apify-data-processor.php';
    echo "   ✅ apify-data-processor.php incluido\n";
    
    echo "\n2. Verificando clases...\n";
    echo "   EnvironmentLoader existe: " . (class_exists('EnvironmentLoader') ? '✅' : '❌') . "\n";
    echo "   ApifyConfig existe: " . (class_exists('ApifyConfig') ? '✅' : '❌') . "\n";
    echo "   ApifyClient existe: " . (class_exists('ApifyClient') ? '✅' : '❌') . "\n";
    echo "   ApifyDataProcessor existe: " . (class_exists('ApifyDataProcessor') ? '✅' : '❌') . "\n";
    
    echo "\n3. Cargando variables de entorno...\n";
    $config = EnvironmentLoader::load();
    echo "   Variables cargadas: " . count($config) . "\n";
    
    $apifyToken = EnvironmentLoader::get('APIFY_API_TOKEN');
    echo "   APIFY_API_TOKEN configurado: " . (!empty($apifyToken) ? '✅' : '❌') . "\n";
    if (!empty($apifyToken)) {
        echo "   Token length: " . strlen($apifyToken) . " chars\n";
    }
    
    echo "\n4. Intentando instanciar ApifyClient...\n";
    $apifyClient = new ApifyClient();
    echo "   ✅ ApifyClient instanciado correctamente\n";
    
    echo "\n5. Verificando métodos requeridos...\n";
    $methods = ['runBookingExtractionSync', 'runHotelExtractionSync', 'startBookingExtractionAsync', 'startHotelExtraction'];
    foreach ($methods as $method) {
        echo "   Método $method: " . (method_exists($apifyClient, $method) ? '✅' : '❌') . "\n";
    }
    
    echo "\n✅ TODAS LAS VERIFICACIONES COMPLETADAS\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
?>