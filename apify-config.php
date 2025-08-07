<?php
/**
 * Configuración del cliente Apify para Hotel Review Aggregator
 */

class ApifyClient {
    private $apiToken;
    private $baseUrl = 'https://api.apify.com/v2';
    private $actorId = 'tri_angle/hotel-review-aggregator';
    
    public function __construct($apiToken = null) {
        $this->apiToken = $apiToken ?: $_ENV['APIFY_API_TOKEN'] ?? null;
        if (!$this->apiToken) {
            throw new Exception('APIFY_API_TOKEN no configurado');
        }
    }
    
    /**
     * Iniciar extracción de reseñas para un hotel
     */
    public function startHotelExtraction($config) {
        $input = $this->buildExtractionInput($config);
        
        $response = $this->makeRequest('POST', "/acts/{$this->actorId}/runs", [
            'input' => $input
        ]);
        
        return $response;
    }
    
    /**
     * Construir input para el actor
     */
    private function buildExtractionInput($config) {
        $defaultConfig = [
            'maxReviews' => 100,
            'reviewPlatforms' => [
                'tripadvisor',
                'booking', 
                'expedia',
                'hotels',
                'airbnb',
                'yelp',
                'google'
            ],
            'reviewLanguages' => ['en', 'es'],
            'reviewDates' => [
                'from' => date('Y-01-01'),
                'to' => date('Y-12-31')
            ]
        ];
        
        return array_merge($defaultConfig, $config);
    }
    
    /**
     * Obtener estado de una ejecución
     */
    public function getRunStatus($runId) {
        return $this->makeRequest('GET', "/actor-runs/{$runId}");
    }
    
    /**
     * Obtener resultados de una ejecución
     */
    public function getRunResults($runId) {
        return $this->makeRequest('GET', "/datasets/{$runId}/items");
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
            CURLOPT_CUSTOMREQUEST => $method
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