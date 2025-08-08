<?php
/**
 * ==========================================================================
 * BOOKING.COM REVIEWS SCRAPER
 * Integración con voyager/booking-reviews-scraper
 * ==========================================================================
 */

class BookingScraper {
    private $apiToken;
    private $baseUrl = 'https://api.apify.com/v2';
    private $actorId = 'PbMHke3jW25J6hSOA'; // voyager/booking-reviews-scraper
    
    public function __construct($apiToken = null) {
        $this->apiToken = $apiToken ?: $_ENV['APIFY_API_TOKEN'] ?? null;
        
        if (!$this->apiToken) {
            throw new Exception("Token de Apify requerido para Booking scraper");
        }
    }
    
    /**
     * Extraer reseñas de Booking.com por URL del hotel
     */
    public function scrapeBookingReviews($hotelUrl, $maxReviews = 50, $options = []) {
        $defaultOptions = [
            'language' => 'es',
            'includeReviewText' => true,
            'includeReviewerInfo' => true,
            'sortBy' => 'date_desc' // Más recientes primero
        ];
        
        $config = array_merge($defaultOptions, $options, [
            'hotelUrl' => $hotelUrl,
            'maxReviews' => $maxReviews
        ]);
        
        try {
            return $this->runBookingExtractionSync($config);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }
    
    /**
     * Buscar hoteles en Booking por nombre y ciudad
     */
    public function searchBookingHotels($hotelName, $city = null, $country = null) {
        $searchQuery = $hotelName;
        if ($city) $searchQuery .= ", {$city}";
        if ($country) $searchQuery .= ", {$country}";
        
        $config = [
            'searchQuery' => $searchQuery,
            'maxHotels' => 10,
            'includeReviews' => false, // Solo buscar hoteles, no reseñas aún
            'language' => 'es'
        ];
        
        try {
            return $this->runBookingSearch($config);
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'hotels' => []
            ];
        }
    }
    
    /**
     * Extraer reseñas de múltiples hoteles de Booking
     */
    public function scrapeMultipleHotels($hotelUrls, $maxReviewsPerHotel = 20) {
        $allReviews = [];
        $errors = [];
        
        foreach ($hotelUrls as $hotelUrl) {
            echo "Extrayendo reseñas de: {$hotelUrl}\n";
            
            $result = $this->scrapeBookingReviews($hotelUrl, $maxReviewsPerHotel);
            
            if ($result['success']) {
                $reviews = $result['data'] ?? [];
                $allReviews = array_merge($allReviews, $reviews);
                echo "✅ Extraídas " . count($reviews) . " reseñas\n";
            } else {
                $errors[] = [
                    'hotel_url' => $hotelUrl,
                    'error' => $result['error']
                ];
                echo "❌ Error: " . $result['error'] . "\n";
            }
            
            // Pausa entre extracciones
            sleep(5);
        }
        
        return [
            'success' => empty($errors),
            'total_reviews' => count($allReviews),
            'reviews' => $allReviews,
            'errors' => $errors
        ];
    }
    
    /**
     * Ejecutar extracción síncrona de Booking
     */
    private function runBookingExtractionSync($config, $timeout = 120) {
        $input = $this->buildBookingInput($config);
        
        // Usar run-sync para obtener resultados inmediatamente
        $queryParams = http_build_query([
            'timeout' => $timeout,
            'memory' => 2048
        ]);
        
        $response = $this->makeRequest(
            'POST', 
            "/acts/{$this->actorId}/run-sync-get-dataset-items?{$queryParams}", 
            $input
        );
        
        if ($response && is_array($response)) {
            return [
                'success' => true,
                'data' => $response,
                'total_reviews' => count($response)
            ];
        } else {
            throw new Exception("No se obtuvieron resultados de Booking");
        }
    }
    
    /**
     * Ejecutar búsqueda de hoteles en Booking
     */
    private function runBookingSearch($config) {
        // Para búsqueda usaríamos un actor diferente o parámetros específicos
        // Por ahora usamos el mismo actor pero configurado para búsqueda
        $input = [
            'searchMode' => true,
            'query' => $config['searchQuery'],
            'maxResults' => $config['maxHotels'],
            'language' => $config['language']
        ];
        
        $response = $this->makeRequest('POST', "/acts/{$this->actorId}/runs", $input);
        
        // Para búsqueda necesitaríamos manejar resultados asincrónicos
        // Implementación simplificada por ahora
        return [
            'success' => true,
            'hotels' => []
        ];
    }
    
    /**
     * Construir input para actor de Booking
     */
    private function buildBookingInput($config) {
        $input = [
            'startUrls' => [],
            'maxReviews' => $config['maxReviews'] ?? 50,
            'language' => $config['language'] ?? 'es',
            'includeReviewText' => $config['includeReviewText'] ?? true,
            'includeReviewerInfo' => $config['includeReviewerInfo'] ?? true
        ];
        
        // Configurar URL del hotel
        if (isset($config['hotelUrl'])) {
            $input['startUrls'][] = [
                'url' => $config['hotelUrl']
            ];
        }
        
        return $input;
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
                'Content-Type: application/json'
            ],
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CONNECTTIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true
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
    
    /**
     * Convertir reseñas de Booking al formato unificado
     */
    public function normalizeBookingReviews($bookingReviews, $hotelId = null) {
        $normalizedReviews = [];
        
        foreach ($bookingReviews as $review) {
            $normalizedReviews[] = [
                'hotel_id' => $hotelId,
                'platform' => 'booking',
                'platform_review_id' => $review['reviewId'] ?? uniqid('booking_'),
                'title' => $review['title'] ?? '',
                'content' => $review['text'] ?? $review['reviewText'] ?? '',
                'rating' => $this->normalizeBookingRating($review['rating'] ?? null),
                'author' => $review['author'] ?? $review['reviewerName'] ?? 'Anónimo',
                'review_date' => $this->normalizeBookingDate($review['date'] ?? $review['reviewDate'] ?? null),
                'scraped_at' => date('Y-m-d H:i:s'),
                'helpful_votes' => $review['helpful'] ?? 0,
                'verified_stay' => true, // Booking normalmente son estancias verificadas
                'room_type' => $review['roomType'] ?? null,
                'stay_date' => $review['stayDate'] ?? null,
                'traveler_type' => $review['travelerType'] ?? null,
                'url' => $review['url'] ?? null,
                'language' => $review['language'] ?? 'es',
                'positive_text' => $review['positiveText'] ?? null,
                'negative_text' => $review['negativeText'] ?? null
            ];
        }
        
        return $normalizedReviews;
    }
    
    /**
     * Normalizar rating de Booking (generalmente 1-10) a escala 1-5
     */
    private function normalizeBookingRating($rating) {
        if (!$rating) return null;
        
        // Si el rating está en escala 1-10, convertir a 1-5
        if ($rating > 5) {
            return round($rating / 2, 1);
        }
        
        return $rating;
    }
    
    /**
     * Normalizar fecha de Booking
     */
    private function normalizeBookingDate($date) {
        if (!$date) return null;
        
        try {
            $dateObj = new DateTime($date);
            return $dateObj->format('Y-m-d');
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Obtener información de actor
     */
    public function getActorInfo() {
        try {
            $response = $this->makeRequest('GET', "/acts/{$this->actorId}");
            
            if ($response && isset($response['data'])) {
                $actor = $response['data'];
                return [
                    'name' => $actor['name'],
                    'title' => $actor['title'],
                    'description' => $actor['description'],
                    'total_runs' => $actor['stats']['totalRuns'] ?? 0,
                    'avg_rating' => $actor['stats']['avgRating'] ?? 0
                ];
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Estimar costo de extracción
     */
    public function estimateCost($totalReviews) {
        // Booking scraper aproximadamente $0.002 por review
        return ($totalReviews * 0.002);
    }
}

// Función helper para obtener URLs de Booking de hoteles existentes
function findBookingUrls($hotelNames) {
    // Esta función ayudaría a encontrar URLs de Booking basado en nombres
    // Por ahora retorna ejemplo
    $bookingUrls = [];
    
    foreach ($hotelNames as $name) {
        // En implementación real, buscaríamos en Booking o usaríamos Google Search
        $cleanName = strtolower(str_replace(' ', '-', $name));
        $bookingUrls[] = "https://www.booking.com/hotel/mx/{$cleanName}.html";
    }
    
    return $bookingUrls;
}

?>

<?php
/**
 * EJEMPLO DE USO DEL BOOKING SCRAPER
 */
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    
    echo "=== EJEMPLO DE USO BOOKING SCRAPER ===\n\n";
    
    try {
        // Cargar variables de entorno
        require_once __DIR__ . '/env-loader.php';
        
        $bookingScraper = new BookingScraper();
        
        // Obtener info del actor
        echo "📊 Información del actor:\n";
        $actorInfo = $bookingScraper->getActorInfo();
        if ($actorInfo) {
            echo "   - Nombre: " . $actorInfo['title'] . "\n";
            echo "   - Runs: " . number_format($actorInfo['total_runs']) . "\n";
            echo "   - Rating: " . $actorInfo['avg_rating'] . "/5\n\n";
        }
        
        // Ejemplo: Extraer reseñas de un hotel en Booking
        $hotelUrl = "https://www.booking.com/hotel/mx/hard-rock-hotel-cancun.html";
        
        echo "🏨 Extrayendo reseñas de: {$hotelUrl}\n";
        echo "📊 Máximo: 5 reseñas para prueba\n\n";
        
        $result = $bookingScraper->scrapeBookingReviews($hotelUrl, 5);
        
        if ($result['success']) {
            $reviews = $result['data'];
            echo "✅ Extraídas " . count($reviews) . " reseñas de Booking\n\n";
            
            // Mostrar muestra
            foreach (array_slice($reviews, 0, 2) as $i => $review) {
                echo "📝 Reseña " . ($i + 1) . ":\n";
                foreach ($review as $key => $value) {
                    $displayValue = is_string($value) ? substr($value, 0, 100) . "..." : $value;
                    echo "   - {$key}: {$displayValue}\n";
                }
                echo "\n";
            }
            
            // Normalizar al formato de BD
            echo "🔄 Normalizando reseñas...\n";
            $normalized = $bookingScraper->normalizeBookingReviews($reviews, 1);
            
            echo "✅ Reseñas normalizadas: " . count($normalized) . "\n";
            echo "Formato para BD:\n";
            print_r($normalized[0] ?? []);
            
        } else {
            echo "❌ Error: " . $result['error'] . "\n";
        }
        
        // Estimar costo
        echo "\n💰 Costo estimado para 100 reseñas: $" . $bookingScraper->estimateCost(100) . "\n";
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n=== FIN EJEMPLO ===\n";
}
?>