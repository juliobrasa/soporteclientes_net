<?php
/**
 * API para el dashboard de clientes
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Hotel-ID, X-Date-Range');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../admin-config.php';

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Obtener conexión a base de datos
$pdo = getDBConnection();
if (!$pdo) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'hotels':
        getHotels($pdo);
        break;
        
    case 'dashboard':
        getDashboardData($pdo);
        break;
        
    case 'otas':
        getOTAsData($pdo);
        break;
        
    case 'reviews':
        getReviewsData($pdo);
        break;
        
    case 'stats':
        getStatsData($pdo);
        break;
        
    default:
        response(['error' => 'Acción no válida'], 400);
}

/**
 * Obtener lista de hoteles disponibles
 */
function getHotels($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT id, nombre_hotel, google_place_id, activo
            FROM hoteles 
            WHERE activo = 1 
            ORDER BY nombre_hotel
        ");
        
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        response([
            'success' => true,
            'data' => $hotels
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo hoteles: ' . $e->getMessage()], 500);
    }
}

/**
 * Obtener datos del dashboard principal
 */
function getDashboardData($pdo) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    try {
        // Calcular fechas
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Obtener información del hotel
        $hotelStmt = $pdo->prepare("SELECT nombre_hotel FROM hoteles WHERE id = ?");
        $hotelStmt->execute([$hotelId]);
        $hotel = $hotelStmt->fetch();
        
        if (!$hotel) {
            response(['error' => 'Hotel no encontrado'], 404);
        }
        
        // Obtener estadísticas de reseñas
        $reviewsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews,
                COUNT(DISTINCT source_platform) as platforms_count
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
        ");
        
        $reviewsStmt->execute([$hotelId, $startDate, $endDate]);
        $reviewStats = $reviewsStmt->fetch();
        
        // Obtener reseñas del período anterior para comparación
        $prevStartDate = date('Y-m-d', strtotime("-" . ($dateRange * 2) . " days"));
        $prevEndDate = $startDate;
        
        $prevReviewsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
        ");
        
        $prevReviewsStmt->execute([$hotelId, $prevStartDate, $prevEndDate]);
        $prevReviewStats = $prevReviewsStmt->fetch();
        
        // Calcular cambios porcentuales
        $reviewsChange = $prevReviewStats['total_reviews'] > 0 
            ? (($reviewStats['total_reviews'] - $prevReviewStats['total_reviews']) / $prevReviewStats['total_reviews']) * 100
            : 0;
            
        $ratingChange = $prevReviewStats['avg_rating'] > 0
            ? (($reviewStats['avg_rating'] - $prevReviewStats['avg_rating']) / $prevReviewStats['avg_rating']) * 100
            : 0;
        
        // Calcular IRO (Índice de Reputación Online)
        $iroScore = calculateIRO($reviewStats);
        
        // Calcular índice semántico
        $semanticScore = calculateSemanticIndex($pdo, $hotelId, $startDate, $endDate);
        
        $dashboardData = [
            'hotel' => $hotel,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $dateRange
            ],
            'iro' => [
                'score' => $iroScore,
                'change' => $reviewsChange > 0 ? $reviewsChange : -abs($reviewsChange),
                'trend' => $reviewsChange >= 0 ? 'up' : 'down',
                'calificacion' => [
                    'value' => round(($reviewStats['avg_rating'] / 5) * 100),
                    'trend' => $ratingChange >= 0 ? 'up' : 'down'
                ],
                'cobertura' => [
                    'value' => min(100, ($reviewStats['total_reviews'] / 100) * 100),
                    'trend' => $reviewsChange >= 0 ? 'up' : 'down'
                ],
                'reseñas' => [
                    'value' => min(100, $reviewStats['total_reviews'] * 2),
                    'trend' => $reviewsChange >= 0 ? 'up' : 'down'
                ]
            ],
            'semantico' => $semanticScore,
            'stats' => [
                'total_reviews' => (int)$reviewStats['total_reviews'],
                'avg_rating' => round($reviewStats['avg_rating'], 2),
                'positive_reviews' => (int)$reviewStats['positive_reviews'],
                'negative_reviews' => (int)$reviewStats['negative_reviews'],
                'platforms_count' => (int)$reviewStats['platforms_count'],
                'changes' => [
                    'reviews' => round($reviewsChange, 1),
                    'rating' => round($ratingChange, 1)
                ]
            ]
        ];
        
        response([
            'success' => true,
            'data' => $dashboardData
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo datos del dashboard: ' . $e->getMessage()], 500);
    }
}

/**
 * Obtener datos de OTAs
 */
function getOTAsData($pdo) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Obtener estadísticas por plataforma
        $otasStmt = $pdo->prepare("
            SELECT 
                source_platform as platform,
                COUNT(*) as reviews_count,
                AVG(rating) as avg_rating,
                MIN(scraped_at) as first_review,
                MAX(scraped_at) as latest_review
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
            GROUP BY source_platform
            ORDER BY reviews_count DESC
        ");
        
        $otasStmt->execute([$hotelId, $startDate, $endDate]);
        $otasData = $otasStmt->fetchAll();
        
        // Obtener datos del año completo para acumulados
        $yearStart = date('Y-01-01');
        $accStmt = $pdo->prepare("
            SELECT 
                source_platform as platform,
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
            GROUP BY source_platform
        ");
        
        $accStmt->execute([$hotelId, $yearStart, $endDate]);
        $accumulatedData = [];
        foreach ($accStmt->fetchAll() as $row) {
            $accumulatedData[$row['platform']] = $row;
        }
        
        // Mapear datos con información de OTAs
        $otasMapping = [
            'booking' => ['name' => 'Booking.com', 'logo' => 'B', 'color' => 'bg-blue-700'],
            'google' => ['name' => 'Google', 'logo' => 'G', 'color' => 'bg-red-500'],
            'tripadvisor' => ['name' => 'TripAdvisor', 'logo' => 'T', 'color' => 'bg-green-600'],
            'expedia' => ['name' => 'Expedia Group', 'logo' => 'E', 'color' => 'bg-blue-600'],
            'despegar' => ['name' => 'Despegar Group', 'logo' => 'D', 'color' => 'bg-purple-600']
        ];
        
        $formattedOTAs = [];
        foreach ($otasMapping as $platform => $info) {
            $currentData = null;
            foreach ($otasData as $ota) {
                if ($ota['platform'] === $platform) {
                    $currentData = $ota;
                    break;
                }
            }
            
            $accData = $accumulatedData[$platform] ?? null;
            
            $formattedOTAs[] = [
                'platform' => $platform,
                'name' => $info['name'],
                'logo' => $info['logo'],
                'bgColor' => $info['color'],
                'rating' => $currentData ? round($currentData['avg_rating'], 2) : null,
                'reviews' => $currentData ? (int)$currentData['reviews_count'] : null,
                'accumulated2025' => $accData ? round($accData['avg_rating'], 2) : null,
                'totalReviews' => $accData ? (int)$accData['total_reviews'] : 0
            ];
        }
        
        response([
            'success' => true,
            'data' => $formattedOTAs
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo datos de OTAs: ' . $e->getMessage()], 500);
    }
}

/**
 * Obtener datos de reseñas
 */
function getReviewsData($pdo) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Obtener reseñas
        $reviewsStmt = $pdo->prepare("
            SELECT 
                id,
                user_name as guest,
                user_location as country,
                DATE_FORMAT(review_date, '%d %b %Y') as date,
                traveler_type as tripType,
                platform_review_id as reviewId,
                source_platform as platform,
                rating,
                review_title as title,
                liked_text as positive,
                disliked_text as negative,
                property_response,
                scraped_at
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
            ORDER BY scraped_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $reviewsStmt->execute([$hotelId, $startDate, $endDate, $limit, $offset]);
        $reviews = $reviewsStmt->fetchAll();
        
        // Procesar reseñas para el frontend
        foreach ($reviews as &$review) {
            $review['guest'] = $review['guest'] ?: 'Usuario Anónimo';
            $review['country'] = $review['country'] ?: 'No especificado';
            $review['tripType'] = $review['tripType'] ?: 'No especificado';
            $review['hasResponse'] = !empty($review['property_response']);
            $review['rating'] = (float)$review['rating'];
            
            // Limpiar campos vacíos
            if (empty($review['positive'])) $review['positive'] = '';
            if (empty($review['negative'])) $review['negative'] = '';
            
            unset($review['property_response'], $review['scraped_at']);
        }
        
        // Obtener total de reseñas para paginación
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
        ");
        $countStmt->execute([$hotelId, $startDate, $endDate]);
        $total = $countStmt->fetchColumn();
        
        response([
            'success' => true,
            'data' => $reviews,
            'pagination' => [
                'total' => (int)$total,
                'limit' => (int)$limit,
                'offset' => (int)$offset,
                'hasMore' => ($offset + $limit) < $total
            ]
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo reseñas: ' . $e->getMessage()], 500);
    }
}

/**
 * Obtener estadísticas generales
 */
function getStatsData($pdo) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Estadísticas básicas
        $basicStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_count,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as neutral_count,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_count
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
        ");
        
        $basicStmt->execute([$hotelId, $startDate, $endDate]);
        $stats = $basicStmt->fetch();
        
        // Calcular NPS
        $totalReviews = (int)$stats['total_reviews'];
        $promoters = (int)$stats['positive_count'];
        $detractors = (int)$stats['negative_count'];
        $nps = $totalReviews > 0 ? (($promoters - $detractors) / $totalReviews) * 100 : 0;
        
        // Cobertura por NPS
        $coverage = [
            'promoters' => $totalReviews > 0 ? round(($promoters / $totalReviews) * 100) : 0,
            'neutrals' => $totalReviews > 0 ? round(((int)$stats['neutral_count'] / $totalReviews) * 100) : 0,
            'detractors' => $totalReviews > 0 ? round(($detractors / $totalReviews) * 100) : 0
        ];
        
        response([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'avg_rating' => round($stats['avg_rating'], 2),
                'coverage_total' => min(100, $totalReviews * 2), // Simulado
                'nps' => round($nps),
                'coverage_nps' => $coverage,
                'cases_created' => 0 // Placeholder
            ]
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo estadísticas: ' . $e->getMessage()], 500);
    }
}

/**
 * Calcular Índice de Reputación Online (IRO)
 */
function calculateIRO($reviewStats) {
    if (!$reviewStats['total_reviews']) return 0;
    
    $ratingScore = ($reviewStats['avg_rating'] / 5) * 40; // 40% peso
    $volumeScore = min(30, $reviewStats['total_reviews']); // 30% peso máximo
    $sentimentScore = ($reviewStats['positive_reviews'] / $reviewStats['total_reviews']) * 30; // 30% peso
    
    return round($ratingScore + $volumeScore + $sentimentScore);
}

/**
 * Calcular índice semántico
 */
function calculateSemanticIndex($pdo, $hotelId, $startDate, $endDate) {
    try {
        // Buscar palabras negativas en las reseñas
        $negativeStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                COUNT(CASE WHEN 
                    LOWER(liked_text) REGEXP 'malo|terrible|horrible|pesimo|sucio|roto|feo' OR
                    LOWER(disliked_text) REGEXP 'malo|terrible|horrible|pesimo|sucio|roto|feo' OR
                    rating <= 2
                THEN 1 END) as negative_mentions
            FROM reviews 
            WHERE hotel_id = ? 
            AND scraped_at BETWEEN ? AND ?
        ");
        
        $negativeStmt->execute([$hotelId, $startDate, $endDate]);
        $sentimentData = $negativeStmt->fetch();
        
        $totalReviews = (int)$sentimentData['total_reviews'];
        $negativeMentions = (int)$sentimentData['negative_mentions'];
        
        if ($totalReviews === 0) {
            return [
                'score' => 50,
                'status' => 'neutral',
                'change' => 0,
                'message' => 'No hay datos suficientes para calcular el índice semántico.'
            ];
        }
        
        // Calcular porcentaje de sentimiento positivo
        $positivePercentage = (($totalReviews - $negativeMentions) / $totalReviews) * 100;
        
        $status = 'good';
        $message = 'Tu propiedad tiene un buen sentimiento general en las reseñas.';
        
        if ($positivePercentage < 30) {
            $status = 'bad';
            $message = 'Cuidado, tu propiedad tiene bastantes menciones negativas en los comentarios.';
        } elseif ($positivePercentage < 60) {
            $status = 'regular';
            $message = 'Tu propiedad tiene un sentimiento mixto en las reseñas.';
        }
        
        return [
            'score' => round($positivePercentage),
            'status' => $status,
            'change' => -50, // Placeholder - calcularias vs período anterior
            'message' => $message
        ];
        
    } catch (Exception $e) {
        return [
            'score' => 50,
            'status' => 'unknown',
            'change' => 0,
            'message' => 'Error calculando índice semántico.'
        ];
    }
}
?>