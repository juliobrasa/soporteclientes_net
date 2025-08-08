<?php
/**
 * ==========================================================================
 * PROBAR CON IDs CORRECTOS DE ACTORES POPULARES
 * ==========================================================================
 */

echo "=== PROBAR ACTORES CON IDs CORRECTOS ===\n\n";

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
    
    // Los IDs correctos de los actores más populares
    $actors = [
        'compass/crawler-google-places' => 'nwua9Gu5YrADL7ZDj',
        'compass/Google-Maps-Reviews-Scraper' => 'Xb8osYTtOjlsgI6k9',
        'maxcopell/tripadvisor-reviews' => 'Hvp4YfFGyLM635Q2F',
        'voyager/booking-reviews-scraper' => 'PbMHke3jW25J6hSOA'
    ];
    
    foreach ($actors as $name => $actorId) {
        echo "🔍 Verificando actor: {$name} (ID: {$actorId})\n";
        
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
                echo "   Runs: " . number_format($actor['stats']['totalRuns'] ?? 0) . "\n";
                echo "   Descripción: " . substr($actor['description'] ?? 'N/A', 0, 80) . "...\n";
                
                // Buscar parámetros clave en el schema
                $schema = $actor['inputSchema'] ?? $actor['taggedBuilds']['latest']['inputSchema'] ?? null;
                
                if ($schema && isset($schema['properties'])) {
                    echo "   Parámetros clave: ";
                    $keyParams = [];
                    
                    foreach ($schema['properties'] as $param => $info) {
                        if (in_array($param, ['searchStringsArray', 'placeIds', 'startUrls', 'query', 'placeUrls', 'hotelUrls'])) {
                            $keyParams[] = $param;
                        }
                    }
                    
                    echo implode(', ', $keyParams) . "\n";
                }
            }
        } elseif ($httpCode === 404) {
            echo "❌ Actor no encontrado\n";
        } else {
            echo "⚠️  Error verificando (HTTP {$httpCode})\n";
        }
        
        echo "\n";
    }
    
    echo "🧪 PROBANDO EL GOOGLE MAPS SCRAPER MÁS POPULAR:\n\n";
    
    // Usar el ID correcto del Google Maps scraper más popular
    $actorId = 'nwua9Gu5YrADL7ZDj'; // compass/crawler-google-places
    $realPlaceId = 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U';
    
    // Input basado en documentación común de Google Maps scrapers
    $testInput = [
        'searchStringsArray' => ['Hard Rock Hotel Cancun'],
        'maxCrawledPlaces' => 1,
        'includeReviews' => true,
        'maxReviews' => 3,
        'reviewsSort' => 'newest',
        'language' => 'en'
    ];
    
    echo "📍 Input de prueba:\n";
    echo json_encode($testInput, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "🚀 Ejecutando extracción...\n";
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/run-sync?timeout=90",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testInput),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 120
    ]);
    
    $startTime = time();
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    curl_close($curl);
    
    $executionTime = time() - $startTime;
    
    echo "⏱️  Completado en {$executionTime} segundos\n";
    echo "HTTP Code: {$httpCode}\n";
    
    if ($error) {
        echo "❌ cURL Error: {$error}\n";
    } elseif ($response) {
        $responseData = json_decode($response, true);
        
        if ($responseData && is_array($responseData)) {
            echo "✅ Datos recibidos: " . count($responseData) . " resultados\n\n";
            
            if (!empty($responseData)) {
                $result = $responseData[0];
                
                echo "📊 INFORMACIÓN DEL HOTEL:\n";
                echo "   - Nombre: " . ($result['title'] ?? 'N/A') . "\n";
                echo "   - Dirección: " . ($result['address'] ?? 'N/A') . "\n";
                echo "   - Rating: " . ($result['totalScore'] ?? 'N/A') . "\n";
                echo "   - Total reviews: " . ($result['reviewsCount'] ?? 'N/A') . "\n";
                echo "   - Place ID: " . ($result['placeId'] ?? 'N/A') . "\n\n";
                
                if (isset($result['reviews']) && !empty($result['reviews'])) {
                    $reviews = $result['reviews'];
                    echo "🌟 REVIEWS EXTRAÍDAS: " . count($reviews) . "\n\n";
                    
                    foreach (array_slice($reviews, 0, 2) as $i => $review) {
                        echo "   Review " . ($i + 1) . ":\n";
                        echo "   - Autor: " . ($review['name'] ?? 'N/A') . "\n";
                        echo "   - Rating: " . ($review['stars'] ?? 'N/A') . "\n";
                        echo "   - Fecha: " . ($review['publishedAtDate'] ?? 'N/A') . "\n";
                        echo "   - Texto: " . substr($review['text'] ?? '', 0, 100) . "...\n\n";
                    }
                    
                    echo "✅ ¡PERFECTO! El sistema está funcionando\n\n";
                    
                    echo "🎯 FORMATO CORRECTO IDENTIFICADO:\n";
                    echo "   - Actor ID: {$actorId}\n";
                    echo "   - Parámetro principal: searchStringsArray\n";
                    echo "   - Include reviews: includeReviews = true\n";
                    echo "   - Máximo reviews: maxReviews\n\n";
                    
                    echo "💡 PRÓXIMOS PASOS:\n";
                    echo "1. Actualizar apify-config.php para usar este actor\n";
                    echo "2. Corregir los Google Place IDs en la base de datos\n";
                    echo "3. Configurar extracción automática\n";
                    echo "4. Limpiar reseñas demo\n";
                    
                } else {
                    echo "⚠️  Hotel encontrado pero sin reviews\n";
                    echo "Estructura completa:\n";
                    print_r($result);
                }
            }
        } else {
            echo "❌ Respuesta inválida\n";
            echo "Response: " . substr($response, 0, 500) . "...\n";
        }
    } else {
        echo "❌ Sin respuesta\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";
?>