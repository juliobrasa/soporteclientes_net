<?php
/**
 * API para el panel de clientes autenticados
 */
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Verificar autenticación
if (!isset($_SESSION['client_user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$user = $_SESSION['client_user'];

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Configuración de base de datos
$host = "soporteclientes.net";
$dbname = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

$action = $_GET['action'] ?? '';

// Verificar que el usuario tenga acceso al hotel solicitado
function verifyHotelAccess($hotelId, $user) {
    foreach ($user['hotels'] as $hotel) {
        if ($hotel['id'] == $hotelId) {
            return true;
        }
    }
    return false;
}

switch ($action) {
    case 'reviews':
        getReviews($pdo, $user);
        break;
        
    case 'stats':
        getStats($pdo, $user);
        break;
        
    case 'dashboard':
        getDashboardData($pdo, $user);
        break;
        
    case 'generate_response':
        generateResponse($pdo, $user);
        break;
        
    case 'create_case':
        createCase($pdo, $user);
        break;
        
    case 'translate':
        translateReview($pdo, $user);
        break;
        
    default:
        response(['error' => 'Acción no válida'], 400);
}

/**
 * Obtener reseñas del hotel
 */
function getReviews($pdo, $user) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    $platform = $_GET['platform'] ?? null;
    $sort = $_GET['sort'] ?? 'date_desc';
    $limit = $_GET['limit'] ?? 20;
    $offset = $_GET['offset'] ?? 0;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    if (!verifyHotelAccess($hotelId, $user)) {
        response(['error' => 'Sin acceso a este hotel'], 403);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Validar y convertir límites a enteros
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        // Construir consulta con filtro opcional de plataforma
        $whereClause = "WHERE hotel_id = ? AND DATE(scraped_at) BETWEEN ? AND ?";
        $params = [$hotelId, $startDate, $endDate];
        
        if (!empty($platform)) {
            $whereClause .= " AND source_platform = ?";
            $params[] = $platform;
        }
        
        // Construir cláusula ORDER BY
        $orderClause = "ORDER BY ";
        switch ($sort) {
            case 'date_asc':
                $orderClause .= "scraped_at ASC";
                break;
            case 'rating_desc':
                $orderClause .= "rating DESC, scraped_at DESC";
                break;
            case 'rating_asc':
                $orderClause .= "rating ASC, scraped_at DESC";
                break;
            case 'date_desc':
            default:
                $orderClause .= "scraped_at DESC";
                break;
        }
        
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
            $whereClause
            $orderClause
            LIMIT $limit OFFSET $offset
        ");
        
        $reviewsStmt->execute($params);
        $reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Procesar reseñas
        foreach ($reviews as &$review) {
            $review['guest'] = $review['guest'] ?: 'Usuario Anónimo';
            $review['country'] = $review['country'] ?: 'No especificado';
            $review['tripType'] = $review['tripType'] ?: 'No especificado';
            $review['hasResponse'] = !empty($review['property_response']);
            $review['rating'] = (float)$review['rating'];
            
            if (empty($review['positive'])) $review['positive'] = '';
            if (empty($review['negative'])) $review['negative'] = '';
            
            unset($review['property_response'], $review['scraped_at']);
        }
        
        // Obtener total para paginación
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total 
            FROM reviews 
            $whereClause
        ");
        $countStmt->execute($params);
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
 * Obtener estadísticas del hotel
 */
function getStats($pdo, $user) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    $platform = $_GET['platform'] ?? null;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    if (!verifyHotelAccess($hotelId, $user)) {
        response(['error' => 'Sin acceso a este hotel'], 403);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Construir consulta con filtro opcional de plataforma
        $whereClause = "WHERE hotel_id = ? AND DATE(scraped_at) BETWEEN ? AND ?";
        $params = [$hotelId, $startDate, $endDate];
        
        if (!empty($platform)) {
            $whereClause .= " AND source_platform = ?";
            $params[] = $platform;
        }
        
        $statsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_count,
                COUNT(CASE WHEN rating = 3 THEN 1 END) as neutral_count,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_count
            FROM reviews 
            $whereClause
        ");
        
        $statsStmt->execute($params);
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalReviews = (int)$stats['total_reviews'];
        $promoters = (int)$stats['positive_count'];
        $detractors = (int)$stats['negative_count'];
        $neutrals = (int)$stats['neutral_count'];
        
        // Calcular NPS
        $nps = $totalReviews > 0 ? (($promoters - $detractors) / $totalReviews) * 100 : 0;
        
        // Cobertura por NPS
        $coverage = [
            'promoters' => $totalReviews > 0 ? round(($promoters / $totalReviews) * 100) : 0,
            'neutrals' => $totalReviews > 0 ? round(($neutrals / $totalReviews) * 100) : 0,
            'detractors' => $totalReviews > 0 ? round(($detractors / $totalReviews) * 100) : 0
        ];
        
        response([
            'success' => true,
            'data' => [
                'total_reviews' => $totalReviews,
                'avg_rating' => round((float)$stats['avg_rating'], 2),
                'coverage_total' => min(100, $totalReviews * 2),
                'nps' => round($nps),
                'coverage_nps' => $coverage,
                'cases_created' => 0
            ]
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo estadísticas: ' . $e->getMessage()], 500);
    }
}

/**
 * Obtener datos del dashboard
 */
function getDashboardData($pdo, $user) {
    $hotelId = $_GET['hotel_id'] ?? null;
    $dateRange = $_GET['date_range'] ?? 30;
    
    if (!$hotelId) {
        response(['error' => 'hotel_id es requerido'], 400);
    }
    
    if (!verifyHotelAccess($hotelId, $user)) {
        response(['error' => 'Sin acceso a este hotel'], 403);
    }
    
    try {
        $startDate = date('Y-m-d', strtotime("-{$dateRange} days"));
        $endDate = date('Y-m-d');
        
        // Obtener estadísticas básicas
        $reviewsStmt = $pdo->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as avg_rating,
                COUNT(CASE WHEN rating >= 4 THEN 1 END) as positive_reviews,
                COUNT(CASE WHEN rating <= 2 THEN 1 END) as negative_reviews
            FROM reviews 
            WHERE hotel_id = ? 
            AND DATE(scraped_at) BETWEEN ? AND ?
        ");
        
        $reviewsStmt->execute([$hotelId, $startDate, $endDate]);
        $reviewStats = $reviewsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Calcular IRO simple
        $iroScore = 0;
        if ($reviewStats['total_reviews'] > 0) {
            $ratingScore = ($reviewStats['avg_rating'] / 5) * 40;
            $volumeScore = min(30, $reviewStats['total_reviews']);
            $sentimentScore = ($reviewStats['positive_reviews'] / $reviewStats['total_reviews']) * 30;
            $iroScore = round($ratingScore + $volumeScore + $sentimentScore);
        }
        
        response([
            'success' => true,
            'data' => [
                'iro' => [
                    'score' => $iroScore,
                    'calificacion' => round(($reviewStats['avg_rating'] / 5) * 100),
                    'cobertura' => min(100, $reviewStats['total_reviews'] * 2),
                    'reseñas' => min(100, $reviewStats['total_reviews'] * 3)
                ],
                'stats' => [
                    'total_reviews' => (int)$reviewStats['total_reviews'],
                    'avg_rating' => round((float)$reviewStats['avg_rating'], 2),
                    'otas_activas' => 3
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        response(['error' => 'Error obteniendo datos del dashboard: ' . $e->getMessage()], 500);
    }
}

/**
 * Generar respuesta con IA (simulado)
 */
function generateResponse($pdo, $user) {
    if (!isset($user['features']['ai_responses']) || !$user['features']['ai_responses']) {
        response(['error' => 'Función no disponible en tu plan'], 403);
    }
    
    $reviewId = $_POST['review_id'] ?? null;
    $regenerate = isset($_POST['regenerate']);
    
    if (!$reviewId) {
        response(['error' => 'review_id es requerido'], 400);
    }
    
    // Diferentes conjuntos de respuestas para variedad
    $positiveResponses = [
        "Estimado huésped, agradecemos enormemente sus comentarios positivos. Nos complace saber que disfrutó de su estadía y esperamos recibirle nuevamente pronto.",
        "Muchas gracias por elegirnos y por tomarse el tiempo de compartir su maravillosa experiencia. Sus palabras nos motivan a seguir mejorando cada día.",
        "¡Qué alegría leer sus comentarios! Nos llena de orgullo saber que cumplimos sus expectativas. Esperamos tener el honor de recibirle nuevamente.",
        "Apreciamos profundamente su confianza en nuestros servicios. Es un placer saber que tuvo una experiencia memorable con nosotros."
    ];
    
    $neutralResponses = [
        "Estimado huésped, agradecemos sinceramente su tiempo para compartir sus comentarios. Valoramos mucho su opinión y esperamos poder mejorar su experiencia en futuras visitas.",
        "Gracias por elegirnos para su estadía. Sus comentarios son muy importantes para nosotros y nos ayudan a seguir creciendo como hotel.",
        "Apreciamos su feedback honesto. Trabajamos constantemente para brindar la mejor experiencia posible a todos nuestros huéspedes."
    ];
    
    $negativeResponses = [
        "Estimado huésped, lamentamos profundamente que su experiencia no haya cumplido sus expectativas. Tomamos muy en serio sus comentarios y ya estamos trabajando en las mejoras necesarias. Esperamos tener la oportunidad de brindarle una experiencia excepcional en el futuro.",
        "Gracias por tomarse el tiempo de compartir su experiencia con nosotros. Sentimos mucho los inconvenientes que experimentó durante su estadía. Sus comentarios nos ayudan a identificar áreas de mejora y implementar cambios inmediatos.",
        "Apreciamos su sinceridad al compartir su experiencia. Lamentamos que no hayamos estado a la altura de sus expectativas. Hemos compartido sus comentarios con nuestro equipo de gestión para tomar las medidas correctivas necesarias."
    ];
    
    // Simular análisis del sentimiento de la reseña para elegir respuesta apropiada
    $allResponses = array_merge($positiveResponses, $neutralResponses, $negativeResponses);
    $response = $allResponses[array_rand($allResponses)];
    
    // Si es regeneración, agregar variación
    if ($regenerate) {
        $variations = [
            "Distinguido huésped, ",
            "Apreciado cliente, ",
            "Querido huésped, ",
            "Estimado/a cliente, "
        ];
        
        $endings = [
            " Quedamos a su disposición para cualquier consulta.",
            " No dude en contactarnos para futuras reservas.",
            " Será un placer atenderle nuevamente.",
            " Esperamos su próxima visita con nosotros."
        ];
        
        $variation = $variations[array_rand($variations)];
        $ending = $endings[array_rand($endings)];
        
        // Reemplazar el inicio y agregar final
        $response = $variation . substr($response, strpos($response, ' ') + 1) . $ending;
    }
    
    response([
        'success' => true,
        'data' => [
            'review_id' => $reviewId,
            'response' => $response,
            'generated_at' => date('Y-m-d H:i:s'),
            'regenerated' => $regenerate
        ]
    ]);
}

/**
 * Crear caso (simulado)
 */
function createCase($pdo, $user) {
    $reviewId = $_POST['review_id'] ?? null;
    
    if (!$reviewId) {
        response(['error' => 'review_id es requerido'], 400);
    }
    
    $caseId = 'CASE-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
    
    response([
        'success' => true,
        'data' => [
            'case_id' => $caseId,
            'review_id' => $reviewId,
            'status' => 'created',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
}

/**
 * Traducir reseña (simulado)
 */
function translateReview($pdo, $user) {
    $reviewId = $_POST['review_id'] ?? null;
    
    if (!$reviewId) {
        response(['error' => 'review_id es requerido'], 400);
    }
    
    response([
        'success' => true,
        'data' => [
            'review_id' => $reviewId,
            'original_language' => 'en',
            'translated_language' => 'es',
            'translation' => 'Traducción simulada de la reseña',
            'translated_at' => date('Y-m-d H:i:s')
        ]
    ]);
}
?>