<?php
// api/dashboard.php
require_once 'config.php';

$db = new Database();

// Parámetros
$hotelName = validateParam('hotel', null);
$dateRange = validateParam('days', 30, 'int');
$destination = validateParam('destination', null);

try {
    // Construir condiciones WHERE
    $whereConditions = ["review_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)"];
    $params = [$dateRange];
    
    if ($hotelName) {
        $whereConditions[] = "hotel_name = ?";
        $params[] = $hotelName;
    }
    
    if ($destination) {
        $whereConditions[] = "hotel_destination = ?";
        $params[] = $destination;
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // 1. Estadísticas generales del hotel
    $hotelStats = $db->fetchOne("
        SELECT 
            hotel_name,
            hotel_destination,
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 2) as average_rating,
            COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
            COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews,
            COUNT(CASE WHEN property_response IS NOT NULL AND property_response != '' THEN 1 END) as responded_reviews,
            MIN(review_date) as first_review,
            MAX(review_date) as last_review,
            COUNT(DISTINCT user_name) as unique_reviewers
        FROM reviews 
        $whereClause
        GROUP BY hotel_name, hotel_destination
        LIMIT 1
    ", $params);
    
    if (!$hotelStats) {
        jsonResponse(['error' => 'No data found for specified criteria'], 404);
    }
    
    // 2. Estadísticas del período anterior para comparación
    $prevParams = array_merge([$dateRange * 2, $dateRange], array_slice($params, 1));
    $prevStats = $db->fetchOne("
        SELECT 
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 2) as average_rating,
            COUNT(CASE WHEN property_response IS NOT NULL AND property_response != '' THEN 1 END) as responded_reviews
        FROM reviews 
        WHERE review_date >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        AND review_date < DATE_SUB(CURDATE(), INTERVAL ? DAY)
        " . (count($params) > 1 ? "AND hotel_name = ?" : "") . "
        " . (count($params) > 2 ? "AND hotel_destination = ?" : "")
    , $prevParams);
    
    // 3. Calcular métricas
    $responseRate = $hotelStats['total_reviews'] > 0 
        ? ($hotelStats['responded_reviews'] / $hotelStats['total_reviews']) 
        : 0;
    
    $coverageRate = 60 + ($hotelStats['unique_reviewers'] / max($hotelStats['total_reviews'], 1)) * 40;
    
    // 4. Calcular cambios vs período anterior
    $reviewsChange = $prevStats && $prevStats['total_reviews'] > 0 
        ? (($hotelStats['total_reviews'] - $prevStats['total_reviews']) / $prevStats['total_reviews']) * 100
        : 0;
    
    $ratingChange = $prevStats && $prevStats['average_rating'] > 0
        ? (($hotelStats['average_rating'] - $prevStats['average_rating']) / $prevStats['average_rating']) * 100
        : 0;
    
    // 5. Obtener reseñas recientes para análisis semántico
    $recentReviews = $db->fetchAll("
        SELECT liked_text, disliked_text, rating, review_title
        FROM reviews 
        $whereClause
        ORDER BY review_date DESC
        LIMIT 50
    ", $params);
    
    // 6. Calcular IRO
    $iroData = calculateIRO([
        'average_rating' => $hotelStats['average_rating'],
        'total_reviews' => $hotelStats['total_reviews'],
        'recent_reviews' => count($recentReviews),
        'response_rate' => $responseRate
    ]);
    
    // 7. Calcular índice semántico
    $semanticScore = calculateSentimentScore($recentReviews);
    
    // 8. Distribución por rating
    $ratingDistribution = $db->fetchAll("
        SELECT 
            rating,
            COUNT(*) as count,
            ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM reviews $whereClause)), 1) as percentage
        FROM reviews 
        $whereClause
        GROUP BY rating
        ORDER BY rating
    ", array_merge($params, $params));
    
    // 9. Tendencia mensual
    $monthlyTrend = $db->fetchAll("
        SELECT 
            DATE_FORMAT(review_date, '%Y-%m') as month,
            COUNT(*) as reviews,
            ROUND(AVG(rating), 2) as avg_rating
        FROM reviews 
        $whereClause
        GROUP BY DATE_FORMAT(review_date, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ", $params);
    
    // 10. Tipos de viajero
    $travelerTypes = $db->fetchAll("
        SELECT 
            COALESCE(traveler_type_spanish, 'Sin especificar') as type,
            COUNT(*) as count,
            ROUND(AVG(rating), 2) as avg_rating
        FROM reviews 
        $whereClause
        GROUP BY traveler_type_spanish
        ORDER BY count DESC
    ", $params);
    
    // Preparar respuesta
    $response = [
        'hotel' => [
            'name' => $hotelStats['hotel_name'],
            'destination' => $hotelStats['hotel_destination'],
            'period_days' => $dateRange
        ],
        'iro' => [
            'score' => $iroData['score'],
            'change' => $reviewsChange > 0 ? $reviewsChange : 0,
            'trend' => $reviewsChange >= 0 ? 'up' : 'down',
            'components' => [
                'rating' => [
                    'value' => $iroData['components']['rating'],
                    'trend' => $ratingChange >= 0 ? 'up' : 'down'
                ],
                'coverage' => [
                    'value' => round($coverageRate, 1),
                    'trend' => 'up'
                ],
                'response' => [
                    'value' => round($responseRate * 100, 1),
                    'trend' => 'up'
                ]
            ]
        ],
        'semantic' => [
            'score' => $semanticScore,
            'status' => $semanticScore >= 70 ? 'good' : ($semanticScore >= 50 ? 'regular' : 'bad'),
            'change' => rand(-20, 20), // Temporal - calcular real después
            'message' => $semanticScore < 50 
                ? 'Cuidado, tu propiedad tiene menciones negativas que requieren atención.'
                : 'El análisis semántico muestra percepciones positivas en los comentarios.'
        ],
        'stats' => [
            'total_reviews' => [
                'period' => (int) $hotelStats['total_reviews'],
                'change' => round($reviewsChange, 1)
            ],
            'average_rating' => [
                'period' => (float) $hotelStats['average_rating'],
                'change' => round($ratingChange, 1)
            ],
            'response_rate' => [
                'period' => round($responseRate * 100, 1),
                'change' => 5 // Temporal
            ],
            'coverage' => [
                'period' => round($coverageRate, 1),
                'change' => 3 // Temporal
            ]
        ],
        'distributions' => [
            'ratings' => $ratingDistribution,
            'travelers' => $travelerTypes
        ],
        'trends' => [
            'monthly' => $monthlyTrend
        ],
        'summary' => [
            'positive_percentage' => $hotelStats['total_reviews'] > 0 
                ? round(($hotelStats['positive_reviews'] / $hotelStats['total_reviews']) * 100, 1)
                : 0,
            'negative_percentage' => $hotelStats['total_reviews'] > 0 
                ? round(($hotelStats['negative_reviews'] / $hotelStats['total_reviews']) * 100, 1)
                : 0,
            'unique_reviewers' => (int) $hotelStats['unique_reviewers'],
            'date_range' => [
                'from' => $hotelStats['first_review'],
                'to' => $hotelStats['last_review']
            ]
        ]
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error: ' . $e->getMessage()], 500);
}
?>