<?php
/**
 * ==========================================================================
 * OBTENER ESQUEMA DEL ACTOR APIFY
 * Para conocer los parámetros correctos que acepta
 * ==========================================================================
 */

echo "=== OBTENER ESQUEMA DEL ACTOR ===\n\n";

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
    
    $actorId = 'tri_angle~hotel-review-aggregator';
    
    echo "🎭 Obteniendo información completa del actor: {$actorId}\n\n";
    
    // Obtener información completa del actor incluyendo input schema
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
    
    echo "HTTP Code: {$httpCode}\n\n";
    
    if ($httpCode === 200 && $response) {
        $actorData = json_decode($response, true);
        
        if ($actorData && isset($actorData['data'])) {
            $actor = $actorData['data'];
            
            echo "✅ INFORMACIÓN DEL ACTOR:\n";
            echo "   - Nombre: " . ($actor['name'] ?? 'N/A') . "\n";
            echo "   - Título: " . ($actor['title'] ?? 'N/A') . "\n";
            echo "   - Versión: " . ($actor['taggedBuilds']['latest']['buildNumber'] ?? 'N/A') . "\n";
            echo "   - Última actualización: " . ($actor['modifiedAt'] ?? 'N/A') . "\n\n";
            
            // Buscar input schema
            if (isset($actor['inputSchema'])) {
                echo "📋 INPUT SCHEMA ENCONTRADO:\n\n";
                
                $schema = $actor['inputSchema'];
                echo "Raw schema:\n";
                echo json_encode($schema, JSON_PRETTY_PRINT) . "\n\n";
                
                // Parsear propiedades
                if (isset($schema['properties'])) {
                    echo "📝 PARÁMETROS DISPONIBLES:\n\n";
                    
                    foreach ($schema['properties'] as $paramName => $paramInfo) {
                        echo "   {$paramName}:\n";
                        echo "     - Tipo: " . ($paramInfo['type'] ?? 'N/A') . "\n";
                        echo "     - Título: " . ($paramInfo['title'] ?? 'N/A') . "\n";
                        echo "     - Descripción: " . ($paramInfo['description'] ?? 'N/A') . "\n";
                        
                        if (isset($paramInfo['enum'])) {
                            echo "     - Valores: " . implode(', ', $paramInfo['enum']) . "\n";
                        }
                        
                        if (isset($paramInfo['default'])) {
                            echo "     - Por defecto: " . json_encode($paramInfo['default']) . "\n";
                        }
                        
                        echo "\n";
                    }
                }
                
                // Buscar parámetros requeridos
                if (isset($schema['required'])) {
                    echo "⚠️  PARÁMETROS REQUERIDOS:\n";
                    foreach ($schema['required'] as $required) {
                        echo "   - {$required}\n";
                    }
                    echo "\n";
                }
                
            } else {
                echo "❌ No se encontró input schema en la respuesta\n";
                echo "Buscando en otras ubicaciones...\n\n";
                
                // Buscar en builds
                if (isset($actor['taggedBuilds']['latest']['inputSchema'])) {
                    echo "📋 Schema encontrado en latest build:\n";
                    $schema = $actor['taggedBuilds']['latest']['inputSchema'];
                    echo json_encode($schema, JSON_PRETTY_PRINT) . "\n\n";
                }
            }
            
            // Mostrar ejemplo de uso si está disponible
            if (isset($actor['exampleInput'])) {
                echo "💡 EJEMPLO DE INPUT:\n";
                echo json_encode($actor['exampleInput'], JSON_PRETTY_PRINT) . "\n\n";
            }
            
            // Mostrar README si tiene información útil
            if (isset($actor['readme'])) {
                echo "📖 README (primeros 500 caracteres):\n";
                echo substr($actor['readme'], 0, 500) . "...\n\n";
            }
            
        } else {
            echo "❌ No se pudo parsear la respuesta del actor\n";
        }
        
    } else {
        echo "❌ Error obteniendo información del actor\n";
        echo "Respuesta: " . substr($response, 0, 500) . "\n";
    }
    
    echo "🔍 INTENTANDO OBTENER RUNS RECIENTES DEL ACTOR:\n\n";
    
    // Obtener runs recientes para ver ejemplos de input
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.apify.com/v2/acts/{$actorId}/runs?limit=5&status=SUCCEEDED",
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
        $runsData = json_decode($response, true);
        
        if ($runsData && isset($runsData['data']['items'])) {
            $runs = $runsData['data']['items'];
            
            echo "📊 Encontrados " . count($runs) . " runs exitosos recientes:\n\n";
            
            foreach ($runs as $i => $run) {
                echo "   Run " . ($i + 1) . " (ID: " . $run['id'] . "):\n";
                echo "   - Status: " . $run['status'] . "\n";
                echo "   - Inicio: " . $run['startedAt'] . "\n";
                
                if (isset($run['input'])) {
                    echo "   - Input usado:\n";
                    echo "     " . json_encode($run['input'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                }
                
                echo "\n";
            }
        } else {
            echo "No se encontraron runs exitosos recientes\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== FIN OBTENCIÓN DE ESQUEMA ===\n";
?>