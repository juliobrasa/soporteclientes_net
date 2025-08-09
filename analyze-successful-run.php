<?php
/**
 * ==========================================================================
 * ANALIZAR RUN EXITOSO DEL ACTOR
 * Para conocer el formato de input que funciona
 * ==========================================================================
 */

echo "=== ANALIZAR RUN EXITOSO ===\n\n";

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
        throw new Exception("Token de Apify no encontrado");
    }
    
    // Analizar el run más reciente exitoso
    $runId = 'IvMYatWJdVjqDhqzu'; // El más reciente de la lista
    
    echo "🔍 Analizando run exitoso: {$runId}\n\n";
    
    // Obtener detalles completos del run
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.apify.com/v2/actor-runs/{$runId}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/json'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    echo "HTTP Code: {$httpCode}\n\n";
    
    if ($httpCode === 200 && $response) {
        $runData = json_decode($response, true);
        
        if ($runData && isset($runData['data'])) {
            $run = $runData['data'];
            
            echo "✅ DETALLES DEL RUN:\n";
            echo "   - ID: " . $run['id'] . "\n";
            echo "   - Status: " . $run['status'] . "\n";
            echo "   - Inicio: " . $run['startedAt'] . "\n";
            echo "   - Fin: " . ($run['finishedAt'] ?? 'N/A') . "\n";
            echo "   - Duración: " . ($run['stats']['runTimeSecs'] ?? 'N/A') . " segundos\n";
            echo "   - Dataset ID: " . ($run['defaultDatasetId'] ?? 'N/A') . "\n\n";
            
            // Mostrar input usado
            if (isset($run['input'])) {
                echo "📥 INPUT USADO (formato correcto):\n";
                echo json_encode($run['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
                
                // Analizar los parámetros
                $input = $run['input'];
                echo "🔧 ANÁLISIS DE PARÁMETROS:\n";
                
                foreach ($input as $key => $value) {
                    echo "   {$key}: ";
                    if (is_array($value)) {
                        echo "Array con " . count($value) . " elementos\n";
                        if (!empty($value)) {
                            echo "      Ejemplo: " . json_encode(array_slice($value, 0, 2)) . "\n";
                        }
                    } else {
                        echo $value . "\n";
                    }
                }
                echo "\n";
                
            } else {
                echo "❌ No se encontró input en el run\n";
            }
            
            // Obtener resultados del run
            $datasetId = $run['defaultDatasetId'] ?? null;
            if ($datasetId) {
                echo "📊 OBTENIENDO RESULTADOS DEL RUN:\n";
                
                $curl = curl_init();
                curl_setopt_array($curl, [
                    CURLOPT_URL => "https://api.apify.com/v2/datasets/{$datasetId}/items?limit=3",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $token,
                        'Content-Type: application/json'
                    ]
                ]);
                
                $resultsResponse = curl_exec($curl);
                $resultsHttpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
                curl_close($curl);
                
                echo "Results HTTP Code: {$resultsHttpCode}\n";
                
                if ($resultsHttpCode === 200 && $resultsResponse) {
                    $results = json_decode($resultsResponse, true);
                    
                    if ($results && is_array($results)) {
                        echo "✅ Resultados obtenidos: " . count($results) . " items\n\n";
                        
                        if (!empty($results)) {
                            echo "📝 MUESTRA DE RESULTADOS (formato de salida):\n";
                            
                            $sample = $results[0];
                            echo "Estructura del primer resultado:\n";
                            foreach ($sample as $field => $value) {
                                echo "   {$field}: " . (is_string($value) ? substr($value, 0, 50) . "..." : json_encode($value)) . "\n";
                            }
                            echo "\n";
                            
                            echo "Primer resultado completo:\n";
                            echo json_encode($sample, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
                        }
                    }
                } else {
                    echo "❌ No se pudieron obtener resultados\n";
                    echo "Response: " . substr($resultsResponse, 0, 200) . "\n";
                }
            }
            
        } else {
            echo "❌ No se pudo parsear la respuesta del run\n";
        }
        
    } else {
        echo "❌ Error obteniendo detalles del run\n";
        echo "Respuesta: " . substr($response, 0, 500) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN ANÁLISIS ===\n";
?>