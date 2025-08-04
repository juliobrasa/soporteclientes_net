<?php
// api/reviews.php
require_once 'config.php';

$db = new Database();

// Parámetros
$hotelName = validateParam('hotel', null);
$page = validateParam('page', 1, 'int');
$limit = validateParam('limit', 20, 'int');
$rating = validateParam('rating', null, 'int');
$dateFrom = validateParam('date_from', null);
$dateTo = validateParam('date_to', null);
$language = validateParam('language', null);
$travelerType = validateParam('traveler_type', null);
$hasResponse = validateParam('has_response', null, 'bool');
$search = validateParam('search', null);

try {
    // Construir condiciones WHERE
    $whereConditions = ["1=1"];
    $params = [];
    
    if ($hotelName) {
        $whereConditions[] = "hotel_name = ?";
        $params[] = $hotelName;
    }
    
    if ($rating) {
        $whereConditions[] = "rating = ?";
        $params[] = $rating;
    }
    
    if ($dateFrom) {
        $whereConditions[] = "review_date >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $whereConditions[] = "review_date <= ?";
        $params[] = $dateTo;
    }
    
    if ($language) {
        $whereConditions[] = "review_language = ?";
        $params[] = $language;
    }
    
    if ($travelerType) {
        $whereConditions[] = "traveler_type_spanish = ?";
        $params[] = $travelerType;
    }
    
    if ($hasResponse !== null) {
        if ($hasResponse) {
            $whereConditions[] = "property_response IS NOT NULL AND property_response != ''";
        } else {
            $whereConditions[] = "(property_response IS NULL OR property_response = '')";
        }
    }
    
    if ($search) {
        $whereConditions[] = "(review_title LIKE ? OR liked_text LIKE ? OR disliked_text LIKE ? OR user_name LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = "WHERE " . implode(" AND ", $whereConditions);
    
    // Contar total de reseñas
    $totalCount = $db->fetchOne("
        SELECT COUNT(*) as total 
        FROM reviews 
        $whereClause
    ", $params)['total'];
    
    // Calcular offset
    $offset = ($page - 1) * $limit;
    
    // Obtener reseñas
    $reviews = $db->fetchAll("
        SELECT 
            id,
            unique_id,
            hotel_name,
            hotel_destination,
            user_name,
            user_location,
            traveler_type_spanish,
            review_title,
            liked_text,
            disliked_text,
            rating,
            review_date,
            check_in_date,
            check_out_date,
            property_response,
            review_language,
            helpful_votes,
            number_of_nights,
            was_translated,
            scraped_at
        FROM reviews 
        $whereClause
        ORDER BY review_date DESC, scraped_at DESC
        LIMIT ? OFFSET ?
    ", array_merge($params, [$limit, $offset]));
    
    // Formatear reseñas para el frontend
    $formattedReviews = [];
    foreach ($reviews as $review) {
        // Determinar sentimiento básico
        $sentiment = 'neutral';
        if ($review['rating'] >= 4) {
            $sentiment = 'positive';
        } elseif ($review['rating'] <= 2) {
            $sentiment = 'negative';
        }
        
        // Extraer tags básicos del contenido
        $tags = [];
        $content = strtolower(($review['liked_text'] ?? '') . ' ' . ($review['disliked_text'] ?? ''));
        
        $tagKeywords = [
            'limpieza' => ['limpio', 'sucio', 'limpieza'],
            'personal' => ['personal', 'staff', 'amable', 'servicio'],
            'ubicación' => ['ubicación', 'localización', 'centro'],
            'precio' => ['precio', 'caro', 'barato', 'valor'],
            'comida' => ['comida', 'restaurante', 'desayuno'],
            'habitación' => ['habitación', 'cuarto', 'cama']
        ];
        
        foreach ($tagKeywords as $tag => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($content, $keyword) !== false) {
                    $tags[] = $tag;
                    break;
                }
            }
        }
        
        $formattedReviews[] = [
            'id' => $review['unique_id'],
            'guest' => $review['user_name'] ?? 'Usuario anónimo',
            'country' => $review['user_location'] ?? 'No especificado',
            'date' => date('d M Y', strtotime($review['review_date'])),
            'tripType' => $review['traveler_type_spanish'] ?? 'Sin especificar',
            'reviewId' => $review['id'],
            'platform' => 'Booking.com',
            'rating' => (float) $review['rating'],
            'title' => $review['review_title'] ?? '',
            'positive' => $review['liked_text'] ?? '',
            'negative' => $review['disliked_text'] ?? '',
            'hasResponse' => !empty($review['property_response']),
            'response' => $review['property_response'] ?? '',
            'platformColor' => 'bg-blue-700',
            'language' => $review['review_language'] ?? 'es',
            'sentiment' => $sentiment,
            'tags' => array_unique($tags),
            'metadata' => [
                'check_in' => $review['check_in_date'],
                'check_out' => $review['check_out_date'],
                'nights' => $review['number_of_nights'],
                'helpful_votes' => (int) $review['helpful_votes'],
                'was_translated' => (bool) $review['was_translated'],
                'scraped_at' => $review['scraped_at']
            ]
        ];
    }
    
    // Obtener estadísticas de filtros disponibles
    $filterStats = [
        'languages' => $db->fetchAll("
            SELECT review_language as language, COUNT(*) as count 
            FROM reviews $whereClause AND review_language IS NOT NULL 
            GROUP BY review_language 
            ORDER BY count DESC
        ", $params),
        'traveler_types' => $db->fetchAll("
            SELECT traveler_type_spanish as type, COUNT(*) as count 
            FROM reviews $whereClause AND traveler_type_spanish IS NOT NULL 
            GROUP BY traveler_type_spanish 
            ORDER BY count DESC
        ", $params),
        'ratings' => $db->fetchAll("
            SELECT rating, COUNT(*) as count 
            FROM reviews $whereClause 
            GROUP BY rating 
            ORDER BY rating DESC
        ", $params)
    ];
    
    $response = [
        'reviews' => $formattedReviews,
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => (int) $totalCount,
            'total_pages' => ceil($totalCount / $limit),
            'has_next' => $page < ceil($totalCount / $limit),
            'has_prev' => $page > 1
        ],
        'filters' => $filterStats,
        'summary' => [
            'average_rating' => $totalCount > 0 ? 
                $db->fetchOne("SELECT ROUND(AVG(rating), 2) as avg FROM reviews $whereClause", $params)['avg'] : 0,
            'total_responses' => $db->fetchOne("
                SELECT COUNT(*) as total 
                FROM reviews $whereClause 
                AND property_response IS NOT NULL 
                AND property_response != ''
            ", $params)['total'],
            'response_rate' => $totalCount > 0 ? 
                round(($db->fetchOne("
                    SELECT COUNT(*) as total 
                    FROM reviews $whereClause 
                    AND property_response IS NOT NULL 
                    AND property_response != ''
                ", $params)['total'] / $totalCount) * 100, 1) : 0
        ]
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Failed to fetch reviews: ' . $e->getMessage()], 500);
}
?>