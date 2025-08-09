<?php
/**
 * ==========================================================================
 * CRON JOB - EXTRACCIÓN AUTOMÁTICA DIARIA DE RESEÑAS
 * Sistema de extracción automática con anti-duplicados
 * ==========================================================================
 */

require_once __DIR__ . '/admin-config.php';
require_once __DIR__ . '/apify-config.php';
require_once __DIR__ . '/apify-data-processor.php';

class CronExtractor {
    private $pdo;
    private $apifyClient;
    private $processor;
    private $logFile;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->apifyClient = new ApifyClient();
        $this->processor = new ApifyDataProcessor();
        $this->logFile = __DIR__ . '/logs/cron-extractor.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Ejecutar extracción automática diaria
     */
    public function runDailyExtraction() {
        $this->log("=== INICIO EXTRACCIÓN AUTOMÁTICA DIARIA ===");
        
        try {
            // Verificar si ya se ejecutó hoy
            if ($this->wasRunToday()) {
                $this->log("Extracción ya ejecutada hoy, saltando...");
                return false;
            }
            
            // Obtener hoteles activos
            $hotels = $this->getActiveHotels();
            $this->log("Hoteles activos encontrados: " . count($hotels));
            
            if (empty($hotels)) {
                $this->log("No hay hoteles activos, terminando.");
                return false;
            }
            
            $totalExtracted = 0;
            $errors = [];
            
            foreach ($hotels as $hotel) {
                try {
                    $this->log("Procesando hotel: {$hotel['nombre_hotel']} (ID: {$hotel['id']})");
                    
                    $extracted = $this->extractHotelReviews($hotel);
                    $totalExtracted += $extracted;
                    
                    $this->log("Extraídas {$extracted} reseñas nuevas del hotel {$hotel['nombre_hotel']}");
                    
                    // Pausa entre hoteles para no sobrecargar APIs
                    sleep(5);
                    
                } catch (Exception $e) {
                    $error = "Error procesando hotel {$hotel['nombre_hotel']}: " . $e->getMessage();
                    $errors[] = $error;
                    $this->log($error, 'ERROR');
                }
            }
            
            // Registrar ejecución
            $this->recordDailyRun($totalExtracted, $errors);
            
            $this->log("=== FIN EXTRACCIÓN AUTOMÁTICA - Total: {$totalExtracted} reseñas ===");
            
            return [
                'success' => true,
                'total_extracted' => $totalExtracted,
                'hotels_processed' => count($hotels),
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->log("Error crítico en extracción automática: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Extraer reseñas de un hotel específico (últimas 48 horas)
     */
    private function extractHotelReviews($hotel) {
        $config = [
            'hotelId' => $hotel['google_place_id'],
            'hotelName' => $hotel['nombre_hotel'],
            'maxReviews' => 100, // Límite por hotel para cron diario
            'reviewPlatforms' => ['booking', 'google', 'tripadvisor'], // Plataformas principales
            'reviewLanguages' => ['en', 'es'],
            'reviewDates' => [
                'from' => date('Y-m-d', strtotime('-48 hours')), // Últimas 48 horas
                'to' => date('Y-m-d')
            ],
            'mode' => 'incremental' // Modo incremental
        ];
        
        // Ejecutar extracción
        $result = $this->apifyClient->runHotelExtractionSync($config, 180); // 3 minutos timeout
        
        if (!$result['success']) {
            throw new Exception("Fallo en extracción Apify: " . ($result['error'] ?? 'Error desconocido'));
        }
        
        // Procesar resultados evitando duplicados
        $newReviews = 0;
        
        if (isset($result['data']) && is_array($result['data'])) {
            foreach ($result['data'] as $reviewData) {
                // Verificar si la reseña ya existe
                if (!$this->reviewExists($reviewData, $hotel['id'])) {
                    if ($this->saveReview($reviewData, $hotel['id'])) {
                        $newReviews++;
                    }
                }
            }
        }
        
        return $newReviews;
    }
    
    /**
     * Verificar si una reseña ya existe en la base de datos
     */
    private function reviewExists($reviewData, $hotelId) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM reviews 
            WHERE hotel_id = ? 
            AND source_platform = ? 
            AND (
                platform_review_id = ? 
                OR (
                    review_title = ? 
                    AND user_name = ? 
                    AND DATE(review_date) = DATE(?)
                )
            )
            LIMIT 1
        ");
        
        $stmt->execute([
            $hotelId,
            $reviewData['platform'] ?? 'unknown',
            $reviewData['reviewId'] ?? '',
            $reviewData['title'] ?? '',
            $reviewData['reviewerName'] ?? '',
            $this->parseDate($reviewData['reviewDate'] ?? null)
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Guardar reseña en la base de datos
     */
    private function saveReview($reviewData, $hotelId) {
        $sql = "INSERT INTO reviews (
            hotel_id, source_platform, platform_review_id, user_name, user_location,
            review_date, traveler_type, rating, review_title, liked_text, disliked_text,
            property_response, scraped_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        
        return $stmt->execute([
            $hotelId,
            $reviewData['platform'] ?? 'unknown',
            $reviewData['reviewId'] ?? null,
            $reviewData['reviewerName'] ?? 'Anónimo',
            $reviewData['reviewerLocation'] ?? null,
            $this->parseDate($reviewData['reviewDate'] ?? null),
            $reviewData['tripType'] ?? null,
            $reviewData['rating'] ?? 0,
            $reviewData['title'] ?? '',
            $reviewData['positive'] ?? '',
            $reviewData['negative'] ?? '',
            $reviewData['ownerResponse'] ?? null
        ]);
    }
    
    /**
     * Obtener hoteles activos
     */
    private function getActiveHotels() {
        $stmt = $this->pdo->query("
            SELECT id, nombre_hotel, google_place_id 
            FROM hoteles 
            WHERE activo = 1 
            AND google_place_id IS NOT NULL 
            AND google_place_id != ''
            ORDER BY nombre_hotel
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Verificar si ya se ejecutó hoy
     */
    private function wasRunToday() {
        $stmt = $this->pdo->prepare("
            SELECT id FROM cron_extraction_log 
            WHERE DATE(execution_date) = CURDATE() 
            AND status = 'completed'
            LIMIT 1
        ");
        $stmt->execute();
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Registrar ejecución diaria
     */
    private function recordDailyRun($totalExtracted, $errors) {
        $stmt = $this->pdo->prepare("
            INSERT INTO cron_extraction_log 
            (execution_date, total_reviews_extracted, hotels_processed, errors_log, status)
            VALUES (NOW(), ?, ?, ?, 'completed')
        ");
        
        $stmt->execute([
            $totalExtracted,
            count($this->getActiveHotels()),
            json_encode($errors)
        ]);
    }
    
    /**
     * Parsear fecha
     */
    private function parseDate($dateString) {
        if (!$dateString) return null;
        
        $timestamp = strtotime($dateString);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
    
    /**
     * Registrar log
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // También mostrar en consola si se ejecuta desde CLI
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Ejecutar si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $extractor = new CronExtractor();
    $result = $extractor->runDailyExtraction();
    
    exit($result['success'] ? 0 : 1);
}
?>