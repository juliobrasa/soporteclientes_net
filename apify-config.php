<?php
/**
 * Configuración del cliente Apify para Hotel Review Aggregator
 */

// Cargar variables de entorno
require_once __DIR__ . '/env-loader.php';

class ApifyClient {
    private $apiToken;
    private $baseUrl = 'https://api.apify.com/v2';
    private $actorId = 'tri_angle~hotel-review-aggregator';
    private $demoMode = false;
    
    public function __construct($apiToken = null) {
        $this->apiToken = $apiToken ?: $_ENV['APIFY_API_TOKEN'] ?? null;
        
        // Modo demo si no hay token real
        if (!$this->apiToken || $this->apiToken === 'demo_token_replace_with_real') {
            $this->demoMode = true;
            $this->apiToken = 'demo_token';
        }
    }
    
    /**
     * Iniciar extracción de reseñas para un hotel (método asíncrono)
     */
    public function startHotelExtraction($config) {
        if ($this->demoMode) {
            return $this->simulateExtractionStart($config);
        }
        
        $input = $this->buildExtractionInput($config);
        
        $response = $this->makeRequest('POST', "/acts/{$this->actorId}/runs", [
            'input' => $input
        ]);
        
        return $response;
    }
    
    /**
     * Ejecutar extracción síncrona y obtener resultados directamente
     */
    public function runHotelExtractionSync($config, $timeout = 300) {
        if ($this->demoMode) {
            return $this->simulateExtractionSync($config);
        }
        
        $input = $this->buildExtractionInput($config);
        
        try {
            // Método 1: Intentar run síncrono con dataset
            $queryParams = http_build_query([
                'timeout' => $timeout,
                'memory' => 4096,
                'format' => 'json'
            ]);
            
            $response = $this->makeRequest('POST', "/acts/{$this->actorId}/run-sync-get-dataset-items?{$queryParams}", $input);
            
            if ($response && isset($response['success']) && $response['success']) {
                return $response;
            }
            
            // Método 2: Run asíncrono y esperar resultados
            error_log("Método síncrono falló, intentando asíncrono...");
            
            $runResponse = $this->startHotelExtraction($config);
            if (!$runResponse || !isset($runResponse['data']['id'])) {
                throw new Exception("No se pudo iniciar el run");
            }
            
            $runId = $runResponse['data']['id'];
            error_log("Run iniciado: {$runId}");
            
            // Esperar a que termine (máximo timeout)
            $startTime = time();
            while (time() - $startTime < $timeout) {
                sleep(5); // Esperar 5 segundos
                
                $status = $this->getRunStatus($runId);
                if (!$status || !isset($status['data']['status'])) {
                    continue;
                }
                
                $runStatus = $status['data']['status'];
                error_log("Run status: {$runStatus}");
                
                if ($runStatus === 'SUCCEEDED') {
                    // Obtener resultados
                    $results = $this->getRunResults($runId);
                    return [
                        'success' => true,
                        'data' => $results,
                        'run_id' => $runId,
                        'execution_time' => time() - $startTime
                    ];
                } elseif ($runStatus === 'FAILED') {
                    throw new Exception("El run falló");
                } elseif ($runStatus === 'ABORTED') {
                    throw new Exception("El run fue abortado");
                }
            }
            
            throw new Exception("Timeout esperando resultados del run");
            
        } catch (Exception $e) {
            error_log("Error en runHotelExtractionSync: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Construir input para el actor usando esquema correcto
     */
    private function buildExtractionInput($config) {
        // Esquema correcto basado en documentación oficial
        $defaultConfig = [
            'maxReviews' => 1000,
            'reviewsFromDate' => date('Y-01-01'), // Fecha desde cuando extraer
            'scrapeReviewPictures' => false, // Para reducir costo
            'scrapeReviewResponses' => true,  // Incluir respuestas del hotel
            // Habilitar plataformas específicas
            'enableGoogleMaps' => true,
            'enableTripadvisor' => true, 
            'enableBooking' => true,
            'enableExpedia' => true,
            'enableHotelsCom' => true,
            'enableYelp' => true,
            'enableAirbnb' => false // Deshabilitar Airbnb por defecto
        ];
        
        // Configurar startIds con Place IDs
        if (isset($config['hotelId'])) {
            $defaultConfig['startIds'] = [$config['hotelId']];
        }
        
        // Configurar URLs si se proporcionan
        if (isset($config['startUrls'])) {
            $defaultConfig['startUrls'] = $config['startUrls'];
        }
        
        return array_merge($defaultConfig, $config);
    }
    
    /**
     * Obtener estado de una ejecución
     */
    public function getRunStatus($runId) {
        if ($this->demoMode) {
            return $this->simulateRunStatus($runId);
        }
        
        return $this->makeRequest('GET', "/actor-runs/{$runId}");
    }
    
    /**
     * Obtener resultados de una ejecución
     */
    public function getRunResults($runId) {
        if ($this->demoMode) {
            return [];
        }
        
        // Primero obtener información del run para encontrar el dataset
        $runInfo = $this->makeRequest('GET', "/actor-runs/{$runId}");
        
        if ($runInfo && isset($runInfo['data']['defaultDatasetId'])) {
            $datasetId = $runInfo['data']['defaultDatasetId'];
            $items = $this->makeRequest('GET', "/datasets/{$datasetId}/items");
            return $items ?: [];
        }
        
        // Método alternativo: usar directamente el runId como dataset
        $items = $this->makeRequest('GET', "/datasets/{$runId}/items");
        return $items ?: [];
    }
    
    /**
     * Realizar petición HTTP a la API de Apify
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
            CURLOPT_TIMEOUT => 300, // 5 minutos timeout
            CURLOPT_CONNECTTIMEOUT => 30, // 30 segundos para conectar
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'Hotel Review System/1.0'
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
            throw new Exception("Error en petición cURL: {$error}");
        }
        
        if ($httpCode >= 400) {
            throw new Exception("Error HTTP {$httpCode}: {$response}");
        }
        
        return json_decode($response, true);
    }
    
    /**
     * Estimar costo de extracción
     */
    public function estimateCost($totalReviews) {
        $pricePerThousand = 1.50;
        return ($totalReviews / 1000) * $pricePerThousand;
    }
    
    /**
     * Debug temporal - obtener información del estado
     */
    public function getDebugInfo() {
        return [
            'demo_mode' => $this->demoMode,
            'api_token' => $this->apiToken ? substr($this->apiToken, 0, 20) . '...' : 'NULL',
            'base_url' => $this->baseUrl,
            'actor_id' => $this->actorId
        ];
    }
    
    /**
     * Validar Google Place ID
     */
    public function validatePlaceId($placeId) {
        return preg_match('/^ChIJ[a-zA-Z0-9_-]+$/', $placeId);
    }
    
    /**
     * Extraer Place ID de URL de Google Maps
     */
    public function extractPlaceIdFromUrl($url) {
        // Extraer CID de URL como https://maps.google.com/maps?cid=123456789
        if (preg_match('/cid=(\d+)/', $url, $matches)) {
            return $this->cidToPlaceId($matches[1]);
        }
        
        // Extraer Place ID directo
        if (preg_match('/place\/([^\/]+)/', $url, $matches)) {
            $encoded = $matches[1];
            // Decodificar si está en formato URL
            return urldecode($encoded);
        }
        
        return null;
    }
    
    /**
     * Convertir CID a Place ID (método aproximado)
     */
    private function cidToPlaceId($cid) {
        // Nota: Conversión exacta requiere API de Google
        // Este es un placeholder para implementación futura
        return "ChIJ_cid_{$cid}";
    }
    
    /**
     * Simular inicio de extracción en modo demo
     */
    private function simulateExtractionStart($config) {
        $runId = 'demo_' . uniqid();
        
        return [
            'data' => [
                'id' => $runId,
                'status' => 'READY',
                'createdAt' => date('c'),
                'modifiedAt' => date('c'),
                'input' => $config
            ],
            'success' => true,
            'demo_mode' => true
        ];
    }
    
    /**
     * Simular estado de ejecución en modo demo
     */
    private function simulateRunStatus($runId) {
        // Simular diferentes estados basados en tiempo
        $states = ['READY', 'RUNNING', 'SUCCEEDED'];
        $randomState = $states[array_rand($states)];
        
        return [
            'data' => [
                'id' => $runId,
                'status' => $randomState,
                'startedAt' => date('c', strtotime('-5 minutes')),
                'finishedAt' => $randomState === 'SUCCEEDED' ? date('c') : null,
                'stats' => [
                    'inputBodyLen' => 1024,
                    'restartCount' => 0,
                    'resurrectCount' => 0
                ]
            ],
            'success' => true,
            'demo_mode' => true
        ];
    }
    
    /**
     * Simular extracción síncrona en modo demo
     */
    private function simulateExtractionSync($config) {
        // Simular datos de reseñas realistas
        $sampleReviews = [
            [
                'reviewId' => 'demo_review_' . uniqid(),
                'reviewerName' => 'María González',
                'rating' => 5,
                'reviewText' => 'Excelente hotel, el servicio fue impecable y las instalaciones de primera calidad. Definitivamente regresaremos.',
                'reviewDate' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                'platform' => 'booking',
                'sentiment' => 'positive',
                'helpful' => rand(0, 15)
            ],
            [
                'reviewId' => 'demo_review_' . uniqid(),
                'reviewerName' => 'Carlos Martínez',
                'rating' => 4,
                'reviewText' => 'Muy buena ubicación, habitaciones limpias. Solo el desayuno podría mejorar un poco.',
                'reviewDate' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                'platform' => 'tripadvisor',
                'sentiment' => 'positive',
                'helpful' => rand(0, 10)
            ],
            [
                'reviewId' => 'demo_review_' . uniqid(),
                'reviewerName' => 'Ana Rodríguez',
                'rating' => 3,
                'reviewText' => 'Hotel decente pero el precio es un poco alto para lo que ofrece. El personal es amable.',
                'reviewDate' => date('Y-m-d', strtotime('-' . rand(1, 30) . ' days')),
                'platform' => 'google',
                'sentiment' => 'neutral',
                'helpful' => rand(0, 8)
            ]
        ];
        
        // Generar más reseñas basado en el maxReviews configurado
        $maxReviews = $config['maxReviews'] ?? 10;
        $reviews = [];
        
        for ($i = 0; $i < min($maxReviews, 1000); $i++) { // Aumentar límite de simulación
            $template = $sampleReviews[$i % count($sampleReviews)];
            $template['reviewId'] = 'demo_review_' . uniqid();
            $template['reviewDate'] = date('Y-m-d', strtotime('-' . rand(1, 365) . ' days'));
            $reviews[] = $template;
        }
        
        return [
            'success' => true,
            'demo_mode' => true,
            'data' => $reviews,
            'stats' => [
                'totalReviews' => count($reviews),
                'platforms' => $config['reviewPlatforms'] ?? ['booking', 'tripadvisor', 'google'],
                'avgRating' => round(array_sum(array_column($reviews, 'rating')) / count($reviews), 2),
                'executionTime' => rand(30, 180) // segundos
            ]
        ];
    }
}

/**
 * Clase para análisis de sentimientos básico
 */
class SentimentAnalyzer {
    private $positiveWords = [
        'excelente', 'bueno', 'genial', 'perfecto', 'increíble', 
        'fantastic', 'excellent', 'great', 'perfect', 'amazing',
        'wonderful', 'outstanding', 'superb'
    ];
    
    private $negativeWords = [
        'terrible', 'malo', 'horrible', 'pésimo', 'awful',
        'bad', 'horrible', 'terrible', 'worst', 'disappointing',
        'poor', 'unacceptable'
    ];
    
    /**
     * Analizar sentimiento de texto (básico)
     */
    public function analyzeSentiment($text) {
        $text = strtolower($text);
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach ($this->positiveWords as $word) {
            $positiveCount += substr_count($text, $word);
        }
        
        foreach ($this->negativeWords as $word) {
            $negativeCount += substr_count($text, $word);
        }
        
        if ($positiveCount > $negativeCount) {
            $sentiment = 'positive';
            $score = min(1.0, $positiveCount / max(1, $negativeCount));
        } elseif ($negativeCount > $positiveCount) {
            $sentiment = 'negative';
            $score = -min(1.0, $negativeCount / max(1, $positiveCount));
        } else {
            $sentiment = 'neutral';
            $score = 0.0;
        }
        
        return [
            'sentiment' => $sentiment,
            'score' => $score,
            'confidence' => abs($score)
        ];
    }
    
    /**
     * Extraer temas principales del texto
     */
    public function extractTopics($text) {
        $topics = [
            'limpieza' => ['clean', 'limpio', 'sucio', 'dirty', 'hygiene'],
            'servicio' => ['service', 'servicio', 'staff', 'personal', 'atencion'],
            'ubicacion' => ['location', 'ubicacion', 'zona', 'area', 'centro'],
            'comida' => ['food', 'comida', 'restaurant', 'breakfast', 'desayuno'],
            'habitacion' => ['room', 'habitacion', 'bed', 'cama', 'bathroom'],
            'precio' => ['price', 'precio', 'value', 'money', 'expensive', 'cheap']
        ];
        
        $text = strtolower($text);
        $foundTopics = [];
        
        foreach ($topics as $topic => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($text, $keyword) !== false) {
                    $foundTopics[] = $topic;
                    break;
                }
            }
        }
        
        return array_unique($foundTopics);
    }
}
?>