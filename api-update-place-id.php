<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin-config.php';
require_once 'debug-logger.php';

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Verificar autenticación
session_start();
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'];

if (!$isAuthenticated && isset($_SERVER['HTTP_X_ADMIN_SESSION'])) {
    $sessionId = $_SERVER['HTTP_X_ADMIN_SESSION'];
    if ($sessionId && strlen($sessionId) > 10) {
        $isAuthenticated = true;
    }
}

if (!$isAuthenticated) {
    response(['error' => 'No autorizado'], 401);
}

// Obtener input
$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(['error' => 'Método no permitido'], 405);
}

if (!isset($input['hotel_id']) || !isset($input['place_id'])) {
    response(['error' => 'hotel_id y place_id son requeridos'], 400);
}

$hotelId = (int)$input['hotel_id'];
$placeId = trim($input['place_id']);

if (empty($placeId)) {
    response(['error' => 'place_id no puede estar vacío'], 400);
}

try {
    $pdo = getDBConnection();
    
    // Verificar que el hotel existe
    $stmt = $pdo->prepare("SELECT id, nombre_hotel FROM hoteles WHERE id = ?");
    $stmt->execute([$hotelId]);
    $hotel = $stmt->fetch();
    
    if (!$hotel) {
        response(['error' => 'Hotel no encontrado'], 404);
    }
    
    // Actualizar Place ID
    $stmt = $pdo->prepare("UPDATE hoteles SET google_place_id = ? WHERE id = ?");
    $stmt->execute([$placeId, $hotelId]);
    
    DebugLogger::info("Place ID actualizado", [
        'hotel_id' => $hotelId,
        'hotel_name' => $hotel['nombre_hotel'],
        'place_id' => $placeId
    ]);
    
    response([
        'success' => true,
        'message' => 'Place ID actualizado correctamente',
        'hotel_name' => $hotel['nombre_hotel'],
        'place_id' => $placeId
    ]);
    
} catch (Exception $e) {
    DebugLogger::error("Error actualizando Place ID", [
        'hotel_id' => $hotelId,
        'place_id' => $placeId,
        'error' => $e->getMessage()
    ]);
    
    response(['error' => $e->getMessage()], 500);
}
?>