<?php
/**
 * Test de conexión con proveedor activo
 */

// Simular request para test de conexión
$_SERVER['REQUEST_METHOD'] = 'POST';
$_REQUEST['action'] = 'testAiProvider';

// Datos del proveedor DeepSeek (activo)
$testData = [
    'id' => 3,
    'provider_type' => 'deepseek',
    'api_key' => 'sk-82389066e1c040639fdd7a7da36757a5',
    'api_url' => 'https://api.deepseek.com/v1/chat/completions',
    'model_name' => 'deepseek-chat',
    'name' => 'DeepSeek Chat'
];

// Simular datos POST directamente
foreach ($testData as $key => $value) {
    $_POST[$key] = $value;
    $_REQUEST[$key] = $value;
}

// Incluir la API
ob_start();
include 'admin_api.php';
$output = ob_get_clean();

echo "Test de Conexión con DeepSeek:\n";
echo "==============================\n";

$result = json_decode($output, true);
echo "Estado: " . ($result['success'] ? '✅ ÉXITO' : '❌ ERROR') . "\n";
echo "Mensaje: " . ($result['test_message'] ?? $result['error']) . "\n";

if (isset($result['response'])) {
    echo "Respuesta IA: " . $result['response'] . "\n";
}

if (isset($result['model_used'])) {
    echo "Modelo usado: " . $result['model_used'] . "\n";
}

if (isset($result['tokens_used'])) {
    echo "Tokens usados: " . $result['tokens_used'] . "\n";
}
?>