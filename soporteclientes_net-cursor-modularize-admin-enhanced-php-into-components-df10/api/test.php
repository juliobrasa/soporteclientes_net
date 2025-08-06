<?php
// api/test.php - Archivo para probar la conexión
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración de la base de datos
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    // Intentar conectar
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    // Probar consultas básicas
    $tests = [];
    
    // Test 1: Contar hoteles
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hoteles");
    $hotelCount = $stmt->fetch()['total'];
    $tests['hoteles'] = ['count' => $hotelCount, 'status' => 'OK'];
    
    // Test 2: Contar reseñas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM reviews");
    $reviewCount = $stmt->fetch()['total'];
    $tests['reviews'] = ['count' => $reviewCount, 'status' => 'OK'];
    
    // Test 3: Obtener algunos hoteles
    $stmt = $pdo->query("SELECT DISTINCT hotel_name, hotel_destination FROM reviews LIMIT 5");
    $sampleHotels = $stmt->fetchAll();
    $tests['sample_hotels'] = $sampleHotels;
    
    // Test 4: Estadísticas básicas
    $stmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT hotel_name) as unique_hotels,
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 2) as avg_rating,
            MIN(review_date) as first_review,
            MAX(review_date) as last_review
        FROM reviews
    ");
    $stats = $stmt->fetch();
    $tests['database_stats'] = $stats;
    
    // Test 5: Reseña más reciente
    $stmt = $pdo->query("
        SELECT hotel_name, user_name, rating, review_date, review_title 
        FROM reviews 
        ORDER BY review_date DESC 
        LIMIT 1
    ");
    $lastReview = $stmt->fetch();
    $tests['last_review'] = $lastReview;
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Conexión exitosa a la base de datos',
        'connection_info' => [
            'host' => $host,
            'database' => $db_name,
            'user' => $username,
            'charset' => 'utf8mb4'
        ],
        'tests' => $tests,
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error de conexión: ' . $e->getMessage(),
        'connection_info' => [
            'host' => $host,
            'database' => $db_name,
            'user' => $username
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Error general: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
?>