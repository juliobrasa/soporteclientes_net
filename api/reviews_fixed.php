<?php
// api/reviews_fixed.php - Versión compatible con MariaDB
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    // Parámetros
    $hotelName = $_GET['hotel'] ?? 'Caribe Internacional';
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 10))); // Máximo 50, mínimo 1
    
    // Consulta SIN OFFSET para evitar problemas de sintaxis
    $sql = "
        SELECT 
            unique_id,
            hotel_name,
            user_name,
            user_location,
            traveler_type_spanish,
            review_title,
            liked_text,
            disliked_text,
            rating,
            review_date,
            property_response,
            review_language
        FROM reviews 
        WHERE hotel_name = ?
        ORDER BY review_date DESC
        LIMIT $limit
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hotelName]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatear datos
    $formattedReviews = [];
    foreach($reviews as $index => $review) {
        if ($index >= $limit) break; // Seguridad adicional
        
        $sentiment = 'neutral';
        $rating = (float)$review['rating'];
        if ($rating >= 4) $sentiment = 'positive';
        elseif ($rating <= 2) $sentiment = 'negative';
        
        // Extraer tags básicos
        $tags = [];
        $content = strtolower(($review['liked_text'] ?? '') . ' ' . ($review['disliked_text'] ?? ''));
        if (strpos($content, 'limpi') !== false) $tags[] = 'limpieza';
        if (strpos($content, 'personal') !== false || strpos($content, 'staff') !== false) $tags[] = 'personal';
        if (strpos($content, 'ubicación') !== false || strpos($content, 'localización') !== false) $tags[] = 'ubicación';
        if (strpos($content, 'precio') !== false || strpos($content, 'caro') !== false) $tags[] = 'precio';
        if (strpos($content, 'comida') !== false || strpos($content, 'desayuno') !== false) $tags[] = 'comida';
        
        $formattedReviews[] = [
            'id' => $review['unique_id'] ?: "review_$index",
            'guest' => $review['user_name'] ?: 'Usuario anónimo',
            'country' => $review['user_location'] ?: 'No especificado',
            'date' => $review['review_date'] ? date('d M Y', strtotime($review['review_date'])) : 'Sin fecha',
            'tripType' => $review['traveler_type_spanish'] ?: 'Sin especificar',
            'reviewId' => 'BK' . ($index + 1),
            'platform' => 'Booking.com',
            'rating' => $rating,
            'title' => $review['review_title'] ?: 'Sin título',
            'positive' => $review['liked_text'] ?: '',
            'negative' => $review['disliked_text'] ?: '',
            'hasResponse' => !empty($review['property_response']),
            'response' => $review['property_response'] ?: '',
            'platformColor' => 'bg-blue-700',
            'language' => $review['review_language'] ?: 'es',
            'sentiment' => $sentiment,
            'tags' => array_slice($tags, 0, 3), // Máximo 3 tags
            'metadata' => [
                'raw_date' => $review['review_date'],
                'hotel' => $review['hotel_name']
            ]
        ];
    }
    
    // Contar total con consulta separada más simple
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM reviews WHERE hotel_name = ?");
    $countStmt->execute([$hotelName]);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Estadísticas básicas
    $avgRating = 0;
    $responseCount = 0;
    if (count($formattedReviews) > 0) {
        $avgRating = array_sum(array_column($formattedReviews, 'rating')) / count($formattedReviews);
        $responseCount = count(array_filter($formattedReviews, function($r) { return $r['hasResponse']; }));
    }
    
    $response = [
        'status' => 'success',
        'reviews' => $formattedReviews,
        'pagination' => [
            'current_page' => 1,
            'per_page' => $limit,
            'total' => (int)$totalCount,
            'total_pages' => ceil($totalCount / $limit),
            'showing' => count($formattedReviews)
        ],
        'summary' => [
            'hotel_name' => $hotelName,
            'total_reviews' => (int)$totalCount,
            'average_rating' => round($avgRating, 2),
            'total_responses' => $responseCount,
            'response_rate' => $totalCount > 0 ? round(($responseCount / count($formattedReviews)) * 100, 1) : 0
        ],
        'filters' => [
            'available_languages' => ['es', 'en'],
            'rating_distribution' => [
                '5' => count(array_filter($formattedReviews, function($r) { return $r['rating'] == 5; })),
                '4' => count(array_filter($formattedReviews, function($r) { return $r['rating'] == 4; })),
                '3' => count(array_filter($formattedReviews, function($r) { return $r['rating'] == 3; })),
                '2' => count(array_filter($formattedReviews, function($r) { return $r['rating'] == 2; })),
                '1' => count(array_filter($formattedReviews, function($r) { return $r['rating'] == 1; }))
            ]
        ],
        'debug' => [
            'query_executed' => 'SUCCESS',
            'sql_version' => $pdo->getAttribute(PDO::ATTR_SERVER_VERSION),
            'rows_found' => count($reviews),
            'hotel_param' => $hotelName
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Database error: ' . $e->getMessage(),
        'code' => $e->getCode(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'General error: ' . $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>