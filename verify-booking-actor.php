<?php
/**
 * ==========================================================================
 * VERIFICAR ACTOR CORRECTO DE BOOKING
 * ==========================================================================
 */

echo "=== VERIFICAR BOOKING ACTOR ===\n\n";

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
    
    // IDs posibles del actor de Booking
    $possibleActors = [
        'voyager/booking-reviews-scraper' => 'PbMHke3jW25J6hSOA',
        'PbMHke3jW25J6hSOA' => 'PbMHke3jW25J6hSOA' // Usando el ID directo
    ];
    
    foreach ($possibleActors as $name => $actorId) {
        echo "🔍 Verificando: {$name} (ID: {$actorId})\n";
        
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
                
                echo "✅ ACTOR ENCONTRADO!\n";
                echo "   - Título: " . $actor['title'] . "\n";
                echo "   - Nombre: " . $actor['name'] . "\n";
                echo "   - Descripción: " . substr($actor['description'], 0, 100) . "...\n";
                echo "   - Runs: " . number_format($actor['stats']['totalRuns'] ?? 0) . "\n";
                echo "   - Rating: " . ($actor['stats']['avgRating'] ?? 'N/A') . "/5\n";
                
                // Mostrar esquema de input si está disponible
                $schema = $actor['inputSchema'] ?? null;
                if ($schema && isset($schema['properties'])) {
                    echo "   - Parámetros principales:\n";
                    
                    $keyParams = ['hotelUrls', 'startUrls', 'maxReviews', 'language', 'sort'];
                    foreach ($keyParams as $param) {
                        if (isset($schema['properties'][$param])) {
                            $info = $schema['properties'][$param];
                            echo "     • {$param}: " . ($info['title'] ?? $info['type'] ?? 'N/A') . "\n";
                        }
                    }
                }
                
                echo "\n🎯 ACTOR CORRECTO: {$actorId}\n\n";
                break;
                
            }
        } elseif ($httpCode === 404) {
            echo "❌ No encontrado\n\n";
        } else {
            echo "⚠️  Error HTTP {$httpCode}\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN VERIFICACIÓN ===\n";
?>