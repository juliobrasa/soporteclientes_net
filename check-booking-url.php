<?php
/**
 * Verificar y actualizar URL de Booking para hotel Ambiance
 */

require_once 'admin-config.php';

$pdo = getDBConnection();
if (!$pdo) {
    die("Error de conexión a la base de datos");
}

echo "=== VERIFICACIÓN DE URL DE BOOKING ===\n\n";

// Verificar URL actual
$stmt = $pdo->prepare("SELECT id, nombre_hotel, url_booking FROM hoteles WHERE id = 7");
$stmt->execute();
$hotel = $stmt->fetch();

if ($hotel) {
    echo "Hotel encontrado: {$hotel['nombre_hotel']}\n";
    echo "URL actual: {$hotel['url_booking']}\n\n";
    
    // Proponer una URL más simple
    $urlSimple = 'https://www.booking.com/hotel/mx/ambiance-suites-cancun.html';
    
    echo "Probando URL simplificada: {$urlSimple}\n";
    
    // Actualizar a URL más simple
    $stmt = $pdo->prepare("UPDATE hoteles SET url_booking = ? WHERE id = 7");
    $stmt->execute([$urlSimple]);
    
    echo "✅ URL actualizada exitosamente\n";
    echo "Nueva URL: {$urlSimple}\n";
    
} else {
    echo "❌ Hotel con ID 7 no encontrado\n";
}

echo "\n=== VERIFICACIÓN COMPLETADA ===\n";
?>