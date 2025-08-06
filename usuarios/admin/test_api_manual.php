<?php
// Test manual de la API
echo "Content-Type: application/json\n\n";

// Simular datos de test
$testData = [
    'action' => 'saveHotel',
    'id' => '1', 
    'name' => 'Test Hotel',
    'description' => 'Test Description',
    'website' => 'https://test.com',
    'total_rooms' => '100',
    'status' => 'active'
];

echo "📋 Datos de test:\n";
print_r($testData);
echo "\n\n";

// Simular llamada a la API
$_POST = $testData;
$_REQUEST = $testData;

echo "🔄 Ejecutando admin_api.php...\n\n";

// Capturar output
ob_start();
include 'admin_api.php';
$output = ob_get_clean();

echo "📤 Output de la API:\n";
echo $output;
?>