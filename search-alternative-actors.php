<?php
/**
 * ==========================================================================
 * BUSCAR ACTORES ALTERNATIVOS PARA EXTRAER RESEÑAS DE HOTELES
 * ==========================================================================
 */

echo "=== BUSCAR ACTORES ALTERNATIVOS ===\n\n";

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
    
    // Búsquedas relevantes para hotel reviews
    $searches = [
        'hotel reviews',
        'google maps reviews',
        'tripadvisor scraper',
        'booking reviews',
        'google places',
        'hotel scraper'
    ];
    
    $allActors = [];
    
    foreach ($searches as $search) {
        echo "🔍 Buscando: '{$search}'\n";
        
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.apify.com/v2/store?search=" . urlencode($search) . "&limit=10",
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
            $data = json_decode($response, true);
            if ($data && isset($data['data']['items'])) {
                $actors = $data['data']['items'];
                
                foreach ($actors as $actor) {
                    $actorId = $actor['id'];
                    if (!isset($allActors[$actorId])) {
                        $allActors[$actorId] = $actor;
                    }
                }
                
                echo "   - Encontrados " . count($actors) . " actores\n";
            }
        }
        
        sleep(1); // Pausa entre búsquedas
    }
    
    echo "\n📋 ACTORES ENCONTRADOS PARA RESEÑAS DE HOTELES:\n\n";
    
    $relevantActors = [];
    
    foreach ($allActors as $actor) {
        $title = strtolower($actor['title'] ?? '');
        $description = strtolower($actor['description'] ?? '');
        
        // Filtrar solo los más relevantes
        $hotelKeywords = ['hotel', 'review', 'tripadvisor', 'booking', 'google maps', 'places', 'accommodation'];
        $isRelevant = false;
        
        foreach ($hotelKeywords as $keyword) {
            if (strpos($title, $keyword) !== false || strpos($description, $keyword) !== false) {
                $isRelevant = true;
                break;
            }
        }
        
        if ($isRelevant) {
            $relevantActors[] = $actor;
        }
    }
    
    // Ordenar por rating y número de runs
    usort($relevantActors, function($a, $b) {
        $scoreA = ($a['stats']['totalRuns'] ?? 0) * 10 + ($a['stats']['avgRating'] ?? 0);
        $scoreB = ($b['stats']['totalRuns'] ?? 0) * 10 + ($b['stats']['avgRating'] ?? 0);
        return $scoreB - $scoreA;
    });
    
    echo "🏆 TOP ACTORES RELEVANTES (ordenados por popularidad):\n\n";
    
    foreach (array_slice($relevantActors, 0, 10) as $i => $actor) {
        echo ($i + 1) . ". {$actor['title']}\n";
        echo "   ID: {$actor['id']}\n";
        echo "   Descripción: " . substr($actor['description'] ?? 'N/A', 0, 100) . "...\n";
        echo "   Rating: " . ($actor['stats']['avgRating'] ?? 'N/A') . "/5\n";
        echo "   Runs: " . number_format($actor['stats']['totalRuns'] ?? 0) . "\n";
        echo "   Precio: " . ($actor['pricingType'] ?? 'N/A') . "\n";
        echo "   Autor: " . ($actor['username'] ?? 'N/A') . "\n";
        echo "   URL: https://apify.com/" . ($actor['username'] ?? '') . "/" . ($actor['name'] ?? '') . "\n";
        echo "\n";
    }
    
    echo "🧪 RECOMENDACIONES DE ACTORES PARA PROBAR:\n\n";
    
    $recommendations = [
        'drobnikj/tripadvisor-reviews-scraper',
        'compass/booking-reviews-scraper', 
        'maxcopell/google-maps-reviews-scraper',
        'dtrungtin/google-maps-scraper',
        'compass/google-maps-scraper'
    ];
    
    foreach ($recommendations as $actorId) {
        echo "📍 Actor recomendado: {$actorId}\n";
        
        // Verificar si existe
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
        
        if ($httpCode === 200) {
            $actorData = json_decode($response, true);
            if ($actorData && isset($actorData['data'])) {
                $actor = $actorData['data'];
                echo "   ✅ Existe - " . ($actor['title'] ?? 'N/A') . "\n";
                echo "   Descripción: " . substr($actor['description'] ?? 'N/A', 0, 80) . "...\n";
                echo "   Runs: " . number_format($actor['stats']['totalRuns'] ?? 0) . "\n";
            }
        } elseif ($httpCode === 404) {
            echo "   ❌ No encontrado\n";
        } else {
            echo "   ⚠️  Error verificando (HTTP {$httpCode})\n";
        }
        
        echo "\n";
    }
    
    echo "💡 PRÓXIMOS PASOS RECOMENDADOS:\n";
    echo "1. Probar actor 'drobnikj/tripadvisor-reviews-scraper' para TripAdvisor\n";
    echo "2. Probar actor 'maxcopell/google-maps-reviews-scraper' para Google\n";
    echo "3. Probar actor 'compass/booking-reviews-scraper' para Booking\n";
    echo "4. Usar múltiples actores especializados en lugar de uno genérico\n";
    echo "5. Configurar sistema para combinar resultados de múltiples fuentes\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN BÚSQUEDA ===\n";
?>