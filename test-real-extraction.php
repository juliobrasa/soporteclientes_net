<?php
/**
 * ==========================================================================
 * PRUEBA DE EXTRACCIÓN REAL CON APIFY
 * Prueba una extracción pequeña para verificar funcionalidad
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PRUEBA DE EXTRACCIÓN REAL ===\n\n";

try {
    // Obtener primer hotel activo con Google Place ID
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, google_place_id 
        FROM hoteles 
        WHERE activo = 1 
        AND google_place_id IS NOT NULL 
        AND google_place_id != '' 
        LIMIT 1
    ");
    
    $hotel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$hotel) {
        throw new Exception("No hay hoteles activos con Google Place ID");
    }
    
    echo "🏨 Hotel seleccionado: {$hotel['nombre_hotel']}\n";
    echo "📍 Google Place ID: {$hotel['google_place_id']}\n";
    echo "🆔 Hotel ID: {$hotel['id']}\n\n";
    
    // Configuración de prueba (extracción pequeña)
    $config = [
        'hotelId' => $hotel['google_place_id'],
        'hotelName' => $hotel['nombre_hotel'],
        'maxReviews' => 10, // Solo 10 reseñas para probar
        'reviewPlatforms' => ['google'], // Solo Google para prueba rápida
        'reviewLanguages' => ['es', 'en'],
        'reviewDates' => [
            'from' => date('Y-m-d', strtotime('-30 days')),
            'to' => date('Y-m-d')
        ]
    ];
    
    echo "⚙️  CONFIGURACIÓN DE PRUEBA:\n";
    echo "   - Máximo reseñas: 10\n";
    echo "   - Plataforma: Google\n";
    echo "   - Período: Últimos 30 días\n";
    echo "   - Idiomas: español, inglés\n\n";
    
    echo "🚀 Iniciando extracción real...\n";
    
    $apifyClient = new ApifyClient();
    $startTime = time();
    
    // Ejecutar extracción síncrona con timeout de 60 segundos
    $result = $apifyClient->runHotelExtractionSync($config, 60);
    
    $executionTime = time() - $startTime;
    
    echo "✅ Extracción completada en {$executionTime} segundos\n\n";
    
    // Analizar resultados
    echo "📊 RESULTADOS:\n";
    
    if (!$result['success']) {
        throw new Exception("Extracción falló: " . ($result['error'] ?? 'Error desconocido'));
    }
    
    if (isset($result['demo_mode']) && $result['demo_mode']) {
        echo "⚠️  ADVERTENCIA: Extracción en modo demo\n";
        echo "   - Los datos no son reales\n";
        echo "   - Verificar configuración del token\n\n";
    } else {
        echo "✅ Extracción REAL completada\n\n";
    }
    
    $reviews = $result['data'] ?? [];
    $stats = $result['stats'] ?? [];
    
    echo "   - Reseñas extraídas: " . count($reviews) . "\n";
    
    if (isset($stats['platforms'])) {
        echo "   - Plataformas: " . implode(', ', $stats['platforms']) . "\n";
    }
    
    if (isset($stats['avgRating'])) {
        echo "   - Rating promedio: " . $stats['avgRating'] . "\n";
    }
    
    if (isset($stats['executionTime'])) {
        echo "   - Tiempo Apify: " . $stats['executionTime'] . " segundos\n";
    }
    
    echo "\n";
    
    // Mostrar muestra de reseñas
    if (!empty($reviews)) {
        echo "📝 MUESTRA DE RESEÑAS EXTRAÍDAS:\n\n";
        
        foreach (array_slice($reviews, 0, 3) as $i => $review) {
            echo "   Reseña " . ($i + 1) . ":\n";
            echo "   - ID: " . ($review['reviewId'] ?? 'N/A') . "\n";
            echo "   - Autor: " . ($review['reviewerName'] ?? 'N/A') . "\n";
            echo "   - Rating: " . ($review['rating'] ?? 'N/A') . "\n";
            echo "   - Fecha: " . ($review['reviewDate'] ?? 'N/A') . "\n";
            echo "   - Plataforma: " . ($review['platform'] ?? 'N/A') . "\n";
            
            $texto = $review['reviewText'] ?? '';
            if ($texto) {
                echo "   - Texto: " . substr($texto, 0, 100) . "...\n";
            }
            
            echo "\n";
        }
    }
    
    // Verificar si son datos reales o demo
    $realReviews = 0;
    $demoReviews = 0;
    
    foreach ($reviews as $review) {
        if (isset($review['reviewId']) && strpos($review['reviewId'], 'demo_') === 0) {
            $demoReviews++;
        } else {
            $realReviews++;
        }
    }
    
    echo "🔍 ANÁLISIS DE DATOS:\n";
    echo "   - Reseñas reales: {$realReviews}\n";
    echo "   - Reseñas demo: {$demoReviews}\n";
    
    if ($realReviews > 0) {
        echo "   - ✅ Extracción REAL funcionando\n";
    } elseif ($demoReviews > 0) {
        echo "   - ⚠️  Aún en modo demo - verificar actor\n";
    } else {
        echo "   - ❓ Sin reseñas extraídas\n";
    }
    
    echo "\n";
    
    // Estimación de costes
    if (!isset($result['demo_mode']) || !$result['demo_mode']) {
        $estimatedCost = $apifyClient->estimateCost(count($reviews));
        echo "💰 COSTE DE ESTA PRUEBA: ~$" . number_format($estimatedCost, 4) . "\n";
        echo "💡 Coste estimado para 1000 reseñas: ~$1.50\n\n";
    }
    
    echo "✅ PRUEBA COMPLETADA EXITOSAMENTE\n\n";
    
    if ($realReviews > 0) {
        echo "🎉 ¡El sistema está listo para extracciones reales!\n";
        echo "   - Puedes proceder a limpiar datos demo\n";
        echo "   - Configurar extracciones automáticas\n";
        echo "   - Extraer reseñas de todos los hoteles\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR EN LA PRUEBA:\n";
    echo "   - " . $e->getMessage() . "\n\n";
    
    echo "🔧 POSIBLES SOLUCIONES:\n";
    echo "   1. Verificar que el token es correcto\n";
    echo "   2. Verificar que tienes créditos en Apify\n";
    echo "   3. Verificar que el actor existe en tu cuenta\n";
    echo "   4. Revisar los Google Place IDs de los hoteles\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>