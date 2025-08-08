<?php
/**
 * ==========================================================================
 * PROBAR ACTOR compass/crawler-google-places
 * El actor más popular para Google Maps (12M+ runs)
 * ==========================================================================
 */

echo "=== PROBAR COMPASS GOOGLE MAPS SCRAPER ===\n\n";

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
    
    if (!$token) {
        throw new Exception("Token no encontrado");
    }
    
    // Actor más popular para Google Maps
    $actorId = 'compass/crawler-google-places';
    
    echo "🎭 Actor: {$actorId}\n";
    echo "📊 Popularidad: 12M+ runs (el más usado para Google Maps)\n\n";
    
    // Place ID real del Hard Rock Cancún
    $realPlaceId = 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U';
    
    // Obtener esquema del actor primero
    echo "📋 Obteniendo esquema del actor...\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode === 200 && $response) {
        $actorData = json_decode($response, true);
        
        if ($actorData && isset($actorData['data'])) {
            $actor = $actorData['data'];
            
            echo "✅ Actor encontrado: " . $actor['title'] . "\n";
            echo "📝 Descripción: " . substr($actor['description'], 0, 100) . "...\n\n";
            
            // Intentar encontrar esquema de input
            $schema = $actor['inputSchema'] ?? $actor['taggedBuilds']['latest']['inputSchema'] ?? null;
            
            if ($schema && isset($schema['properties'])) {
                echo "📋 PARÁMETROS DISPONIBLES:\n";
                
                $importantParams = [];
                foreach ($schema['properties'] as $param => $info) {
                    echo "   - {$param}: " . ($info['title'] ?? $info['type'] ?? 'N/A') . "\n";
                    
                    // Identificar parámetros importantes
                    if (in_array($param, ['searchStringsArray', 'placeIds', 'startUrls', 'query'])) {
                        $importantParams[$param] = $info;
                    }
                }
                
                echo "\n🎯 PARÁMETROS CLAVE IDENTIFICADOS:\n";
                foreach ($importantParams as $param => $info) {
                    echo "   - {$param}: " . ($info['description'] ?? $info['title'] ?? 'N/A') . "\n";
                }
                echo "\n";
            }
        }
    }
    
    // Probar diferentes formatos de input comunes para Google Maps scrapers
    $testInputs = [
        [
            'name' => 'searchStringsArray (búsqueda por nombre)',
            'input' => [
                'searchStringsArray' => ['Hard Rock Hotel Cancun, Mexico'],
                'maxCrawledPlaces' => 3,
                'includeReviews' => true,
                'maxReviews' => 5,
                'reviewsSort' => 'newest'
            ]
        ],
        [
            'name' => 'placeIds (array de Place IDs)',
            'input' => [
                'placeIds' => [$realPlaceId],
                'includeReviews' => true,
                'maxReviews' => 5,
                'reviewsSort' => 'newest'
            ]
        ],
        [
            'name' => 'startUrls (URLs de Google Maps)',
            'input' => [
                'startUrls' => [
                    "https://www.google.com/maps/place/?q=place_id:{$realPlaceId}"
                ],
                'includeReviews' => true,
                'maxReviews' => 5
            ]
        ]
    ];
    
    foreach ($testInputs as $i => $test) {
        echo "🧪 PRUEBA " . ($i + 1) . ": {$test['name']}\n";
        echo "Input: " . json_encode($test['input'], JSON_PRETTY_PRINT) . "\n";
        
        // Ejecutar con timeout corto para prueba
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/run-sync?timeout=60",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($test['input']),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ],
            CURLOPT_TIMEOUT => 90 // Timeout del cURL
        ]);
        
        $startTime = time();
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        $executionTime = time() - $startTime;
        
        echo "HTTP Code: {$httpCode}, Tiempo: {$executionTime}s\n";
        
        if ($error) {
            echo "❌ cURL Error: {$error}\n";
        } elseif ($response) {
            $responseData = json_decode($response, true);
            
            if ($responseData && is_array($responseData)) {
                $resultCount = count($responseData);
                echo "✅ Respuesta recibida: {$resultCount} resultados\n";
                
                if ($resultCount > 0) {
                    echo "🎉 ¡ÉXITO! Actor funcionando\n\n";
                    
                    $firstResult = $responseData[0];
                    echo "📝 ESTRUCTURA DEL PRIMER RESULTADO:\n";
                    
                    foreach ($firstResult as $field => $value) {
                        if (is_array($value)) {
                            echo "   - {$field}: Array con " . count($value) . " elementos\n";
                            
                            // Mostrar reviews si las hay
                            if ($field === 'reviews' && !empty($value)) {
                                echo "     Primera review: " . json_encode(array_slice($value[0] ?? [], 0, 3)) . "\n";
                            }
                        } else {
                            $displayValue = is_string($value) ? substr($value, 0, 50) . "..." : $value;
                            echo "   - {$field}: {$displayValue}\n";
                        }
                    }
                    
                    // Verificar si tiene reviews
                    if (isset($firstResult['reviews']) && !empty($firstResult['reviews'])) {
                        $reviews = $firstResult['reviews'];
                        echo "\n🌟 REVIEWS ENCONTRADAS: " . count($reviews) . "\n";
                        
                        foreach (array_slice($reviews, 0, 2) as $j => $review) {
                            echo "\n   Review " . ($j + 1) . ":\n";
                            foreach ($review as $key => $val) {
                                $displayVal = is_string($val) ? substr($val, 0, 80) . "..." : $val;
                                echo "     - {$key}: {$displayVal}\n";
                            }
                        }
                        
                        echo "\n✅ ¡ESTE ACTOR FUNCIONA PERFECTAMENTE!\n";
                        echo "🎯 Formato exitoso: {$test['name']}\n";
                        
                        // Estimar costo
                        $estimatedCost = count($responseData) * 0.001; // Estimación aproximada
                        echo "💰 Costo estimado: ~$" . number_format($estimatedCost, 4) . "\n";
                        
                        break; // Salir del loop, encontramos un formato que funciona
                    } else {
                        echo "⚠️  Resultado sin reviews - verificar configuración\n";
                    }
                } else {
                    echo "⚠️  Respuesta vacía\n";
                }
            } else {
                echo "❌ Respuesta no es JSON válido o está vacía\n";
                echo "Response preview: " . substr($response, 0, 200) . "...\n";
            }
        } else {
            echo "❌ Sin respuesta\n";
        }
        
        echo "\n" . str_repeat("-", 50) . "\n\n";
        
        // Pausa entre pruebas
        sleep(3);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN PRUEBA COMPASS GOOGLE MAPS ===\n";
?>