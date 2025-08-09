<?php
/**
 * ==========================================================================
 * PRUEBA CON PLACE ID REAL DE HOTEL FAMOSO
 * Para confirmar que el problema son los Place IDs falsos
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PRUEBA CON PLACE ID REAL ===\n\n";

try {
    echo "🎯 OBJETIVO: Confirmar que el problema son los Place IDs falsos\n";
    echo "📍 Usando Place ID REAL de hotel famoso con muchas reseñas\n\n";
    
    // Usar Place ID real de Hard Rock Hotel Cancún (hotel muy conocido con muchas reseñas)
    $realPlaceId = 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U';
    $hotelName = 'Hard Rock Hotel Cancún (PRUEBA)';
    
    echo "🏨 Hotel de prueba: {$hotelName}\n";
    echo "📍 Place ID real: {$realPlaceId}\n";
    echo "🌐 Verificar en Maps: https://www.google.com/maps/place/?q=place_id:{$realPlaceId}\n\n";
    
    // Configuración muy conservadora para la prueba
    $config = [
        'hotelId' => $realPlaceId,
        'hotelName' => $hotelName,
        'maxReviews' => 5, // Solo 5 reseñas para prueba
        'reviewPlatforms' => ['google'], // Solo Google
        'reviewLanguages' => ['es', 'en'],
        'reviewDates' => [
            'from' => date('Y-m-d', strtotime('-60 days')), // Últimos 60 días
            'to' => date('Y-m-d')
        ]
    ];
    
    echo "⚙️  CONFIGURACIÓN DE PRUEBA:\n";
    echo "   - Máximo reseñas: 5\n";
    echo "   - Plataforma: Solo Google\n";
    echo "   - Período: Últimos 60 días\n";
    echo "   - Hotel: Famoso con muchas reseñas\n\n";
    
    echo "🚀 Iniciando extracción con Place ID REAL...\n";
    
    $apifyClient = new ApifyClient();
    $startTime = time();
    
    // Timeout más largo para dar tiempo a la extracción
    $result = $apifyClient->runHotelExtractionSync($config, 90);
    
    $executionTime = time() - $startTime;
    
    echo "⏱️  Extracción completada en {$executionTime} segundos\n\n";
    
    // Analizar resultados
    echo "📊 RESULTADOS:\n";
    
    if (!$result['success']) {
        throw new Exception("Extracción falló: " . ($result['error'] ?? 'Error desconocido'));
    }
    
    // Verificar si es modo demo
    if (isset($result['demo_mode']) && $result['demo_mode']) {
        echo "❌ SIGUE EN MODO DEMO\n";
        echo "   - El token aún no está funcionando correctamente\n";
        echo "   - Verificar configuración .env\n\n";
        
        $debugInfo = $apifyClient->getDebugInfo();
        echo "Debug info:\n";
        print_r($debugInfo);
        
        exit(1);
    } else {
        echo "✅ Extracción REAL ejecutada\n\n";
    }
    
    $reviews = $result['data'] ?? [];
    $runId = $result['run_id'] ?? 'N/A';
    
    echo "   - Reseñas extraídas: " . count($reviews) . "\n";
    echo "   - Run ID: {$runId}\n";
    echo "   - Tiempo total: {$executionTime} segundos\n\n";
    
    if (count($reviews) > 0) {
        echo "🎉 ¡ÉXITO! El sistema funciona con Place IDs reales\n\n";
        
        echo "📝 MUESTRA DE RESEÑAS EXTRAÍDAS:\n\n";
        
        foreach (array_slice($reviews, 0, 2) as $i => $review) {
            echo "   Reseña " . ($i + 1) . ":\n";
            echo "   - ID: " . ($review['id'] ?? $review['reviewId'] ?? 'N/A') . "\n";
            echo "   - Autor: " . ($review['reviewerName'] ?? $review['author'] ?? 'N/A') . "\n";
            echo "   - Rating: " . ($review['rating'] ?? 'N/A') . "\n";
            echo "   - Fecha: " . ($review['reviewDate'] ?? $review['date'] ?? 'N/A') . "\n";
            echo "   - Plataforma: " . ($review['platform'] ?? $review['source'] ?? 'N/A') . "\n";
            
            $texto = $review['reviewText'] ?? $review['text'] ?? $review['content'] ?? '';
            if ($texto) {
                echo "   - Texto: " . substr($texto, 0, 100) . "...\n";
            }
            
            echo "\n";
        }
        
        echo "✅ CONFIRMADO: El problema son los Place IDs falsos en la base de datos\n\n";
        
        echo "🔧 PRÓXIMOS PASOS:\n";
        echo "1. Obtener Place IDs reales para cada hotel:\n";
        echo "   - Buscar cada hotel en Google Maps\n";
        echo "   - Copiar el Place ID real de la URL\n";
        echo "   - Actualizar la base de datos\n\n";
        
        echo "2. Hoteles que necesitan Place IDs reales:\n";
        
        // Obtener lista de hoteles
        $host = "soporteclientes.net";
        $dbname = "soporteia_bookingkavia";
        $username = "soporteia_admin";
        $password = "QCF8RhS*}.Oj0u(v";
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1 ORDER BY id");
        $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($hotels as $hotel) {
            echo "   - ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
        }
        
        echo "\n3. Comando de actualización (ejemplo):\n";
        echo "   UPDATE hoteles SET google_place_id = 'PLACE_ID_REAL' WHERE id = X;\n\n";
        
        echo "4. Una vez actualizados los Place IDs:\n";
        echo "   - Limpiar reseñas demo de la base de datos\n";
        echo "   - Configurar extracciones automáticas\n";
        echo "   - Extraer reseñas reales de todos los hoteles\n\n";
        
    } else {
        echo "⚠️  No se extrajeron reseñas\n";
        echo "   - Verificar si el hotel tiene reseñas públicas recientes\n";
        echo "   - Intentar con período más amplio\n";
        echo "   - Verificar configuración del actor\n\n";
        
        // Mostrar detalles del resultado para debug
        echo "🔍 DETALLES DEL RESULTADO:\n";
        print_r($result);
    }
    
} catch (Exception $e) {
    echo "❌ ERROR EN LA PRUEBA:\n";
    echo "   - " . $e->getMessage() . "\n\n";
    
    echo "🔧 VERIFICACIONES ADICIONALES:\n";
    echo "   1. Token de Apify correcto y con créditos\n";
    echo "   2. Actor 'tri_angle~hotel-review-aggregator' existe\n";
    echo "   3. Place ID es válido y el hotel tiene reseñas\n";
    echo "   4. Conexión a internet estable\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";
?>