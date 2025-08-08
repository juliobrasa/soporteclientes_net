<?php
/**
 * API de Reviews - Versión Unificada 
 * 
 * Versión actualizada que aprovecha el esquema unificado de la tabla reviews
 * Compatible con datos de Apify y sistema legacy
 */

require_once __DIR__ . '/../env-loader.php';
require_once __DIR__ . '/../ReviewsSchemaAdapter.php';

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class ReviewsUnifiedAPI 
{
    private $pdo;
    
    public function __construct() 
    {
        try {
            $this->pdo = createDatabaseConnection();
        } catch (PDOException $e) {
            $this->jsonError("Database connection failed: " . $e->getMessage(), 500);
        }
    }
    
    public function handleRequest() 
    {
        $action = $_GET['action'] ?? 'list';
        
        switch ($action) {
            case 'list':
                return $this->listReviews();
            case 'stats':
                return $this->getStats();
            case 'compatibility':
                return $this->getCompatibilityInfo();
            case 'sources':
                return $this->getExtractionSources();
            default:
                $this->jsonError("Invalid action: $action", 400);
        }
    }
    
    /**
     * Listar reviews con filtros avanzados
     */
    private function listReviews() 
    {
        $params = $this->getRequestParams();
        $whereConditions = ["1=1"];
        $sqlParams = [];
        
        // Filtros básicos
        if ($params['hotel_id']) {
            $whereConditions[] = "hotel_id = ?";
            $sqlParams[] = $params['hotel_id'];
        }
        
        if ($params['platform']) {
            // Usar campo unificado - buscar en ambas columnas
            $whereConditions[] = "(source_platform = ? OR platform = ?)";
            $sqlParams[] = $params['platform'];
            $sqlParams[] = $params['platform'];
        }
        
        if ($params['rating_min']) {
            // Usar campo unificado - buscar en ambas columnas rating
            $whereConditions[] = "(rating >= ? OR normalized_rating >= ?)";
            $sqlParams[] = $params['rating_min'];
            $sqlParams[] = $params['rating_min'];
        }
        
        if ($params['rating_max']) {
            $whereConditions[] = "(rating <= ? OR normalized_rating <= ?)";
            $sqlParams[] = $params['rating_max'];
            $sqlParams[] = $params['rating_max'];
        }
        
        if ($params['date_from']) {
            $whereConditions[] = "review_date >= ?";
            $sqlParams[] = $params['date_from'];
        }
        
        if ($params['date_to']) {
            $whereConditions[] = "review_date <= ?";
            $sqlParams[] = $params['date_to'];
        }
        
        if ($params['has_response'] !== null) {
            if ($params['has_response']) {
                $whereConditions[] = "(property_response IS NOT NULL AND property_response != '') OR (response_from_owner IS NOT NULL AND response_from_owner != '')";
            } else {
                $whereConditions[] = "(property_response IS NULL OR property_response = '') AND (response_from_owner IS NULL OR response_from_owner = '')";
            }
        }
        
        if ($params['extraction_source']) {
            $whereConditions[] = "extraction_source = ?";
            $sqlParams[] = $params['extraction_source'];
        }
        
        if ($params['verified_only']) {
            $whereConditions[] = "is_verified = 1";
        }
        
        if ($params['search']) {
            $searchTerm = "%{$params['search']}%";
            $whereConditions[] = "(
                review_title LIKE ? OR 
                liked_text LIKE ? OR 
                disliked_text LIKE ? OR 
                review_text LIKE ? OR
                user_name LIKE ? OR 
                reviewer_name LIKE ?
            )";
            $sqlParams = array_merge($sqlParams, array_fill(0, 6, $searchTerm));
        }
        
        $whereClause = "WHERE " . implode(" AND ", $whereConditions);
        
        // Contar total
        $totalQuery = "SELECT COUNT(*) as total FROM reviews $whereClause";
        $stmt = $this->pdo->prepare($totalQuery);
        $stmt->execute($sqlParams);
        $total = $stmt->fetch()['total'];
        
        // Obtener reviews con paginación
        $offset = ($params['page'] - 1) * $params['limit'];
        
        $reviewsQuery = "
            SELECT 
                id,
                unique_id,
                hotel_id,
                
                -- Campos unificados de usuario (priorizar nombres estándar)
                COALESCE(user_name, reviewer_name) as user_name,
                COALESCE(reviewer_name, user_name) as reviewer_name,
                user_location,
                
                -- Campos unificados de contenido
                review_title,
                liked_text,
                disliked_text,
                review_text,
                
                -- Campos unificados de calificación
                COALESCE(rating, normalized_rating) as rating,
                COALESCE(normalized_rating, rating) as normalized_rating,
                
                -- Campos unificados de plataforma
                COALESCE(source_platform, platform) as source_platform,
                COALESCE(platform, source_platform) as platform,
                
                -- Campos unificados de respuesta
                COALESCE(property_response, response_from_owner) as property_response,
                COALESCE(response_from_owner, property_response) as response_from_owner,
                
                -- Campos de fechas y metadatos
                review_date,
                scraped_at,
                review_language,
                language_detected,
                traveler_type_spanish,
                helpful_votes,
                
                -- Campos nuevos del esquema unificado
                platform_review_id,
                extraction_source,
                sentiment_score,
                tags,
                is_verified,
                processed_at
                
            FROM reviews 
            $whereClause
            ORDER BY scraped_at DESC, review_date DESC
            LIMIT ? OFFSET ?
        ";
        
        $stmt = $this->pdo->prepare($reviewsQuery);
        $stmt->execute(array_merge($sqlParams, [$params['limit'], $offset]));
        $reviews = $stmt->fetchAll();
        
        // Formatear reviews para el frontend
        $formattedReviews = [];
        foreach ($reviews as $review) {
            $formattedReviews[] = $this->formatReview($review);
        }
        
        // Obtener estadísticas de filtros
        $filterStats = $this->getFilterStats($whereClause, $sqlParams);
        
        $this->jsonResponse([
            'success' => true,
            'data' => $formattedReviews,
            'pagination' => [
                'current_page' => $params['page'],
                'per_page' => $params['limit'],
                'total' => (int) $total,
                'total_pages' => ceil($total / $params['limit']),
                'has_next' => ($offset + $params['limit']) < $total,
                'has_prev' => $params['page'] > 1
            ],
            'filters' => $filterStats,
            'meta' => [
                'unified_schema' => true,
                'api_version' => '2.0',
                'generated_at' => date('c')
            ]
        ]);
    }
    
    /**
     * Formatear review para respuesta consistente
     */
    private function formatReview($review) 
    {
        // Determinar sentimiento
        $rating = $review['rating'] ?: $review['normalized_rating'];
        $sentiment = 'neutral';
        
        if ($rating >= 8) $sentiment = 'positive';
        elseif ($rating <= 4) $sentiment = 'negative';
        
        // Parsear tags JSON si existen
        $tags = [];
        if ($review['tags']) {
            $tags = json_decode($review['tags'], true) ?: [];
        }
        
        // Combinar texto de reseña si no existe review_text
        $fullReviewText = $review['review_text'];
        if (!$fullReviewText && ($review['liked_text'] || $review['disliked_text'])) {
            $parts = [];
            if ($review['liked_text']) $parts[] = $review['liked_text'];
            if ($review['disliked_text']) $parts[] = $review['disliked_text'];
            $fullReviewText = implode(' | ', $parts);
        }
        
        return [
            'id' => $review['unique_id'],
            'internal_id' => $review['id'],
            'guest' => $review['user_name'] ?: 'Usuario Anónimo',
            'country' => $review['user_location'] ?: 'No especificado',
            'date' => $review['review_date'] ? date('d M Y', strtotime($review['review_date'])) : null,
            'scraped_date' => date('d M Y H:i', strtotime($review['scraped_at'])),
            
            'platform' => $review['source_platform'] ?: $review['platform'],
            'platform_review_id' => $review['platform_review_id'],
            'extraction_source' => $review['extraction_source'] ?: 'legacy',
            
            'rating' => (float) $rating,
            'sentiment' => $sentiment,
            'sentiment_score' => $review['sentiment_score'] ? (float) $review['sentiment_score'] : null,
            
            'title' => $review['review_title'],
            'content' => [
                'full_text' => $fullReviewText,
                'positive' => $review['liked_text'],
                'negative' => $review['disliked_text']
            ],
            
            'response' => [
                'has_response' => !empty($review['property_response']) || !empty($review['response_from_owner']),
                'text' => $review['property_response'] ?: $review['response_from_owner']
            ],
            
            'metadata' => [
                'language' => $review['review_language'] ?: $review['language_detected'],
                'traveler_type' => $review['traveler_type_spanish'],
                'helpful_votes' => (int) $review['helpful_votes'],
                'is_verified' => (bool) $review['is_verified'],
                'processed_at' => $review['processed_at'],
                'hotel_id' => $review['hotel_id']
            ],
            
            'tags' => $tags
        ];
    }
    
    /**
     * Obtener estadísticas generales
     */
    private function getStats() 
    {
        try {
            $stats = [];
            
            // Total reviews
            $stmt = $this->pdo->query("SELECT COUNT(*) as total FROM reviews");
            $stats['total_reviews'] = (int) $stmt->fetch()['total'];
            
            // Por plataforma (unificado)
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(source_platform, platform, 'unknown') as platform,
                    COUNT(*) as count,
                    AVG(COALESCE(rating, normalized_rating)) as avg_rating
                FROM reviews 
                GROUP BY COALESCE(source_platform, platform, 'unknown')
                ORDER BY count DESC
            ");
            $stats['by_platform'] = $stmt->fetchAll();
            
            // Por fuente de extracción
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    COUNT(*) as count,
                    AVG(COALESCE(rating, normalized_rating)) as avg_rating
                FROM reviews 
                GROUP BY COALESCE(extraction_source, 'legacy')
                ORDER BY count DESC
            ");
            $stats['by_extraction_source'] = $stmt->fetchAll();
            
            // Actividad reciente (últimos 30 días)
            $stmt = $this->pdo->query("
                SELECT 
                    DATE(scraped_at) as date,
                    COUNT(*) as count
                FROM reviews 
                WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(scraped_at)
                ORDER BY date DESC
                LIMIT 30
            ");
            $stats['recent_activity'] = $stmt->fetchAll();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $stats
            ]);
            
        } catch (Exception $e) {
            $this->jsonError("Error getting stats: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener información de compatibilidad del esquema
     */
    private function getCompatibilityInfo() 
    {
        try {
            $compatibility = ReviewsSchemaAdapter::getCompatibilityStats($this->pdo);
            
            $this->jsonResponse([
                'success' => true,
                'data' => $compatibility
            ]);
            
        } catch (Exception $e) {
            $this->jsonError("Error getting compatibility info: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener fuentes de extracción disponibles
     */
    private function getExtractionSources() 
    {
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as recent_reviews,
                    MIN(scraped_at) as first_review,
                    MAX(scraped_at) as latest_review
                FROM reviews 
                GROUP BY COALESCE(extraction_source, 'legacy')
                ORDER BY total_reviews DESC
            ");
            
            $sources = $stmt->fetchAll();
            
            $this->jsonResponse([
                'success' => true,
                'data' => $sources
            ]);
            
        } catch (Exception $e) {
            $this->jsonError("Error getting extraction sources: " . $e->getMessage(), 500);
        }
    }
    
    /**
     * Obtener parámetros de request con validación
     */
    private function getRequestParams() 
    {
        return [
            'page' => max(1, (int) ($_GET['page'] ?? 1)),
            'limit' => min(100, max(1, (int) ($_GET['limit'] ?? 20))),
            'hotel_id' => $_GET['hotel_id'] ? (int) $_GET['hotel_id'] : null,
            'platform' => $_GET['platform'] ?? null,
            'rating_min' => $_GET['rating_min'] ? (float) $_GET['rating_min'] : null,
            'rating_max' => $_GET['rating_max'] ? (float) $_GET['rating_max'] : null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'has_response' => isset($_GET['has_response']) ? (bool) $_GET['has_response'] : null,
            'extraction_source' => $_GET['extraction_source'] ?? null,
            'verified_only' => isset($_GET['verified_only']) ? (bool) $_GET['verified_only'] : false,
            'search' => $_GET['search'] ?? null
        ];
    }
    
    /**
     * Obtener estadísticas de filtros
     */
    private function getFilterStats($whereClause, $sqlParams) 
    {
        try {
            $stats = [];
            
            // Plataformas disponibles
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(source_platform, platform) as platform, COUNT(*) as count 
                FROM reviews $whereClause 
                GROUP BY COALESCE(source_platform, platform)
                ORDER BY count DESC
            ");
            $stmt->execute($sqlParams);
            $stats['platforms'] = $stmt->fetchAll();
            
            // Calificaciones
            $stmt = $this->pdo->prepare("
                SELECT COALESCE(rating, normalized_rating) as rating, COUNT(*) as count 
                FROM reviews $whereClause 
                GROUP BY COALESCE(rating, normalized_rating)
                ORDER BY rating DESC
            ");
            $stmt->execute($sqlParams);
            $stats['ratings'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function jsonResponse($data, $code = 200) 
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    private function jsonError($message, $code = 400) 
    {
        $this->jsonResponse([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], $code);
    }
}

// Ejecutar API
try {
    $api = new ReviewsUnifiedAPI();
    $api->handleRequest();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>