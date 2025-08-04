<?php
// api/hotels.php
require_once 'config.php';

$db = new Database();

try {
    // Obtener lista de hoteles con estadísticas básicas
    $hotels = $db->fetchAll("
        SELECT DISTINCT
            r.hotel_name,
            r.hotel_destination,
            COUNT(*) as total_reviews,
            ROUND(AVG(r.rating), 2) as average_rating,
            MIN(r.review_date) as first_review,
            MAX(r.review_date) as last_review,
            COUNT(CASE WHEN r.review_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as recent_reviews,
            COUNT(CASE WHEN r.property_response IS NOT NULL AND r.property_response != '' THEN 1 END) as responded_reviews,
            h.activo as is_active,
            h.max_reviews
        FROM reviews r
        LEFT JOIN hoteles h ON r.hotel_name = h.nombre_hotel
        GROUP BY r.hotel_name, r.hotel_destination
        ORDER BY r.hotel_destination, total_reviews DESC
    ");
    
    // Obtener estadísticas por destino
    $destinations = $db->fetchAll("
        SELECT 
            hotel_destination,
            COUNT(DISTINCT hotel_name) as hotels_count,
            COUNT(*) as total_reviews,
            ROUND(AVG(rating), 2) as avg_rating
        FROM reviews 
        GROUP BY hotel_destination
        ORDER BY total_reviews DESC
    ");
    
    // Formatear datos para el frontend
    $formattedHotels = [];
    foreach ($hotels as $hotel) {
        $responseRate = $hotel['total_reviews'] > 0 
            ? ($hotel['responded_reviews'] / $hotel['total_reviews']) * 100
            : 0;
        
        $formattedHotels[] = [
            'id' => md5($hotel['hotel_name'] . $hotel['hotel_destination']),
            'name' => $hotel['hotel_name'],
            'destination' => $hotel['hotel_destination'],
            'type' => 'hotel', // Todos son hoteles reales
            'stats' => [
                'total_reviews' => (int) $hotel['total_reviews'],
                'average_rating' => (float) $hotel['average_rating'],
                'recent_reviews' => (int) $hotel['recent_reviews'],
                'response_rate' => round($responseRate, 1),
                'first_review' => $hotel['first_review'],
                'last_review' => $hotel['last_review']
            ],
            'config' => [
                'is_active' => (bool) ($hotel['is_active'] ?? true),
                'max_reviews' => (int) ($hotel['max_reviews'] ?? 50)
            ]
        ];
    }
    
    $formattedDestinations = [];
    foreach ($destinations as $dest) {
        $formattedDestinations[] = [
            'name' => $dest['hotel_destination'],
            'hotels_count' => (int) $dest['hotels_count'],
            'total_reviews' => (int) $dest['total_reviews'],
            'average_rating' => (float) $dest['avg_rating']
        ];
    }
    
    $response = [
        'hotels' => $formattedHotels,
        'destinations' => $formattedDestinations,
        'summary' => [
            'total_hotels' => count($formattedHotels),
            'total_destinations' => count($formattedDestinations),
            'total_reviews' => array_sum(array_column($hotels, 'total_reviews')),
            'last_updated' => date('Y-m-d H:i:s')
        ]
    ];
    
    jsonResponse($response);
    
} catch (Exception $e) {
    jsonResponse(['error' => 'Failed to fetch hotels: ' . $e->getMessage()], 500);
}
?>