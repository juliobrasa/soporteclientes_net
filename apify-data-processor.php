<?php
/**
 * Procesador de Datos Apify - Versi�n Completa y Corregida
 * Maneja extracci�n, procesamiento y almacenamiento de reviews
 */

require_once 'env-loader.php';
require_once 'apify-config.php';

class ApifyDataProcessor 
{
    private $pdo;
    private $apiToken;
    private $logFile;
    private $config;
    
    public function __construct() 
    {
        $this->pdo = EnvironmentLoader::createDatabaseConnection();
        $this->apiToken = EnvironmentLoader::get('APIFY_API_TOKEN');
        $this->logFile = __DIR__ . '/storage/logs/apify-processor.log';
        $this->config = ApifyConfig::class;
        
        $this->ensureLogDirectory();
        
        if (empty($this->apiToken)) {
            throw new Exception("APIFY_API_TOKEN no configurado en variables de entorno");
        }
    }
    
    private function ensureLogDirectory() 
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
        
        if ($level === 'ERROR') {
            error_log($logEntry);
        }
    }
    
    /**
     * Procesar extracci�n completa para un hotel
     */
    public function processHotelExtraction($hotelId, $platforms = null) 
    {
        try {
            $this->log("=� Iniciando extracci�n para hotel ID: $hotelId");
            
            // Obtener datos del hotel
            $hotel = $this->getHotelData($hotelId);
            if (!$hotel) {
                throw new Exception("Hotel no encontrado: $hotelId");
            }
            
            // Determinar plataformas a extraer
            $platforms = $platforms ?? ['booking', 'googlemaps', 'tripadvisor'];
            
            // Crear job de extracci�n
            $jobId = $this->createExtractionJob($hotelId, $platforms);
            
            // Configurar input para Apify
            $input = $this->buildExtractionInput($hotel, $platforms);
            
            // Ejecutar extracci�n en Apify
            $runId = $this->executeApifyRun($input);
            
            // Registrar run en base de datos
            $this->recordExtractionRun($jobId, $runId, $input);
            
            // Monitorear y procesar resultados
            $results = $this->monitorAndProcessRun($runId, $jobId);
            
            $this->log(" Extracci�n completada para hotel $hotelId. Resultados: " . json_encode($results));
            
            return [
                'success' => true,
                'job_id' => $jobId,
                'run_id' => $runId,
                'results' => $results
            ];
            
        } catch (Exception $e) {
            $this->log("L Error en extracci�n hotel $hotelId: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Obtener datos del hotel desde base de datos
     */
    private function getHotelData($hotelId) 
    {
        $stmt = $this->pdo->prepare("
            SELECT h.*, 
                   COUNT(r.id) as total_reviews,
                   AVG(r.rating) as avg_rating
            FROM hoteles h 
            LEFT JOIN reviews_unified r ON r.hotel_id = h.id
            WHERE h.id = ? AND h.activo = 1
            GROUP BY h.id
        ");
        
        $stmt->execute([$hotelId]);
        return $stmt->fetch();
    }
    
    /**
     * Crear job de extracci�n en base de datos
     */
    private function createExtractionJob($hotelId, $platforms) 
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO extraction_jobs (
                hotel_id, platforms, status, created_at, started_at
            ) VALUES (?, ?, 'pending', NOW(), NOW())
        ");
        
        $stmt->execute([
            $hotelId,
            json_encode($platforms)
        ]);
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Construir input para Apify usando configuraci�n corregida
     */
    private function buildExtractionInput($hotel, $platforms) 
    {
        // Usar ApifyConfig para generar input correcto
        // Usar ExtractionInputBuilder corregido
        require_once 'extraction-utils.php';
        $platformFlags = ExtractionInputBuilder::buildExtractionInput(['platforms' => $platforms]);
        
        $input = array_merge($platformFlags, [
            // Datos del hotel
            'hotelName' => $hotel['nombre_hotel'],
            'location' => $hotel['hoja_destino'],
            'city' => $this->extractCity($hotel['hoja_destino']),
            'country' => 'Spain', // Default para la mayor�a de hoteles
            
            // URLs espec�ficas si est�n disponibles
            'bookingUrl' => $hotel['url_booking'] ?? null,
            'googleMapsUrl' => $hotel['google_maps_url'] ?? null,
            'placeId' => $hotel['place_id'] ?? null,
            
            // Configuraci�n de extracci�n
            'maxReviews' => 100,
            'minRating' => 1,
            'language' => 'es',
            'sortBy' => 'newest',
            
            // Configuraci�n de performance
            'waitForSelector' => true,
            'screenshot' => false,
            'htmlSnapshot' => false,
            
            // Headers y proxies
            'useRotatingProxies' => true,
            'maxConcurrency' => 2
        ]);
        
        $this->log("=' Input generado: " . json_encode($input, JSON_PRETTY_PRINT));
        return $input;
    }
    
    /**
     * Extraer ciudad del destino
     */
    private function extractCity($destination) 
    {
        // L�gica simple para extraer ciudad
        $parts = explode(',', $destination);
        return trim($parts[0]);
    }
    
    /**
     * Ejecutar run en Apify
     */
    private function executeApifyRun($input) 
    {
        $actorId = ApifyConfig::$ACTOR_IDS['multi_otas'];
        $url = "https://api.apify.com/v2/acts/$actorId/runs";
        
        $data = [
            'input' => $input,
            'timeout' => ApifyConfig::$TIMEOUT_CONFIG['run_timeout']
        ];
        
        $response = $this->makeApifyRequest($url, 'POST', $data);
        
        if (!$response || !isset($response['data']['id'])) {
            throw new Exception("Error iniciando run en Apify: " . json_encode($response));
        }
        
        $runId = $response['data']['id'];
        $this->log("� Run iniciado en Apify: $runId");
        
        return $runId;
    }
    
    /**
     * Registrar run en base de datos
     */
    private function recordExtractionRun($jobId, $runId, $input) 
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO apify_extraction_runs (
                job_id, apify_run_id, input_config, status, 
                started_at, created_at
            ) VALUES (?, ?, ?, 'running', NOW(), NOW())
        ");
        
        $stmt->execute([
            $jobId,
            $runId,
            json_encode($input)
        ]);
    }
    
    /**
     * Monitorear run y procesar resultados
     */
    private function monitorAndProcessRun($runId, $jobId) 
    {
        $maxWaitTime = ApifyConfig::$TIMEOUT_CONFIG['run_timeout'];
        $checkInterval = ApifyConfig::$TIMEOUT_CONFIG['wait_timeout'];
        $startTime = time();
        
        while ((time() - $startTime) < $maxWaitTime) {
            $status = $this->checkRunStatus($runId);
            
            $this->updateRunStatus($runId, $status);
            
            if ($status['status'] === 'SUCCEEDED') {
                $this->log(" Run completado exitosamente: $runId");
                return $this->processRunResults($runId, $jobId);
            }
            
            if (in_array($status['status'], ['FAILED', 'ABORTED', 'TIMED-OUT'])) {
                throw new Exception("Run fall� con status: " . $status['status']);
            }
            
            $this->log("� Run en progreso: {$status['status']}");
            sleep($checkInterval);
        }
        
        throw new Exception("Timeout esperando resultados del run: $runId");
    }
    
    /**
     * Verificar status del run en Apify
     */
    private function checkRunStatus($runId) 
    {
        $url = "https://api.apify.com/v2/actor-runs/$runId";
        $response = $this->makeApifyRequest($url, 'GET');
        
        if (!$response || !isset($response['data'])) {
            throw new Exception("Error obteniendo status del run: $runId");
        }
        
        return $response['data'];
    }
    
    /**
     * Actualizar status del run en base de datos
     */
    private function updateRunStatus($runId, $statusData) 
    {
        $stmt = $this->pdo->prepare("
            UPDATE apify_extraction_runs 
            SET status = ?, progress = ?, updated_at = NOW()
            WHERE apify_run_id = ?
        ");
        
        $progress = $statusData['stats']['computeUnits'] ?? 0;
        
        $stmt->execute([
            strtolower($statusData['status']),
            $progress,
            $runId
        ]);
    }
    
    /**
     * Procesar resultados del run
     */
    private function processRunResults($runId, $jobId) 
    {
        $this->log("=� Procesando resultados del run: $runId");
        
        // Obtener dataset del run
        $datasetItems = $this->getRunDataset($runId);
        
        if (empty($datasetItems)) {
            $this->log("� No se encontraron datos en el dataset", 'WARNING');
            return ['processed' => 0, 'errors' => 0];
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($datasetItems as $item) {
            try {
                $this->processReviewItem($item, $jobId);
                $processed++;
            } catch (Exception $e) {
                $this->log("L Error procesando item: " . $e->getMessage(), 'ERROR');
                $errors++;
            }
        }
        
        // Actualizar job como completado
        $this->completeExtractionJob($jobId, $processed, $errors);
        
        return [
            'processed' => $processed,
            'errors' => $errors,
            'total' => count($datasetItems)
        ];
    }
    
    /**
     * Obtener dataset del run
     */
    private function getRunDataset($runId) 
    {
        $url = "https://api.apify.com/v2/actor-runs/$runId/dataset/items";
        $response = $this->makeApifyRequest($url, 'GET');
        
        return $response ?? [];
    }
    
    /**
     * Procesar item individual de review
     */
    private function processReviewItem($item, $jobId) 
    {
        // Obtener hotel_id del job
        $stmt = $this->pdo->prepare("SELECT hotel_id FROM extraction_jobs WHERE id = ?");
        $stmt->execute([$jobId]);
        $job = $stmt->fetch();
        
        if (!$job) {
            throw new Exception("Job no encontrado: $jobId");
        }
        
        $hotelId = $job['hotel_id'];
        
        // Usar ReviewsSchemaAdapter para inserción normalizada
        require_once __DIR__ . '/ReviewsSchemaAdapter.php';
        $adapter = new ReviewsSchemaAdapter($this->pdo);
        
        // Preparar datos normalizados
        $reviewData = [
            'unique_id' => ($item['reviewId'] ?? $item['id'] ?? uniqid('apify_')) . '_' . $hotelId,
            'hotel_id' => $hotelId,
            'user_name' => $item['authorName'] ?? $item['author_name'] ?? 'Anónimo',
            'review_text' => $item['comment'] ?? $item['review_text'] ?? null,
            'liked_text' => $item['positiveText'] ?? null,
            'disliked_text' => $item['negativeText'] ?? null,
            'rating' => $item['rating'] ?? 0,
            'source_platform' => $item['platform'] ?? 'apify',
            'property_response' => $item['response'] ?? $item['managerResponse'] ?? null,
            'review_date' => $item['date_created'] ?? $item['publishedAt'] ?? date('Y-m-d'),
            'scraped_at' => date('Y-m-d H:i:s'),
            'platform_review_id' => $item['reviewId'] ?? $item['external_id'] ?? null,
            'extraction_run_id' => $runId ?? null,
            'review_language' => $item['language'] ?? 'auto',
            'helpful_votes' => $item['helpful_votes'] ?? 0
        ];
        
        // Insertar usando adapter
        return $adapter->insertReview($reviewData);
        
    }
    
    /**
     * Completar job de extracci�n
     */
    private function completeExtractionJob($jobId, $processed, $errors) 
    {
        $status = $errors > 0 ? 'completed_with_errors' : 'completed';
        
        $stmt = $this->pdo->prepare("
            UPDATE extraction_jobs 
            SET status = ?, processed_reviews = ?, errors_count = ?, 
                completed_at = NOW(), updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $processed, $errors, $jobId]);
        
        $this->log(" Job completado: $jobId - Procesadas: $processed, Errores: $errors");
    }
    
    /**
     * Hacer petici�n a API de Apify
     */
    private function makeApifyRequest($url, $method = 'GET', $data = null) 
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url . '?token=' . $this->apiToken,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json'
            ]
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Error CURL: $error");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Error HTTP $httpCode: $response");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Obtener estad�sticas de procesamiento
     */
    public function getProcessingStats($hotelId = null) 
    {
        $whereClause = $hotelId ? "WHERE ej.hotel_id = ?" : "";
        $params = $hotelId ? [$hotelId] : [];
        
        $stmt = $this->pdo->prepare("
            SELECT 
                COUNT(*) as total_jobs,
                SUM(CASE WHEN ej.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN ej.status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN ej.status = 'running' THEN 1 ELSE 0 END) as running,
                SUM(ej.processed_reviews) as total_reviews,
                AVG(ej.processed_reviews) as avg_reviews_per_job
            FROM extraction_jobs ej
            $whereClause
        ");
        
        $stmt->execute($params);
        return $stmt->fetch();
    }
    
    /**
     * Procesar resultados de un run de Apify (llamado desde handleGetRunStatus)
     */
    public function processApifyResults($runId, $hotelId)
    {
        $this->log("Procesando resultados del run: $runId para hotel: $hotelId");
        
        try {
            // Obtener datos del dataset
            $datasetItems = $this->getRunDataset($runId);
            
            if (empty($datasetItems)) {
                $this->log("No se encontraron datos en el dataset del run: $runId", 'WARNING');
                return ['processed' => 0, 'errors' => 0, 'total' => 0];
            }
            
            $processed = 0;
            $errors = 0;
            
            // CORRECCIÓN: Detectar formato de datos (per-review vs per-hotel)
            $firstItem = $datasetItems[0];
            $isPerReview = isset($firstItem['userName']) || isset($firstItem['reviewTextParts']) || 
                          isset($firstItem['reviewDate']) || (isset($firstItem['rating']) && 
                          !isset($firstItem['hotel']) && !isset($firstItem['reviews']));
            
            $this->log("Formato detectado: " . ($isPerReview ? 'per-review (Booking)' : 'per-hotel (multi-OTA)'));
            
            if ($isPerReview) {
                // Formato Booking: cada item es una reseña individual
                foreach ($datasetItems as $reviewItem) {
                    try {
                        $normalizedReview = $this->normalizeBookingReview($reviewItem, $hotelId, $runId);
                        $this->insertReviewUnified($normalizedReview);
                        $processed++;
                    } catch (Exception $e) {
                        $this->log("Error procesando reseña Booking: " . $e->getMessage(), 'ERROR');
                        $errors++;
                    }
                }
            } else {
                // Formato multi-OTA: cada item contiene {hotel: {...}, reviews: [...]}
                foreach ($datasetItems as $item) {
                    try {
                        if (isset($item['reviews']) && is_array($item['reviews'])) {
                            foreach ($item['reviews'] as $review) {
                                $normalizedReview = $this->normalizeMultiOTAReview($review, $hotelId, $runId);
                                $this->insertReviewUnified($normalizedReview);
                                $processed++;
                            }
                        }
                    } catch (Exception $e) {
                        $this->log("Error procesando item multi-OTA: " . $e->getMessage(), 'ERROR');
                        $errors++;
                    }
                }
            }
            
            $this->log("Procesamiento completado - Procesadas: $processed, Errores: $errors");
            
            return [
                'processed' => $processed,
                'errors' => $errors,
                'total' => count($datasetItems)
            ];
            
        } catch (Exception $e) {
            $this->log("Error en processApifyResults: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Normalizar reseña del formato Booking (per-review)
     */
    private function normalizeBookingReview($reviewItem, $hotelId, $runId)
    {
        return [
            'unique_id' => ($reviewItem['id'] ?? uniqid('booking_')) . '_' . $hotelId,
            'hotel_id' => $hotelId,
            'user_name' => $reviewItem['userName'] ?? 'Anónimo',
            'review_text' => $reviewItem['reviewTextParts']['Liked'] ?? ($reviewItem['reviewText'] ?? ''),
            'liked_text' => $reviewItem['reviewTextParts']['Liked'] ?? '',
            'disliked_text' => $reviewItem['reviewTextParts']['Disliked'] ?? '',
            'source_platform' => 'booking',
            'rating' => $this->normalizeRating($reviewItem['rating'] ?? 0, 'booking'),
            'original_rating' => $reviewItem['rating'] ?? 0,
            'review_date' => $reviewItem['reviewDate'] ?? date('Y-m-d'),
            'review_title' => $reviewItem['reviewTitle'] ?? null,
            'property_response' => $reviewItem['ownerResponse'] ?? null,
            'platform_review_id' => $reviewItem['id'] ?? null,
            'extraction_run_id' => $runId,
            'helpful_votes' => $reviewItem['helpfulVotes'] ?? 0,
            'reviewer_location' => $reviewItem['userLocation'] ?? null,
            'stay_date' => $reviewItem['stayDate'] ?? null,
            'room_type' => $reviewItem['roomInfo'] ?? null,
            'number_of_nights' => $reviewItem['stayLength'] ?? null,
            'review_language' => $reviewItem['language'] ?? 'auto',
            'scraped_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Normalizar reseña del formato multi-OTA
     */
    private function normalizeMultiOTAReview($review, $hotelId, $runId)
    {
        return [
            'unique_id' => ($review['id'] ?? uniqid('multiota_')) . '_' . $hotelId,
            'hotel_id' => $hotelId,
            'user_name' => $review['author_name'] ?? $review['reviewer_name'] ?? 'Anónimo',
            'review_text' => $review['review_text'] ?? $review['text'] ?? '',
            'liked_text' => $review['liked_text'] ?? $review['positive_text'] ?? '',
            'disliked_text' => $review['disliked_text'] ?? $review['negative_text'] ?? '',
            'source_platform' => $review['platform'] ?? 'unknown',
            'rating' => $this->normalizeRating($review['rating'] ?? 0, $review['platform'] ?? 'generic'),
            'original_rating' => $review['original_rating'] ?? $review['rating'] ?? 0,
            'review_date' => $review['date_created'] ?? $review['review_date'] ?? date('Y-m-d'),
            'review_title' => $review['title'] ?? null,
            'property_response' => $review['response_from_owner'] ?? $review['management_response'] ?? null,
            'platform_review_id' => $review['external_id'] ?? $review['review_id'] ?? null,
            'extraction_run_id' => $runId,
            'helpful_votes' => $review['helpful_votes'] ?? 0,
            'review_language' => $review['language'] ?? 'auto',
            'scraped_at' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Normalizar rating según la plataforma
     */
    private function normalizeRating($rating, $platform)
    {
        $rating = floatval($rating);
        
        switch (strtolower($platform)) {
            case 'booking':
                // Booking usa escala 1-10, normalizar a 1-5
                return round(($rating / 10) * 5, 1);
            case 'tripadvisor':
            case 'google':
            default:
                // Otras plataformas suelen usar 1-5
                return round($rating, 1);
        }
    }
    
    /**
     * Insertar reseña en esquema unificado
     */
    private function insertReviewUnified($reviewData)
    {
        $columns = array_keys($reviewData);
        $placeholders = ':' . implode(', :', $columns);
        $columnsList = implode(', ', $columns);
        
        $sql = "INSERT INTO reviews ({$columnsList}) VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                review_text = VALUES(review_text),
                liked_text = VALUES(liked_text),
                disliked_text = VALUES(disliked_text),
                updated_at = NOW()";
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($reviewData);
    }
}

// Funci�n helper para compatibilidad
function processHotelReviews($hotelId, $platforms = null) {
    $processor = new ApifyDataProcessor();
    return $processor->processHotelExtraction($hotelId, $platforms);
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'help';
    $hotelId = $argv[2] ?? null;
    
    try {
        $processor = new ApifyDataProcessor();
        
        switch ($action) {
            case 'process':
                if (!$hotelId) {
                    echo "Uso: php apify-data-processor.php process <hotel_id>\n";
                    exit(1);
                }
                
                echo "=� Procesando extracci�n para hotel ID: $hotelId\n";
                $result = $processor->processHotelExtraction($hotelId);
                echo " Completado: " . json_encode($result, JSON_PRETTY_PRINT) . "\n";
                break;
                
            case 'stats':
                $stats = $processor->getProcessingStats($hotelId);
                echo "=� Estad�sticas: " . json_encode($stats, JSON_PRETTY_PRINT) . "\n";
                break;
                
            default:
                echo "Comandos disponibles:\n";
                echo "  process <hotel_id> - Procesar extracci�n para un hotel\n";
                echo "  stats [hotel_id]   - Mostrar estad�sticas de procesamiento\n";
        }
        
    } catch (Exception $e) {
        echo "L Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}

/**
 * Cliente Apify corregido para manejar tanto Booking como Multi-OTA
 */
class ApifyClient 
{
    private $apiToken;
    private $baseUrl = 'https://api.apify.com/v2';
    
    // CORRECCIÓN: Actor ID correcto para Booking (reviews)
    private $bookingActorId = 'voyager/booking-reviews-scraper'; // PbMHke3jW25J6hSOA
    private $multiOtaActorId = 'dSCLg0C3YEZ83HzYX'; // Actor multi-OTA
    
    public function __construct() {
        $this->apiToken = EnvironmentLoader::get('APIFY_API_TOKEN');
        if (!$this->apiToken) {
            throw new Exception("APIFY_API_TOKEN no configurado");
        }
    }
    
    /**
     * Obtener URL de Booking para un hotel desde la base de datos
     */
    private function getBookingUrlForHotel($hotelId) {
        if (!$hotelId) {
            return null;
        }
        
        try {
            $pdo = EnvironmentLoader::createDatabaseConnection();
            $stmt = $pdo->prepare("SELECT url_booking FROM hoteles WHERE id = ?");
            $stmt->execute([$hotelId]);
            $hotel = $stmt->fetch();
            
            return $hotel ? $hotel['url_booking'] : null;
        } catch (Exception $e) {
            error_log("Error obteniendo URL de Booking: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * CORRECCIÓN: runBookingExtractionSync unificado con voyager/booking-reviews-scraper
     */
    public function runBookingExtractionSync($config, $timeout = 300) {
        try {
            $bookingUrl = $this->getBookingUrlForHotel($config['hotel_id'] ?? null);
            if (!$bookingUrl) {
                throw new Exception("No se encontró URL de Booking para el hotel");
            }

            $input = [
                'startUrls' => [
                    ['url' => $bookingUrl]
                ],
                'maxReviewsPerHotel' => $config['maxReviews'] ?? 50,
                'proxyConfiguration' => [
                    'useApifyProxy' => true,
                    'apifyProxyGroups' => ['RESIDENTIAL']
                ]
            ];

            $queryParams = http_build_query([
                'timeout' => $timeout,
                'memory' => 2048,
                'format' => 'json'
            ]);

            $response = $this->makeRequest('POST', "/acts/{$this->bookingActorId}/run-sync-get-dataset-items?{$queryParams}", $input);

            return [
                'success' => true,
                'data' => $response ?? [],
                'execution_time' => 0,
                'reviews_count' => is_array($response) ? count($response) : 0
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * CORRECCIÓN: startBookingExtractionAsync unificado con voyager/booking-reviews-scraper
     */
    public function startBookingExtractionAsync($config) {
        $bookingUrl = $this->getBookingUrlForHotel($config['hotel_id'] ?? ($config['hotelId'] ?? null));
        if (!$bookingUrl) {
            throw new Exception("No se encontró URL de Booking para el hotel");
        }

        $input = [
            'startUrls' => [
                ['url' => $bookingUrl]
            ],
            'maxReviewsPerHotel' => $config['maxReviews'] ?? 50,
            'proxyConfiguration' => [
                'useApifyProxy' => true,
                'apifyProxyGroups' => ['RESIDENTIAL']
            ]
        ];

        return $this->makeRequest('POST', "/acts/{$this->bookingActorId}/runs", [
            'input' => $input
        ]);
    }
    
    /**
     * Extracción síncrona multi-OTA
     */
    public function runHotelExtractionSync($config, $timeout = 300) {
        try {
            $input = $this->buildExtractionInput($config);
            
            $queryParams = http_build_query([
                'timeout' => $timeout,
                'memory' => 4096
            ]);
            
            $response = $this->makeRequest(
                'POST',
                "/acts/{$this->multiOtaActorId}/run-sync-get-dataset-items?{$queryParams}",
                $input
            );
            
            if ($response && is_array($response)) {
                return [
                    'success' => true,
                    'data' => $response,
                    'stats' => ['total_reviews' => count($response)]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'No se obtuvieron resultados del actor multi-OTA',
                    'data' => []
                ];
            }
            
        } catch (Exception $e) {
            error_log("Error en runHotelExtractionSync: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error en extracción multi-OTA: ' . $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Iniciar extracción asíncrona multi-OTA
     */
    public function startHotelExtraction($config) {
        try {
            $input = $this->buildExtractionInput($config);
            $response = $this->makeRequest('POST', "/acts/{$this->multiOtaActorId}/runs", $input);
            
            return [
                'success' => true,
                'data' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * CORRECCIÓN: buildExtractionInput corregido para respetar plataformas seleccionadas
     */
    private function buildExtractionInput($config) {
        $defaultConfig = [
            'maxReviews' => 1000,
            'reviewsFromDate' => date('Y-01-01'),
            'scrapeReviewPictures' => false,
            'scrapeReviewResponses' => true,

            // Todos a false por defecto
            'enableGoogleMaps' => false,
            'enableTripadvisor' => false,
            'enableBooking' => false,
            'enableExpedia' => false,
            'enableHotelsCom' => false,
            'enableYelp' => false,
            'enableAirbnb' => false,
        ];

        if (!empty($config['platforms']) && is_array($config['platforms'])) {
            foreach ($config['platforms'] as $p) {
                $p = strtolower($p);
                if ($p === 'google') $defaultConfig['enableGoogleMaps'] = true;
                if ($p === 'tripadvisor') $defaultConfig['enableTripadvisor'] = true;
                if ($p === 'booking') $defaultConfig['enableBooking'] = true;
                if ($p === 'expedia') $defaultConfig['enableExpedia'] = true;
                if ($p === 'hotels' || $p === 'hotelscom' || $p === 'hotels.com') $defaultConfig['enableHotelsCom'] = true;
                if ($p === 'yelp') $defaultConfig['enableYelp'] = true;
                if ($p === 'airbnb') $defaultConfig['enableAirbnb'] = true;
            }
        }

        if (isset($config['hotelId'])) {
            $defaultConfig['startIds'] = [$config['hotelId']];
        }

        if (isset($config['startUrls'])) {
            $defaultConfig['startUrls'] = $config['startUrls'];
        }

        return array_merge($defaultConfig, $config);
    }
    
    /**
     * Obtener estado de una ejecución
     */
    public function getRunStatus($runId) {
        try {
            $response = $this->makeRequest('GET', "/actor-runs/{$runId}");
            return [
                'success' => true,
                'data' => $response
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => null
            ];
        }
    }
    
    /**
     * Estimar costo de extracción
     */
    public function estimateCost($totalReviews) {
        // Estimación aproximada: $0.001 por review
        return $totalReviews * 0.001;
    }
    
    /**
     * Realizar petición HTTP a Apify API
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60
        ]);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: $error");
        }
        
        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error']['message'] ?? "HTTP $httpCode";
            throw new Exception("Apify API Error: $errorMessage");
        }
        
        return json_decode($response, true);
    }
}
?>