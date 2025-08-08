<?php
/**
 * API específico para extracción de reseñas de Booking.com
 * Basado en voyager/booking-reviews-scraper (ID: PbMHke3jW25J6hSOA)
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'admin-config.php';
require_once 'env-loader.php';

class BookingExtractor {
    private $apiToken;
    private $baseUrl = 'https://api.apify.com/v2';
    private $actorId = 'PbMHke3jW25J6hSOA'; // voyager/booking-reviews-scraper - CONFIRMADO que funciona
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->apiToken = $_ENV['APIFY_API_TOKEN'] ?? getenv('APIFY_API_TOKEN') ?? null;
        
        if (!$this->apiToken) {
            throw new Exception("Token de Apify requerido. Configura APIFY_API_TOKEN en variables de entorno.");
        }
    }
    
    /**
     * Extraer reseñas de Booking.com de forma síncrona
     */
    public function extractBookingReviewsSync($hotelId, $maxReviews = 50, $timeout = 300) {
        // Obtener información del hotel
        $stmt = $this->pdo->prepare("SELECT nombre_hotel, url_booking, booking_url FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            throw new Exception("Hotel no encontrado");
        }
        
        // Usar url_booking como preferencia, luego booking_url
        $bookingUrl = $hotel['url_booking'] ?: $hotel['booking_url'];
        if (!$bookingUrl) {
            throw new Exception("Hotel no tiene URL de Booking configurada");
        }
        
        // Configuración para el actor de Booking
        $input = [
            'startUrls' => [
                ['url' => $bookingUrl]
            ],
            'maxItems' => $maxReviews,
            'proxyConfiguration' => [
                'useApifyProxy' => true,
                'apifyProxyGroups' => ['RESIDENTIAL'] // IMPORTANTE: Usar proxies residenciales
            ]
        ];
        
        $startTime = time();
        
        // Ejecutar extracción síncrona
        $queryParams = http_build_query([
            'timeout' => $timeout,
            'memory' => 2048
        ]);
        
        $response = $this->makeRequest(
            'POST', 
            "/acts/{$this->actorId}/run-sync-get-dataset-items?{$queryParams}", 
            $input
        );
        
        $executionTime = time() - $startTime;
        
        if (!$response || !is_array($response)) {
            throw new Exception("No se obtuvieron resultados de Booking");
        }
        
        // Procesar y guardar reseñas
        $savedCount = $this->saveBookingReviews($hotelId, $response);
        
        // Crear registro en extraction_jobs
        $stmt = $this->pdo->prepare("
            INSERT INTO extraction_jobs (
                hotel_id, status, progress, reviews_extracted, 
                created_at, updated_at, completed_at, platform
            ) VALUES (?, 'completed', 100, ?, NOW(), NOW(), NOW(), 'booking')
        ");
        $stmt->execute([$hotelId, $savedCount]);
        
        $jobId = $this->pdo->lastInsertId();
        
        return [
            'success' => true,
            'job_id' => $jobId,
            'hotel_name' => $hotel['nombre_hotel'],
            'reviews_extracted' => count($response),
            'reviews_saved' => $savedCount,
            'execution_time' => $executionTime,
            'platform' => 'booking',
            'message' => 'Extracción de Booking completada exitosamente'
        ];
    }
    
    /**
     * Guardar reseñas de Booking en la base de datos
     */
    private function saveBookingReviews($hotelId, $bookingReviews) {
        $savedCount = 0;
        
        // Obtener nombre del hotel
        $stmt = $this->pdo->prepare("SELECT nombre_hotel FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        $hotelName = $hotel['nombre_hotel'] ?? 'Hotel Desconocido';
        
        $stmt = $this->pdo->prepare("
            INSERT INTO reviews (
                unique_id, hotel_id, hotel_name, hotel_destination,
                user_name, review_date, rating, review_title, liked_text,
                source_platform, platform_review_id, extraction_run_id, 
                extraction_status, scraped_at, helpful_votes, review_language
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
            ON DUPLICATE KEY UPDATE
                rating = VALUES(rating),
                liked_text = VALUES(liked_text),
                review_date = VALUES(review_date),
                scraped_at = NOW()
        ");
        
        foreach ($bookingReviews as $review) {
            try {
                $normalizedReview = $this->normalizeBookingReview($review);
                
                $stmt->execute([
                    $normalizedReview['unique_id'],
                    $hotelId,
                    $hotelName,
                    'Cancún, México', // Default destination
                    $normalizedReview['user_name'],
                    $normalizedReview['review_date'],
                    $normalizedReview['rating'],
                    $normalizedReview['review_title'],
                    $normalizedReview['review_text'],
                    'booking',
                    $normalizedReview['platform_review_id'],
                    'booking_' . time(),
                    'completed',
                    $normalizedReview['helpful_votes'],
                    'auto'
                ]);
                
                $savedCount++;
            } catch (Exception $e) {
                error_log("Error guardando reseña de Booking: " . $e->getMessage());
            }
        }
        
        return $savedCount;
    }
    
    /**
     * Normalizar datos de reseña de Booking
     */
    private function normalizeBookingReview($review) {
        $reviewId = $review['id'] ?? uniqid('booking_');
        $userName = $review['userName'] ?? $review['author'] ?? 'Anónimo';
        $rating = $this->normalizeRating($review['rating'] ?? 0, 10);
        $reviewTitle = $review['reviewTitle'] ?? 'Reseña de Booking.com';
        
        // Combinar texto positivo y negativo
        $reviewParts = [];
        if (!empty($review['likedText'])) {
            $reviewParts[] = "👍 Liked: " . $review['likedText'];
        }
        if (!empty($review['dislikedText'])) {
            $reviewParts[] = "👎 Disliked: " . $review['dislikedText'];
        }
        $reviewText = implode("\n\n", $reviewParts);
        
        $reviewDate = $this->parseDate($review['reviewDate'] ?? null);
        $helpfulVotes = $review['helpfulVotes'] ?? 0;
        
        return [
            'unique_id' => $reviewId . '_booking',
            'platform_review_id' => $reviewId,
            'user_name' => $userName,
            'rating' => $rating,
            'review_title' => $reviewTitle,
            'review_text' => $reviewText,
            'review_date' => $reviewDate,
            'helpful_votes' => $helpfulVotes
        ];
    }
    
    /**
     * Normalizar rating a escala 1-5
     */
    private function normalizeRating($rating, $maxScale = 10) {
        if (!$rating || !is_numeric($rating)) {
            return null;
        }
        
        $rating = floatval($rating);
        
        // Convertir de escala 1-10 a 1-5
        if ($maxScale == 10) {
            return round(($rating / 10) * 5, 1);
        }
        
        return $rating;
    }
    
    /**
     * Parsear fechas
     */
    private function parseDate($dateString) {
        if (!$dateString) return date('Y-m-d');
        
        try {
            $date = new DateTime($dateString);
            return $date->format('Y-m-d');
        } catch (Exception $e) {
            return date('Y-m-d');
        }
    }
    
    /**
     * Eliminar un trabajo de extracción
     */
    public function deleteJob($jobId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, hotel_id, status FROM extraction_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            $job = $stmt->fetch();
            
            if (!$job) {
                throw new Exception('Trabajo no encontrado');
            }
            
            if ($job['status'] === 'running') {
                throw new Exception('No se puede eliminar un trabajo en ejecución');
            }
            
            $stmt = $this->pdo->prepare("DELETE FROM extraction_jobs WHERE id = ?");
            $stmt->execute([$jobId]);
            
            return [
                'success' => true,
                'message' => 'Trabajo de extracción eliminado correctamente'
            ];
            
        } catch (Exception $e) {
            throw new Exception('Error eliminando trabajo: ' . $e->getMessage());
        }
    }
    
    /**
     * Realizar petición HTTP a Apify
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'User-Agent: Hotel-Reviews-Extractor/1.0'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ];
        
        if ($data && ($method === 'POST' || $method === 'PUT')) {
            $options[CURLOPT_POSTFIELDS] = json_encode($data);
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, $options);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        if ($error) {
            throw new Exception("Error cURL: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Error HTTP {$httpCode}: {$response}");
        }
        
        return json_decode($response, true);
    }
}

function response($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Verificar autenticación
session_start();
if (!isset($_SESSION['admin_logged']) && !isset($_SERVER['HTTP_X_ADMIN_SESSION'])) {
    response(['error' => 'No autorizado'], 401);
}

// Conectar a base de datos
$pdo = getDBConnection();
if (!$pdo) {
    response(['error' => 'Error de conexión a la base de datos'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $bookingExtractor = new BookingExtractor($pdo);
    
    switch ($method) {
        case 'POST':
            if (!isset($input['hotel_id'])) {
                response(['error' => 'hotel_id es requerido'], 400);
            }
            
            $hotelId = $input['hotel_id'];
            $maxReviews = $input['max_reviews'] ?? 50;
            $timeout = $input['timeout'] ?? 300;
            
            $result = $bookingExtractor->extractBookingReviewsSync($hotelId, $maxReviews, $timeout);
            response($result);
            break;
            
        case 'DELETE':
            if (isset($_GET['job_id'])) {
                $result = $bookingExtractor->deleteJob($_GET['job_id']);
                response($result);
            } else {
                response(['error' => 'job_id requerido'], 400);
            }
            break;
            
        default:
            response(['error' => 'Método no permitido'], 405);
    }
    
} catch (Exception $e) {
    response([
        'error' => $e->getMessage(),
        'platform' => 'booking'
    ], 500);
}
?>