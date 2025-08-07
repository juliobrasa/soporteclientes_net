<?php
/**
 * Procesador de datos del Hotel Review Aggregator de Apify
 */

require_once 'admin-config.php';
require_once 'apify-config.php';

class ApifyDataProcessor {
    private $pdo;
    private $sentimentAnalyzer;
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->sentimentAnalyzer = new SentimentAnalyzer();
    }
    
    /**
     * Procesar resultados de una ejecución de Apify
     */
    public function processApifyResults($runId, $hotelId) {
        try {
            $apifyClient = new ApifyClient();
            $results = $apifyClient->getRunResults($runId);
            
            if (!$results || empty($results)) {
                throw new Exception("No se encontraron resultados para el run {$runId}");
            }
            
            $totalProcessed = 0;
            $errors = [];
            
            foreach ($results as $hotelData) {
                try {
                    $processed = $this->processHotelData($hotelData, $hotelId, $runId);
                    $totalProcessed += $processed;
                } catch (Exception $e) {
                    $errors[] = "Error procesando hotel: " . $e->getMessage();
                    error_log("Error procesando datos de hotel: " . $e->getMessage());
                }
            }
            
            // Actualizar estado del run
            $this->updateExtractionRunStatus($runId, 'succeeded', $totalProcessed);
            
            // Actualizar métricas del hotel
            $this->updateHotelMetrics($hotelId);
            
            // Generar alertas si es necesario
            $this->checkAndGenerateAlerts($hotelId);
            
            return [
                'success' => true,
                'total_processed' => $totalProcessed,
                'errors' => $errors
            ];
            
        } catch (Exception $e) {
            $this->updateExtractionRunStatus($runId, 'failed', 0, $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Procesar datos de un hotel específico
     */
    private function processHotelData($hotelData, $hotelId, $runId) {
        $processed = 0;
        
        // Actualizar información del hotel si está disponible
        if (isset($hotelData['hotel'])) {
            $this->updateHotelInfo($hotelId, $hotelData['hotel']);
        }
        
        // Procesar reseñas
        if (isset($hotelData['reviews']) && is_array($hotelData['reviews'])) {
            foreach ($hotelData['reviews'] as $reviewData) {
                try {
                    if ($this->processReview($reviewData, $hotelId, $runId)) {
                        $processed++;
                    }
                } catch (Exception $e) {
                    error_log("Error procesando reseña: " . $e->getMessage());
                }
            }
        }
        
        return $processed;
    }
    
    /**
     * Actualizar información del hotel
     */
    private function updateHotelInfo($hotelId, $hotelInfo) {
        $sql = "UPDATE hoteles SET ";
        $params = [];
        $updates = [];
        
        if (isset($hotelInfo['googlePlaceId'])) {
            $updates[] = "google_place_id = ?";
            $params[] = $hotelInfo['googlePlaceId'];
        }
        
        if (isset($hotelInfo['coordinates']['lat']) && isset($hotelInfo['coordinates']['lng'])) {
            $updates[] = "coordinates_lat = ?, coordinates_lng = ?";
            $params[] = $hotelInfo['coordinates']['lat'];
            $params[] = $hotelInfo['coordinates']['lng'];
        }
        
        if (!empty($updates)) {
            $sql .= implode(', ', $updates) . " WHERE id = ?";
            $params[] = $hotelId;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
        }
    }
    
    /**
     * Procesar una reseña individual
     */
    private function processReview($reviewData, $hotelId, $runId) {
        // Verificar si la reseña ya existe
        if ($this->reviewExists($reviewData, $hotelId)) {
            return false; // Ya existe, no procesar
        }
        
        // Normalizar rating según la escala de la plataforma
        $normalizedRating = $this->normalizeRating(
            $reviewData['rating'] ?? 0,
            $reviewData['platform'] ?? 'unknown'
        );
        
        // Detectar idioma
        $language = $this->detectLanguage($reviewData['reviewText'] ?? '');
        
        // Insertar reseña
        $reviewId = $this->insertReview([
            'hotel_id' => $hotelId,
            'platform' => $reviewData['platform'] ?? 'unknown',
            'platform_review_id' => $reviewData['id'] ?? null,
            'reviewer_name' => $reviewData['reviewerName'] ?? 'Anónimo',
            'rating' => $reviewData['rating'] ?? 0,
            'original_rating' => $reviewData['rating'] ?? 0,
            'normalized_rating' => $normalizedRating,
            'review_text' => $reviewData['reviewText'] ?? '',
            'review_date' => $this->parseReviewDate($reviewData['reviewDate'] ?? null),
            'helpful_votes' => $reviewData['helpfulVotes'] ?? 0,
            'response_from_owner' => $reviewData['ownerResponse'] ?? null,
            'reviewer_info' => json_encode($reviewData['reviewerInfo'] ?? []),
            'review_language' => $language,
            'extraction_run_id' => $runId,
            'scraped_at' => date('Y-m-d H:i:s')
        ]);
        
        // Análisis de sentimientos
        if ($reviewId && !empty($reviewData['reviewText'])) {
            $this->analyzeReviewSentiment($reviewId, $reviewData['reviewText'], $language);
        }
        
        return $reviewId !== false;
    }
    
    /**
     * Verificar si una reseña ya existe
     */
    private function reviewExists($reviewData, $hotelId) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM reviews 
            WHERE hotel_id = ? 
            AND platform = ? 
            AND (
                platform_review_id = ? 
                OR (review_text = ? AND reviewer_name = ?)
            )
            LIMIT 1
        ");
        
        $stmt->execute([
            $hotelId,
            $reviewData['platform'] ?? 'unknown',
            $reviewData['id'] ?? '',
            $reviewData['reviewText'] ?? '',
            $reviewData['reviewerName'] ?? 'Anónimo'
        ]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Normalizar rating a escala 1-5
     */
    private function normalizeRating($rating, $platform) {
        $scales = [
            'booking' => 10,
            'tripadvisor' => 5,
            'expedia' => 5,
            'hotels' => 5,
            'airbnb' => 5,
            'yelp' => 5,
            'google' => 5
        ];
        
        $originalScale = $scales[$platform] ?? 5;
        
        // Convertir a escala 1-5
        return round(($rating / $originalScale) * 5, 2);
    }
    
    /**
     * Detectar idioma del texto (básico)
     */
    private function detectLanguage($text) {
        // Detección básica por palabras comunes
        $spanishWords = ['el', 'la', 'de', 'que', 'y', 'es', 'en', 'un', 'hotel', 'muy', 'pero'];
        $englishWords = ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'hotel', 'very'];
        
        $text = strtolower($text);
        $spanishCount = 0;
        $englishCount = 0;
        
        foreach ($spanishWords as $word) {
            $spanishCount += substr_count($text, ' ' . $word . ' ');
        }
        
        foreach ($englishWords as $word) {
            $englishCount += substr_count($text, ' ' . $word . ' ');
        }
        
        return $spanishCount > $englishCount ? 'es' : 'en';
    }
    
    /**
     * Insertar reseña en la base de datos
     */
    private function insertReview($reviewData) {
        $sql = "INSERT INTO reviews (
            hotel_id, platform, platform_review_id, reviewer_name, rating, 
            original_rating, normalized_rating, review_text, review_date,
            helpful_votes, response_from_owner, reviewer_info, review_language,
            extraction_run_id, scraped_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        
        $success = $stmt->execute([
            $reviewData['hotel_id'],
            $reviewData['platform'],
            $reviewData['platform_review_id'],
            $reviewData['reviewer_name'],
            $reviewData['rating'],
            $reviewData['original_rating'],
            $reviewData['normalized_rating'],
            $reviewData['review_text'],
            $reviewData['review_date'],
            $reviewData['helpful_votes'],
            $reviewData['response_from_owner'],
            $reviewData['reviewer_info'],
            $reviewData['review_language'],
            $reviewData['extraction_run_id'],
            $reviewData['scraped_at']
        ]);
        
        return $success ? $this->pdo->lastInsertId() : false;
    }
    
    /**
     * Analizar sentimiento de una reseña
     */
    private function analyzeReviewSentiment($reviewId, $reviewText, $language) {
        $analysis = $this->sentimentAnalyzer->analyzeSentiment($reviewText);
        $topics = $this->sentimentAnalyzer->extractTopics($reviewText);
        
        $sql = "INSERT INTO review_analysis (
            review_id, sentiment, sentiment_score, confidence, topics, language
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            $reviewId,
            $analysis['sentiment'],
            $analysis['score'],
            $analysis['confidence'],
            json_encode($topics),
            $language
        ]);
    }
    
    /**
     * Parsear fecha de reseña
     */
    private function parseReviewDate($dateString) {
        if (!$dateString) return null;
        
        // Intentar varios formatos
        $formats = ['Y-m-d', 'Y-m-d H:i:s', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
        
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $dateString);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        // Intentar strtotime como último recurso
        $timestamp = strtotime($dateString);
        return $timestamp !== false ? date('Y-m-d', $timestamp) : null;
    }
    
    /**
     * Actualizar estado de ejecución
     */
    private function updateExtractionRunStatus($runId, $status, $totalExtracted = 0, $errorMessage = null) {
        $sql = "UPDATE apify_extraction_runs SET 
                status = ?, 
                total_reviews_extracted = ?, 
                finished_at = NOW()";
        
        $params = [$status, $totalExtracted];
        
        if ($errorMessage) {
            $sql .= ", error_message = ?";
            $params[] = $errorMessage;
        }
        
        $sql .= " WHERE apify_run_id = ?";
        $params[] = $runId;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    /**
     * Actualizar métricas del hotel
     */
    private function updateHotelMetrics($hotelId) {
        // Usar procedimiento almacenado si existe
        try {
            $stmt = $this->pdo->prepare("CALL UpdateHotelMetrics(?)");
            $stmt->execute([$hotelId]);
        } catch (Exception $e) {
            // Si el procedimiento no existe, actualizar manualmente
            $this->updateHotelMetricsManual($hotelId);
        }
    }
    
    /**
     * Actualizar métricas manualmente
     */
    private function updateHotelMetricsManual($hotelId) {
        // Actualizar conteo total y rating promedio
        $sql = "UPDATE hoteles SET 
                total_reviews_count = (SELECT COUNT(*) FROM reviews WHERE hotel_id = ?),
                average_rating_overall = (SELECT AVG(normalized_rating) FROM reviews WHERE hotel_id = ?),
                last_extraction_date = NOW()
                WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId, $hotelId, $hotelId]);
    }
    
    /**
     * Generar alertas basadas en las métricas
     */
    private function checkAndGenerateAlerts($hotelId) {
        // Verificar caída significativa en rating
        $this->checkRatingDrop($hotelId);
        
        // Verificar spike de reseñas negativas
        $this->checkNegativeSpike($hotelId);
        
        // Verificar bajo volumen de reseñas
        $this->checkLowVolume($hotelId);
    }
    
    /**
     * Verificar caída en rating
     */
    private function checkRatingDrop($hotelId) {
        $sql = "SELECT 
                    h.average_rating_overall,
                    AVG(r.normalized_rating) as recent_avg
                FROM hoteles h
                LEFT JOIN reviews r ON h.id = r.hotel_id 
                WHERE h.id = ? AND r.scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY h.id, h.average_rating_overall";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId]);
        $result = $stmt->fetch();
        
        if ($result && $result['recent_avg'] < ($result['average_rating_overall'] - 0.5)) {
            $this->createAlert($hotelId, 'rating_drop', 'medium', 
                "Rating promedio ha caído significativamente en los últimos 30 días",
                $result['average_rating_overall'], $result['recent_avg']
            );
        }
    }
    
    /**
     * Verificar spike de reseñas negativas
     */
    private function checkNegativeSpike($hotelId) {
        $sql = "SELECT 
                    COUNT(*) as recent_negative
                FROM reviews r
                JOIN review_analysis ra ON r.id = ra.review_id
                WHERE r.hotel_id = ? 
                AND ra.sentiment = 'negative'
                AND r.scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId]);
        $result = $stmt->fetch();
        
        if ($result && $result['recent_negative'] > 10) {
            $this->createAlert($hotelId, 'negative_spike', 'high',
                "Spike de reseñas negativas detectado en los últimos 7 días",
                10, $result['recent_negative']
            );
        }
    }
    
    /**
     * Verificar bajo volumen de reseñas
     */
    private function checkLowVolume($hotelId) {
        $sql = "SELECT COUNT(*) as recent_reviews
                FROM reviews 
                WHERE hotel_id = ? 
                AND scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$hotelId]);
        $result = $stmt->fetch();
        
        if ($result && $result['recent_reviews'] < 5) {
            $this->createAlert($hotelId, 'low_volume', 'low',
                "Bajo volumen de reseñas en los últimos 30 días",
                5, $result['recent_reviews']
            );
        }
    }
    
    /**
     * Crear alerta
     */
    private function createAlert($hotelId, $type, $severity, $message, $threshold, $current) {
        // Verificar si ya existe una alerta similar no resuelta
        $stmt = $this->pdo->prepare("
            SELECT id FROM review_alerts 
            WHERE hotel_id = ? AND alert_type = ? AND is_resolved = 0
            AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ");
        $stmt->execute([$hotelId, $type]);
        
        if ($stmt->fetch()) {
            return; // Ya existe una alerta reciente del mismo tipo
        }
        
        // Crear nueva alerta
        $stmt = $this->pdo->prepare("
            INSERT INTO review_alerts 
            (hotel_id, alert_type, severity, message, threshold_value, current_value)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$hotelId, $type, $severity, $message, $threshold, $current]);
    }
}
?>