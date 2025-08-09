<?php
/**
 * Test directo de api-extraction.php para diagnosticar error 500
 */

echo "🧪 TEST DE API EXTRACTION\n";
echo str_repeat("=", 40) . "\n\n";

// Simular datos que envía el admin-extraction.php (corregido)
$testData = [
    'hotel_id' => 6,  // Singular, no plural
    'max_reviews' => 200,
    'platforms' => ['booking'],
    'languages' => ['en', 'es']
    // Eliminado extraction_mode - no lo usa el API
];

echo "📋 Datos de prueba:\n";
echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Headers que envía el JavaScript
$headers = [
    'Content-Type: application/json',
    'X-Admin-Session: test-session',
    'X-Requested-With: XMLHttpRequest'
];

echo "📡 Enviando petición POST a api-extraction.php...\n";

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => 'https://soporteclientes.net/api-extraction.php',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($testData),
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true,
    CURLOPT_VERBOSE => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_TIMEOUT => 30
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "📊 RESULTADOS:\n";
echo "HTTP Code: $httpCode\n";

if ($error) {
    echo "❌ cURL Error: $error\n";
}

if ($response) {
    $headerSize = strpos($response, "\r\n\r\n");
    $headers = substr($response, 0, $headerSize);
    $body = substr($response, $headerSize + 4);
    
    echo "\n📋 Headers de respuesta:\n";
    echo $headers . "\n";
    
    echo "\n📋 Body de respuesta:\n";
    echo $body . "\n";
    
    // Intentar decodificar JSON si es válido
    if ($body && $body[0] === '{') {
        $json = json_decode($body, true);
        if ($json) {
            echo "\n📋 JSON decodificado:\n";
            echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
        }
    }
} else {
    echo "❌ No se recibió respuesta\n";
}

// Test adicional: verificar si el archivo existe y es accesible
echo "\n🔍 VERIFICACIONES ADICIONALES:\n";

$apiFile = '/root/soporteclientes_net/api-extraction.php';
if (file_exists($apiFile)) {
    echo "✅ Archivo api-extraction.php existe\n";
    
    // Verificar sintaxis PHP
    $output = [];
    $returnCode = 0;
    exec("php -l $apiFile 2>&1", $output, $returnCode);
    
    if ($returnCode === 0) {
        echo "✅ Sintaxis PHP correcta\n";
    } else {
        echo "❌ Error de sintaxis PHP:\n";
        echo implode("\n", $output) . "\n";
    }
} else {
    echo "❌ Archivo api-extraction.php no encontrado\n";
}

echo "\n💡 SUGERENCIAS:\n";
echo "1. Revisar logs de PHP en el servidor\n";
echo "2. Verificar permisos del archivo api-extraction.php\n";
echo "3. Comprobar configuración de base de datos\n";
echo "4. Validar autenticación admin\n";
?>