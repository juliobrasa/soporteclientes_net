<?php
/**
 * ==========================================================================
 * SISTEMA MULTI-PLATAFORMA DE EXTRACCIÓN DE RESEÑAS
 * Combina múltiples scrapers especializados
 * ==========================================================================
 */

require_once __DIR__ . '/booking-scraper.php';
require_once __DIR__ . '/env-loader.php';

class MultiPlatformScraper {
    private $apiToken;
    private $bookingScraper;
    private $availableScrapers;
    
    public function __construct($apiToken = null) {
        $this->apiToken = $apiToken ?: $_ENV['APIFY_API_TOKEN'] ?? null;
        
        if (!$this->apiToken) {
            throw new Exception("Token de Apify requerido");
        }
        
        // Inicializar scrapers especializados
        $this->bookingScraper = new BookingScraper($this->apiToken);
        
        // Configurar scrapers disponibles
        $this->availableScrapers = [
            'booking' => [
                'scraper' => $this->bookingScraper,
                'actor_id' => 'PbMHke3jW25J6hSOA',
                'name' => 'Booking.com Reviews',
                'cost_per_review' => 0.002,
                'max_reviews_per_run' => 500
            ],
            'google_maps' => [
                'actor_id' => 'nwua9Gu5YrADL7ZDj',
                'name' => 'Google Maps Reviews',
                'cost_per_review' => 0.001,
                'max_reviews_per_run' => 100
            ],
            'tripadvisor' => [
                'actor_id' => 'Hvp4YfFGyLM635Q2F',
                'name' => 'TripAdvisor Reviews',
                'cost_per_review' => 0.0015,
                'max_reviews_per_run' => 200
            ],
            'expedia' => [
                'actor_id' => '4zyibEJ79jE7VXIpA',
                'name' => 'Expedia/Hotels.com Reviews',
                'cost_per_review' => 0.002,
                'max_reviews_per_run' => 300
            ]
        ];
    }
    
    /**
     * Extraer reseñas de múltiples plataformas para un hotel
     */
    public function extractAllPlatforms($hotelData, $options = []) {
        $defaultOptions = [
            'max_reviews_per_platform' => 50,
            'platforms' => ['booking', 'google_maps', 'tripadvisor'],
            'language' => 'es',
            'date_from' => date('Y-m-d', strtotime('-1 year'))
        ];
        
        $config = array_merge($defaultOptions, $options);
        $results = [
            'hotel_id' => $hotelData['id'],
            'hotel_name' => $hotelData['nombre_hotel'],
            'platforms' => [],
            'total_reviews' => 0,
            'all_reviews' => [],
            'errors' => [],
            'execution_time' => 0,
            'estimated_cost' => 0
        ];
        
        $startTime = time();
        
        foreach ($config['platforms'] as $platform) {
            echo "🔍 Extrayendo de {$platform}...\n";
            
            try {
                $platformResult = $this->extractFromPlatform(
                    $platform,
                    $hotelData,
                    $config['max_reviews_per_platform'],
                    $config
                );
                
                $results['platforms'][$platform] = $platformResult;
                $results['total_reviews'] += $platformResult['count'];
                $results['all_reviews'] = array_merge($results['all_reviews'], $platformResult['reviews']);
                $results['estimated_cost'] += $platformResult['estimated_cost'];
                
                echo "   ✅ {$platform}: {$platformResult['count']} reseñas\n";
                
                // Pausa entre plataformas
                sleep(5);
                
            } catch (Exception $e) {
                $results['errors'][$platform] = $e->getMessage();
                echo "   ❌ {$platform}: " . $e->getMessage() . "\n";
            }
        }
        
        $results['execution_time'] = time() - $startTime;
        
        return $results;
    }
    
    /**
     * Extraer reseñas de una plataforma específica
     */
    private function extractFromPlatform($platform, $hotelData, $maxReviews, $config) {
        switch ($platform) {
            case 'booking':
                return $this->extractFromBooking($hotelData, $maxReviews, $config);
                
            case 'google_maps':
                return $this->extractFromGoogleMaps($hotelData, $maxReviews, $config);
                
            case 'tripadvisor':
                return $this->extractFromTripAdvisor($hotelData, $maxReviews, $config);
                
            case 'expedia':
                return $this->extractFromExpedia($hotelData, $maxReviews, $config);
                
            default:
                throw new Exception("Plataforma {$platform} no soportada");
        }
    }
    
    /**
     * Extraer de Booking.com
     */
    private function extractFromBooking($hotelData, $maxReviews, $config) {
        $hotelUrl = $this->findBookingUrl($hotelData['nombre_hotel']);
        
        if (!$hotelUrl) {
            throw new Exception("No se pudo encontrar URL de Booking para el hotel");
        }
        
        $options = [
            'language' => $config['language'],
            'includeReviewText' => true,
            'includeReviewerInfo' => true
        ];
        
        $result = $this->bookingScraper->scrapeBookingReviews($hotelUrl, $maxReviews, $options);
        
        if (!$result['success']) {
            throw new Exception($result['error']);
        }
        
        $reviews = $this->bookingScraper->normalizeBookingReviews($result['data'], $hotelData['id']);
        
        return [
            'platform' => 'booking',
            'count' => count($reviews),
            'reviews' => $reviews,
            'source_url' => $hotelUrl,
            'estimated_cost' => $this->availableScrapers['booking']['cost_per_review'] * count($reviews)
        ];
    }
    
    /**
     * Extraer de Google Maps (implementación futura)
     */
    private function extractFromGoogleMaps($hotelData, $maxReviews, $config) {
        // Implementar con compass/crawler-google-places
        $placeId = $hotelData['google_place_id'];
        
        if (!$placeId || $this->isFakePlaceId($placeId)) {
            throw new Exception("Place ID de Google inválido o faltante");
        }
        
        // Por ahora retornar estructura vacía
        return [
            'platform' => 'google_maps',
            'count' => 0,
            'reviews' => [],
            'source_place_id' => $placeId,
            'estimated_cost' => 0,
            'note' => 'Implementación pendiente'
        ];
    }
    
    /**
     * Extraer de TripAdvisor (implementación futura)
     */
    private function extractFromTripAdvisor($hotelData, $maxReviews, $config) {
        // Implementar con maxcopell/tripadvisor-reviews
        return [
            'platform' => 'tripadvisor',
            'count' => 0,
            'reviews' => [],
            'estimated_cost' => 0,
            'note' => 'Implementación pendiente'
        ];
    }
    
    /**
     * Extraer de Expedia (implementación futura)
     */
    private function extractFromExpedia($hotelData, $maxReviews, $config) {
        // Implementar con tri_angle/expedia-hotels-com-reviews-scraper
        return [
            'platform' => 'expedia',
            'count' => 0,
            'reviews' => [],
            'estimated_cost' => 0,
            'note' => 'Implementación pendiente'
        ];
    }
    
    /**
     * Buscar URL de Booking para un hotel
     */
    private function findBookingUrl($hotelName) {
        // Generar URL probable basada en el nombre
        $cleanName = strtolower($hotelName);
        $cleanName = str_replace(['ñ'], ['n'], $cleanName);
        $cleanName = preg_replace('/[^a-z0-9\s]/', '', $cleanName);
        $cleanName = str_replace(' ', '-', trim($cleanName));
        
        // URLs comunes para hoteles en México
        $possibleUrls = [
            "https://www.booking.com/hotel/mx/{$cleanName}.html",
            "https://www.booking.com/hotel/mx/{$cleanName}-cancun.html",
            "https://www.booking.com/hotel/mx/hotel-{$cleanName}.html"
        ];
        
        return $possibleUrls[0]; // Por ahora retornar la primera
    }
    
    /**
     * Verificar si un Place ID es falso
     */
    private function isFakePlaceId($placeId) {
        // Los Place IDs falsos que encontramos tienen patrones específicos
        $fakePatterns = [
            '/^ChIJ[a-zA-Z0-9_-]{15,25}$/', // Patrón demasiado simple
            '/kav/', '/caribe/', '/xbalamque/', '/hacienda/', '/imperial/', '/plaza/', '/luma/' // Nombres incluidos
        ];
        
        foreach ($fakePatterns as $pattern) {
            if (preg_match($pattern, $placeId)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Guardar reseñas en la base de datos
     */
    public function saveReviewsToDatabase($reviews, $hotelId) {
        try {
            $host = "soporteclientes.net";
            $dbname = "soporteia_bookingkavia";
            $username = "soporteia_admin";
            $password = "QCF8RhS*}.Oj0u(v";
            
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $insertedCount = 0;
            $duplicateCount = 0;
            
            foreach ($reviews as $review) {
                // Verificar duplicados por platform_review_id
                $checkStmt = $pdo->prepare("
                    SELECT id FROM reviews 
                    WHERE platform_review_id = ? AND platform = ? AND hotel_id = ?
                ");
                $checkStmt->execute([$review['platform_review_id'], $review['platform'], $hotelId]);
                
                if ($checkStmt->rowCount() > 0) {
                    $duplicateCount++;
                    continue;
                }
                
                // Insertar reseña
                $insertStmt = $pdo->prepare("
                    INSERT INTO reviews (
                        hotel_id, platform, platform_review_id, title, content, 
                        rating, author, review_date, scraped_at, helpful_votes,
                        verified_stay, room_type, stay_date, traveler_type, url, language
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $insertStmt->execute([
                    $hotelId,
                    $review['platform'],
                    $review['platform_review_id'],
                    $review['title'],
                    $review['content'],
                    $review['rating'],
                    $review['author'],
                    $review['review_date'],
                    $review['scraped_at'],
                    $review['helpful_votes'],
                    $review['verified_stay'] ? 1 : 0,
                    $review['room_type'],
                    $review['stay_date'],
                    $review['traveler_type'],
                    $review['url'],
                    $review['language']
                ]);
                
                $insertedCount++;
            }
            
            return [
                'success' => true,
                'inserted' => $insertedCount,
                'duplicates' => $duplicateCount,
                'total_processed' => count($reviews)
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'inserted' => 0
            ];
        }
    }
    
    /**
     * Obtener estadísticas de scrapers disponibles
     */
    public function getScrapersInfo() {
        $info = [];
        
        foreach ($this->availableScrapers as $platform => $config) {
            $info[$platform] = [
                'name' => $config['name'],
                'status' => $platform === 'booking' ? 'active' : 'pending',
                'cost_per_review' => $config['cost_per_review'],
                'max_reviews' => $config['max_reviews_per_run']
            ];
        }
        
        return $info;
    }
    
    /**
     * Estimar costo total para extracción completa
     */
    public function estimateTotalCost($numHotels, $reviewsPerPlatform, $platforms) {
        $totalCost = 0;
        
        foreach ($platforms as $platform) {
            if (isset($this->availableScrapers[$platform])) {
                $costPerReview = $this->availableScrapers[$platform]['cost_per_review'];
                $totalCost += $numHotels * $reviewsPerPlatform * $costPerReview;
            }
        }
        
        return $totalCost;
    }
}

// Ejemplo de uso
if (basename(__FILE__) == basename($_SERVER["SCRIPT_FILENAME"])) {
    
    echo "=== SISTEMA MULTI-PLATAFORMA ===\n\n";
    
    try {
        $multiScraper = new MultiPlatformScraper();
        
        echo "📊 SCRAPERS DISPONIBLES:\n";
        $scrapersInfo = $multiScraper->getScrapersInfo();
        
        foreach ($scrapersInfo as $platform => $info) {
            $status = $info['status'] === 'active' ? '✅' : '⏳';
            echo "   {$status} {$info['name']}\n";
            echo "      - Costo: $" . number_format($info['cost_per_review'], 4) . " por reseña\n";
            echo "      - Máximo: " . number_format($info['max_reviews']) . " reseñas por run\n\n";
        }
        
        // Ejemplo de extracción para un hotel
        $hotelData = [
            'id' => 6,
            'nombre_hotel' => 'Hard Rock Hotel Cancun',
            'google_place_id' => 'ChIJ3cWF0FjPTYUR8LcqQNNi-Qw' // Place ID falso de ejemplo
        ];
        
        echo "🏨 EJEMPLO: Extraer reseñas para {$hotelData['nombre_hotel']}\n\n";
        
        $options = [
            'max_reviews_per_platform' => 5, // Solo 5 para ejemplo
            'platforms' => ['booking'], // Solo Booking por ahora
            'language' => 'es'
        ];
        
        // Estimar costo
        $estimatedCost = $multiScraper->estimateTotalCost(1, 5, ['booking']);
        echo "💰 Costo estimado: $" . number_format($estimatedCost, 4) . "\n\n";
        
        echo "🚀 Iniciando extracción multi-plataforma...\n\n";
        
        $result = $multiScraper->extractAllPlatforms($hotelData, $options);
        
        echo "📊 RESULTADOS FINALES:\n";
        echo "   - Total reseñas: " . $result['total_reviews'] . "\n";
        echo "   - Plataformas exitosas: " . count($result['platforms']) . "\n";
        echo "   - Tiempo total: " . $result['execution_time'] . " segundos\n";
        echo "   - Costo real: $" . number_format($result['estimated_cost'], 4) . "\n";
        
        if (!empty($result['errors'])) {
            echo "   - Errores: " . count($result['errors']) . "\n";
            foreach ($result['errors'] as $platform => $error) {
                echo "     {$platform}: {$error}\n";
            }
        }
        
        if ($result['total_reviews'] > 0) {
            echo "\n✅ SISTEMA MULTI-PLATAFORMA FUNCIONANDO\n";
            
            // Mostrar muestra de reseñas
            echo "\n📝 MUESTRA DE RESEÑAS:\n";
            foreach (array_slice($result['all_reviews'], 0, 2) as $i => $review) {
                echo "   Reseña " . ($i + 1) . " ({$review['platform']}):\n";
                echo "      - Rating: {$review['rating']}/5\n";
                echo "      - Autor: {$review['author']}\n";
                echo "      - Fecha: {$review['review_date']}\n";
                echo "      - Contenido: " . substr($review['content'], 0, 100) . "...\n\n";
            }
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "=== FIN SISTEMA MULTI-PLATAFORMA ===\n";
}
?>