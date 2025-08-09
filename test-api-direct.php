<?php
/**
 * Test directo del API para capturar errores 500
 */

session_start();

// Simular que estamos loggeados para evitar error 401
$_SESSION['admin_logged'] = true;
$_SESSION['admin_email'] = 'test@admin.com';

echo "🧪 TEST DIRECTO DE API-EXTRACTION.PHP\n";
echo str_repeat("=", 50) . "\n\n";

echo "✅ Sesión admin simulada:\n";
echo "   admin_logged = " . ($_SESSION['admin_logged'] ? 'true' : 'false') . "\n";
echo "   session_id = " . session_id() . "\n\n";

// Simular datos que envía el admin
$testInput = [
    'hotel_id' => 7,  // Hotel que está fallando
    'max_reviews' => 200,
    'platforms' => ['booking'],
    'languages' => ['en', 'es'],
    'sentiment_analysis' => false,
    'generate_alerts' => false,
    'sync_mode' => false
];

echo "📋 Datos de prueba (simulando POST):\n";
echo json_encode($testInput, JSON_PRETTY_PRINT) . "\n\n";

// Simular la petición POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = $testInput; // También en POST

// Simular el input JSON
$inputJson = json_encode($testInput);
file_put_contents('php://temp', $inputJson);

echo "🔄 Ejecutando api-extraction.php directamente...\n";
echo str_repeat("-", 30) . "\n";

// Capturar toda la salida y errores
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Simular file_get_contents('php://input')
    $GLOBALS['test_input'] = $inputJson;
    
    // Incluir el archivo API
    include_once 'api-extraction.php';
    
} catch (Exception $e) {
    echo "\n❌ EXCEPCIÓN CAPTURADA:\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "\n💥 ERROR FATAL CAPTURADO:\n";
    echo "Mensaje: " . $e->getMessage() . "\n"; 
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

$output = ob_get_contents();
ob_end_clean();

echo str_repeat("-", 30) . "\n";
echo "📊 SALIDA CAPTURADA:\n";
echo $output;

// Verificar si hubo errores PHP
$lastError = error_get_last();
if ($lastError && $lastError['type'] === E_ERROR) {
    echo "\n💥 ÚLTIMO ERROR PHP:\n";
    echo "Mensaje: " . $lastError['message'] . "\n";
    echo "Archivo: " . $lastError['file'] . ":" . $lastError['line'] . "\n";
}

echo "\n🔍 DIAGNÓSTICO:\n";
if (empty($output)) {
    echo "❌ No hubo salida - posible error fatal\n";
} else {
    if (strpos($output, 'error') !== false) {
        echo "⚠️ Salida contiene 'error'\n";
    }
    if (strpos($output, 'success') !== false) {
        echo "✅ Salida contiene 'success'\n";
    }
}

echo "\n💡 SIGUIENTE PASO:\n";
echo "Si ves errores arriba, esa es la causa del error 500\n";
echo "Si no ves errores, el problema puede estar en las dependencias\n";
?>