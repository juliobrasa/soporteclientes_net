<?php
/**
 * ==========================================================================
 * PROBAR FORMATOS COMUNES DE INPUT PARA HOTEL REVIEW SCRAPERS
 * Basado en patrones comunes de Apify actors
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PROBAR FORMATOS DE INPUT COMUNES ===\n\n";

try {
    // Cargar token
    $envFile = __DIR__ . '/.env';
    $token = null;
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, 'APIFY_API_TOKEN=') === 0) {
                $token = substr($line, strlen('APIFY_API_TOKEN='));
                break;
            }
        }
    }
    
    $actorId = 'tri_angle~hotel-review-aggregator';
    
    // Usar Place ID real del Hard Rock Cancún
    $realPlaceId = 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U';
    
    // Diferentes formatos de input que son comunes en scrapers de hoteles
    $testInputs = [
        [
            'name' => 'Formato 1: placeIds array',
            'input' => [
                'placeIds' => [$realPlaceId],
                'maxReviews' => 5,
                'language' => 'en'
            ]
        ],
        [
            'name' => 'Formato 2: hotelPlaceIds',
            'input' => [
                'hotelPlaceIds' => [$realPlaceId],
                'maxReviewsPerHotel' => 5,
                'languages' => ['en', 'es']
            ]
        ],
        [
            'name' => 'Formato 3: places con objetos',
            'input' => [
                'places' => [
                    [
                        'placeId' => $realPlaceId,
                        'name' => 'Hard Rock Hotel Cancun'
                    ]
                ],
                'maxReviewsPerPlace' => 5,
                'reviewLanguages' => ['en']
            ]
        ],
        [
            'name' => 'Formato 4: URLs de Google Maps',
            'input' => [
                'hotelUrls' => ["https://www.google.com/maps/place/?q=place_id:{$realPlaceId}"],
                'maxReviews' => 5
            ]
        ],
        [
            'name' => 'Formato 5: placeId simple',
            'input' => [
                'placeId' => $realPlaceId,
                'maxReviews' => 5,
                'platforms' => ['google']
            ]
        ],
        [
            'name' => 'Formato 6: searchTerms',
            'input' => [
                'searchTerms' => ['Hard Rock Hotel Cancun'],
                'maxReviewsPerSearch' => 5,
                'country' => 'MX'
            ]
        ]
    ];
    
    foreach ($testInputs as $i => $test) {
        echo "🧪 PRUEBA " . ($i + 1) . ": {$test['name']}\n";
        echo "Input: " . json_encode($test['input'], JSON_PRETTY_PRINT) . "\n";
        
        // Hacer llamada corta (30 segundos timeout)
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/run-sync?timeout=30",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($test['input']),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        echo "HTTP Code: {$httpCode}\n";
        
        if ($error) {
            echo "❌ cURL Error: {$error}\n";
        }
        
        if ($response) {
            $responseData = json_decode($response, true);
            
            if ($responseData) {
                if ($httpCode === 201 || $httpCode === 200) {
                    echo "✅ Run creado exitosamente\n";
                    
                    if (isset($responseData['data'])) {
                        $runId = $responseData['data']['id'] ?? 'N/A';
                        $status = $responseData['data']['status'] ?? 'N/A';
                        echo "Run ID: {$runId}, Status: {$status}\n";
                        
                        // Si es SUCCEEDED, intentar obtener resultados inmediatamente
                        if ($status === 'SUCCEEDED' && $runId !== 'N/A') {
                            echo "🎉 Run completado inmediatamente, obteniendo resultados...\n";
                            
                            sleep(2); // Esperar un poco
                            
                            $curl = curl_init();
                            curl_setopt_array($curl, [
                                CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/runs/{$runId}/dataset/items?limit=2",
                                CURLOPT_RETURNTRANSFER => true,
                                CURLOPT_HTTPHEADER => [
                                    'Authorization: Bearer ' . $token,
                                    'Content-Type: application/json'
                                ]
                            ]);
                            
                            $resultsResponse = curl_exec($curl);
                            $resultsHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                            curl_close($curl);
                            
                            if ($resultsHttpCode === 200 && $resultsResponse) {
                                $results = json_decode($resultsResponse, true);
                                if ($results && is_array($results) && !empty($results)) {
                                    echo "🎉 ¡ÉXITO! Encontramos " . count($results) . " resultados\n";
                                    echo "Primera reseña:\n";
                                    print_r($results[0]);
                                    echo "\n¡Este formato funciona!\n";
                                    break; // Salir del loop si encontramos un formato que funciona
                                } else {
                                    echo "⚠️  Run exitoso pero 0 resultados\n";
                                }
                            }
                        }
                    } else {
                        echo "⚠️  Respuesta sin data\n";
                        echo "Response: " . substr($response, 0, 200) . "...\n";
                    }
                } else {
                    echo "❌ Error HTTP {$httpCode}\n";
                    echo "Response: " . substr($response, 0, 300) . "...\n";
                }
            } else {
                echo "❌ JSON inválido\n";
                echo "Raw: " . substr($response, 0, 200) . "...\n";
            }
        } else {
            echo "❌ Sin respuesta\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
        
        // Pausa entre pruebas para no saturar la API
        sleep(3);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN DE PRUEBAS ===\n";
?>