<?php
// api/reviews_simple.php - Versión simplificada
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Configuración directa (sin require)
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
    
    // Parámetros simples
    $hotelName = $_GET['hotel'] ?? 'Caribe Internacional';
    $limit = (int)($_GET['limit'] ?? 10);
    $page = (int)($_GET['page'] ?? 1);
    $offset = ($page - 1) * $limit;
    
    // Consulta simplificada
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
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$hotelName, $limit, $offset]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar total
    $countSql = "SELECT COUNT(*) as total FROM reviews WHERE hotel_name = ?";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute([$hotelName]);
    $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Formatear para frontend
    $formattedReviews = [];
    foreach($reviews as $review) {
        $sentiment = 'neutral';
        if ($review['rating'] >= 4) $sentiment = 'positive';
        elseif ($review['rating'] <= 2) $sentiment = 'negative';
        
        $formattedReviews[] = [
            'id' => $review['unique_id'],
            'guest' => $review['user_name'] ?: 'Usuario anónimo',
            'country' => $review['user_location'] ?: 'No especificado',
            'date' => date('d M Y', strtotime($review['review_date'])),
            'tripType' => $review['traveler_type_spanish'] ?: 'Sin especificar',
            'reviewId' => substr($review['unique_id'], -8),
            'platform' => 'Booking.com',
            'rating' => (float)$review['rating'],
            'title' => $review['review_title'] ?: '',
            'positive' => $review['liked_text'] ?: '',
            'negative' => $review['disliked_text'] ?: '',
            'hasResponse' => !empty($review['property_response']),
            'response' => $review['property_response'] ?: '',
            'platformColor' => 'bg-blue-700',
            'language' => $review['review_language'] ?: 'es',
            'sentiment' => $sentiment,
            'tags' => ['booking']
        ];
    }
    
    $response = [
        'reviews' => $formattedReviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int)$totalCount,
            'total_pages' => ceil($totalCount / $limit)
        ],
        'summary' => [
            'hotel_name' => $hotelName,
            'total_reviews' => (int)$totalCount,
            'reviews_shown' => count($formattedReviews)
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'line' => $e->getLine(),
        'file' => basename($e->getFile())
    ], JSON_UNESCAPED_UNICODE);
}
?>