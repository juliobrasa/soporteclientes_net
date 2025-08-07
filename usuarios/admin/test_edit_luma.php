<?php
// Test específico para editar el hotel Luma
header('Content-Type: application/json; charset=utf-8');

echo "🧪 TEST EDICIÓN HOTEL LUMA\n";
echo "==========================\n\n";

// Datos exactos del hotel Luma
$lumaData = [
    'action' => 'saveHotel',
    'id' => 14,
    'name' => 'luma',
    'description' => 'cancun',
    'website' => 'https://www.booking.com/hotel/mx/luma-by-kavia-cancun.es.html',
    'total_rooms' => 200,
    'status' => 'active'
];

echo "📋 Datos para editar Luma:\n";
foreach ($lumaData as $key => $value) {
    echo "   $key: $value\n";
}
echo "\n";

// Simular el request POST JSON como lo hace el frontend
$json = json_encode($lumaData);
echo "📤 JSON enviado:\n$json\n\n";

// Configurar el entorno como si fuera una request real
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simular php://input
file_put_contents('php://temp/maxmemory:1048576', $json);

// Limpiar variables para simular request limpio
$_POST = [];
$_GET = [];
$_REQUEST = [];

echo "🔄 Ejecutando admin_api.php...\n\n";

ob_start();
include 'admin_api.php';
$output = ob_get_clean();

echo "📤 Resultado:\n$output\n";
?>