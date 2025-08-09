<?php
/**
 * ==========================================================================
 * DEBUG DETALLADO DE LLAMADAS A APIFY
 * Diagnóstico completo de la API de Apify
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== DEBUG DETALLADO DE APIFY ===\n\n";

try {
    $apifyClient = new ApifyClient();
    
    // 1. Verificar información básica
    echo "📊 INFORMACIÓN DEL CLIENTE:\n";
    $debugInfo = $apifyClient->getDebugInfo();
    print_r($debugInfo);
    echo "\n";
    
    // 2. Hacer una llamada directa a la API para verificar token
    echo "🔑 VERIFICANDO TOKEN CON API DIRECTA:\n";
    
    $token = $_ENV['APIFY_API_TOKEN'] ?? null;
    if (!$token) {
        // Cargar .env manualmente si no está cargado
        $envFile = __DIR__ . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, 'APIFY_API_TOKEN=') === 0) {
                    $token = substr($line, strlen('APIFY_API_TOKEN='));
                    $_ENV['APIFY_API_TOKEN'] = $token;
                    break;
                }
            }
        }
    }
    
    echo "Token: " . substr($token, 0, 20) . "...\n";
    
    // Verificar token con API directa
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://api.apify.com/v2/users/me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200) {
        $userData = json_decode($response, true);
        echo "✅ Token válido\n";
        echo "Usuario: " . ($userData['data']['username'] ?? 'N/A') . "\n";
        echo "Email: " . ($userData['data']['email'] ?? 'N/A') . "\n";
    } elseif ($httpCode === 401) {
        echo "❌ Token inválido o expirado\n";
        echo "Respuesta: {$response}\n";
        exit(1);
    } else {
        echo "⚠️  Respuesta inesperada\n";
        echo "Respuesta: {$response}\n";
    }
    
    echo "\n";
    
    // 3. Verificar el actor específico
    echo "🎭 VERIFICANDO ACTOR ESPECÍFICO:\n";
    
    $actorId = 'tri_angle~hotel-review-aggregator';
    echo "Actor ID: {$actorId}\n";
    
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
    
    echo "HTTP Code: {$httpCode}\n";
    
    if ($httpCode === 200) {
        $actorData = json_decode($response, true);
        echo "✅ Actor encontrado\n";
        echo "Nombre: " . ($actorData['data']['name'] ?? 'N/A') . "\n";
        echo "Título: " . ($actorData['data']['title'] ?? 'N/A') . "\n";
        echo "Descripción: " . substr($actorData['data']['description'] ?? 'N/A', 0, 100) . "...\n";
    } elseif ($httpCode === 404) {
        echo "❌ Actor no encontrado\n";
        echo "El actor 'tri_angle~hotel-review-aggregator' no existe o no tienes acceso\n";
        
        // Buscar actores disponibles
        echo "\n🔍 BUSCANDO ACTORES DISPONIBLES:\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.apify.com/v2/acts?my=true&limit=10',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($httpCode === 200) {
            $actorsData = json_decode($response, true);
            $actors = $actorsData['data']['items'] ?? [];
            
            echo "Tus actores disponibles:\n";
            foreach ($actors as $actor) {
                echo "- " . $actor['id'] . " (" . $actor['title'] . ")\n";
            }
            
            if (empty($actors)) {
                echo "No tienes actores propios. Buscando actores públicos relacionados...\n";
                
                // Buscar actores públicos relacionados con hotel/reviews
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => 'https://api.apify.com/v2/store?search=hotel%20reviews&limit=5',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $token,
                        'Content-Type: application/json'
                    ]
                ]);
                
                $response = curl_exec($curl);
                $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                if ($httpCode === 200) {
                    $storeData = json_decode($response, true);
                    $storeActors = $storeData['data']['items'] ?? [];
                    
                    echo "\nActores públicos relacionados:\n";
                    foreach ($storeActors as $actor) {
                        echo "- " . $actor['id'] . " (" . $actor['title'] . ")\n";
                        echo "  Descripción: " . substr($actor['description'] ?? '', 0, 80) . "...\n";
                    }
                }
            }
        }
        
        exit(1);
    } else {
        echo "⚠️  Error verificando actor\n";
        echo "Respuesta: {$response}\n";
    }
    
    echo "\n";
    
    // 4. Intentar ejecutar el actor con configuración mínima
    echo "🚀 PROBANDO EJECUCIÓN DEL ACTOR:\n";
    
    $testInput = [
        'hotelId' => 'ChIJ3cWF0FjPTYUR8LcqQNNi-Qw',
        'maxReviews' => 1,
        'reviewPlatforms' => ['google'],
        'test' => true
    ];
    
    echo "Input de prueba:\n";
    print_r($testInput);
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/run-sync?timeout=30",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($testInput),
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
        echo "cURL Error: {$error}\n";
    }
    
    if ($response) {
        echo "Respuesta completa:\n";
        $responseData = json_decode($response, true);
        if ($responseData) {
            print_r($responseData);
            
            // Verificar estructura específica
            if (isset($responseData['data'])) {
                echo "\n🔍 ANÁLISIS DE LA RESPUESTA:\n";
                echo "- Tiene 'data': ✅\n";
                
                if (isset($responseData['data']['status'])) {
                    echo "- Status: " . $responseData['data']['status'] . "\n";
                }
                
                if (isset($responseData['data']['id'])) {
                    echo "- Run ID: " . $responseData['data']['id'] . "\n";
                }
                
                // Si es un run exitoso, intentar obtener resultados
                if (isset($responseData['data']['id']) && isset($responseData['data']['status'])) {
                    $runId = $responseData['data']['id'];
                    $status = $responseData['data']['status'];
                    
                    echo "\n📥 INTENTANDO OBTENER RESULTADOS DEL RUN:\n";
                    echo "Run ID: {$runId}\n";
                    
                    // Intentar obtener dataset items
                    $curl = curl_init();
                    curl_setopt_array($curl, [
                        CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/runs/{$runId}/dataset/items",
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_HTTPHEADER => [
                            'Authorization: Bearer ' . $token,
                            'Content-Type: application/json'
                        ]
                    ]);
                    
                    $datasetResponse = curl_exec($curl);
                    $datasetHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                    curl_close($curl);
                    
                    echo "Dataset HTTP Code: {$datasetHttpCode}\n";
                    
                    if ($datasetHttpCode === 200 && $datasetResponse) {
                        $datasetData = json_decode($datasetResponse, true);
                        if ($datasetData && is_array($datasetData)) {
                            echo "✅ Resultados obtenidos: " . count($datasetData) . " items\n";
                            
                            if (!empty($datasetData)) {
                                echo "\n📝 PRIMERA MUESTRA:\n";
                                print_r(array_slice($datasetData, 0, 1));
                            }
                        }
                    } else {
                        echo "❌ No se pudieron obtener resultados del dataset\n";
                        echo "Respuesta: " . substr($datasetResponse, 0, 200) . "...\n";
                    }
                }
            } else {
                echo "\n❌ Respuesta sin 'data'\n";
            }
        } else {
            echo "❌ JSON inválido\n";
            echo "Raw response: " . substr($response, 0, 1000) . "...\n";
        }
    } else {
        echo "❌ Sin respuesta\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR EN DEBUG:\n";
    echo $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DEBUG ===\n";
?>