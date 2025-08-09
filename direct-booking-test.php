<?php
/**
 * Prueba real directa del BookingExtractor
 */
require_once 'admin-config.php';
require_once 'booking-extractor-class.php';

echo "=== PRUEBA REAL DIRECTA DE EXTRACCIÓN DE BOOKING ===\n\n";

class DirectBookingExtractor extends BookingExtractor {
    /**
     * Versión real de extracción (no solo simulación)
     */
    public function extractBookingReviewsReal($hotelId, $maxReviews = 5, $timeout = 300) {
        // Obtener información del hotel
        $stmt = $this->pdo->prepare("SELECT nombre_hotel, url_booking FROM hoteles WHERE id = ?");
        $stmt->execute([$hotelId]);
        $hotel = $stmt->fetch();
        
        if (!$hotel) {
            throw new Exception("Hotel no encontrado");
        }
        
        if (!$hotel['url_booking']) {
            throw new Exception("Hotel no tiene URL de Booking configurada");
        }
        
        echo "🏨 Hotel: {$hotel['nombre_hotel']}\n";
        echo "📍 URL: {$hotel['url_booking']}\n";
        echo "📊 Máximo reseñas: {$maxReviews}\n";
        echo "⏱️  Timeout: {$timeout}s\n\n";
        
        // Configuración para el actor de Booking
        $input = [
            'startUrls' => [
                ['url' => $hotel['url_booking']]
            ],
            'maxItems' => $maxReviews,
            'proxyConfiguration' => [
                'useApifyProxy' => true,
                'apifyProxyGroups' => ['RESIDENTIAL']
            ]
        ];
        
        echo "🚀 Configuración del actor:\n";
        echo "   - Actor ID: {$this->actorId}\n";
        echo "   - URL objetivo: {$hotel['url_booking']}\n";
        echo "   - Proxy: Residencial\n\n";
        
        $startTime = time();
        
        echo "📤 Ejecutando extracción síncrona en Apify...\n";
        
        // Ejecutar extracción síncrona
        $queryParams = http_build_query([
            'timeout' => $timeout,
            'memory' => 2048
        ]);
        
        try {
            $response = $this->makeRequest(
                'POST', 
                "/acts/{$this->actorId}/run-sync-get-dataset-items?{$queryParams}", 
                $input
            );
            
            $executionTime = time() - $startTime;
            
            echo "✅ Extracción completada en {$executionTime}s\n";
            echo "📊 Resultados recibidos: " . (is_array($response) ? count($response) : 0) . " items\n\n";
            
            if (!$response || !is_array($response)) {
                throw new Exception("No se obtuvieron resultados de Booking");
            }
            
            // Procesar y guardar reseñas
            echo "💾 Guardando reseñas en la base de datos...\n";
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
                'raw_data' => array_slice($response, 0, 2) // Muestra de 2 reseñas
            ];
            
        } catch (Exception $e) {
            $executionTime = time() - $startTime;
            echo "❌ Error después de {$executionTime}s: " . $e->getMessage() . "\n";
            throw $e;
        }
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
        
        echo "   Procesando " . count($bookingReviews) . " reseñas...\n";
        
        foreach ($bookingReviews as $i => $review) {
            try {
                $normalizedReview = $this->normalizeBookingReview($review);
                
                $stmt->execute([
                    $normalizedReview['unique_id'],
                    $hotelId,
                    $hotelName,
                    'Cancún, México',
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
                echo "   ✅ Reseña " . ($i + 1) . " guardada\n";
            } catch (Exception $e) {
                echo "   ❌ Error guardando reseña " . ($i + 1) . ": " . $e->getMessage() . "\n";
            }
        }
        
        echo "   💾 Total guardadas: {$savedCount}\n\n";
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
            'unique_id' => $reviewId . '_booking_' . time(),
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
     * Realizar petición HTTP a Apify
     */
    private function makeRequest($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        echo "🌐 Llamando a: " . parse_url($url, PHP_URL_PATH) . "\n";
        
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json',
                'User-Agent: Hotel-Reviews-Extractor/1.0'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 400,
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
        
        echo "📡 Respuesta HTTP: {$httpCode}\n";
        
        if ($httpCode >= 400) {
            throw new Exception("Error HTTP {$httpCode}: " . substr($response, 0, 200));
        }
        
        return json_decode($response, true);
    }
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception("Error conectando a la base de datos");
    }
    
    // Crear extractor
    $extractor = new DirectBookingExtractor($pdo);
    
    // Seleccionar hotel para prueba
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, url_booking 
        FROM hoteles 
        WHERE activo = 1 AND url_booking IS NOT NULL AND url_booking != ''
        ORDER BY id ASC
        LIMIT 1
    ");
    $hotel = $stmt->fetch();
    
    if (!$hotel) {
        throw new Exception("No hay hoteles disponibles para la prueba");
    }
    
    // Ejecutar extracción real
    $result = $extractor->extractBookingReviewsReal($hotel['id'], 5, 300);
    
    echo "=== RESULTADOS FINALES ===\n\n";
    
    if ($result['success']) {
        echo "🎉 ¡EXTRACCIÓN EXITOSA!\n\n";
        echo "📊 Estadísticas:\n";
        echo "   - Job ID: {$result['job_id']}\n";
        echo "   - Hotel: {$result['hotel_name']}\n";
        echo "   - Plataforma: {$result['platform']}\n";
        echo "   - Reseñas extraídas: {$result['reviews_extracted']}\n";
        echo "   - Reseñas guardadas: {$result['reviews_saved']}\n";
        echo "   - Tiempo total: {$result['execution_time']}s\n\n";
        
        if (!empty($result['raw_data'])) {
            echo "📝 Muestra de datos extraídos:\n";
            foreach ($result['raw_data'] as $i => $review) {
                echo "\nReseña " . ($i + 1) . ":\n";
                foreach (array_slice($review, 0, 8, true) as $key => $value) {
                    $displayValue = is_string($value) ? substr($value, 0, 80) . "..." : $value;
                    echo "   - {$key}: {$displayValue}\n";
                }
            }
        }
        
        echo "\n✅ ¡El sistema de extracción de Booking está funcionando perfectamente!\n";
        
    } else {
        echo "❌ La extracción falló\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Error durante la prueba: " . $e->getMessage() . "\n";
    echo "📍 Archivo: " . $e->getFile() . " (línea " . $e->getLine() . ")\n";
}
?>