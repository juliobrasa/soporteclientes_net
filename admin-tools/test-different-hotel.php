<?php
/**
 * ==========================================================================
 * PRUEBA CON DIFERENTES HOTELES Y CONFIGURACIONES
 * ==========================================================================
 */

require_once __DIR__ . '/apify-config.php';

echo "=== PRUEBA CON DIFERENTES CONFIGURACIONES ===\n\n";

try {
    // Obtener todos los hoteles activos
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
        ORDER BY id
        LIMIT 3
    ");
    
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($hotels)) {
        throw new Exception("No hay hoteles activos con Google Place ID");
    }
    
    $apifyClient = new ApifyClient();
    
    // Probar diferentes configuraciones
    $testConfigs = [
        [
            'name' => 'Período amplio - Solo Google',
            'config' => [
                'maxReviews' => 20,
                'reviewPlatforms' => ['google'],
                'reviewLanguages' => ['es', 'en'],
                'reviewDates' => [
                    'from' => date('Y-m-d', strtotime('-365 days')),
                    'to' => date('Y-m-d')
                ]
            ]
        ],
        [
            'name' => 'Múltiples plataformas',
            'config' => [
                'maxReviews' => 15,
                'reviewPlatforms' => ['google', 'booking', 'tripadvisor'],
                'reviewLanguages' => ['es', 'en'],
                'reviewDates' => [
                    'from' => date('Y-m-d', strtotime('-180 days')),
                    'to' => date('Y-m-d')
                ]
            ]
        ],
        [
            'name' => 'Solo Booking',
            'config' => [
                'maxReviews' => 10,
                'reviewPlatforms' => ['booking'],
                'reviewLanguages' => ['es', 'en'],
                'reviewDates' => [
                    'from' => date('Y-m-d', strtotime('-90 days')),
                    'to' => date('Y-m-d')
                ]
            ]
        ]
    ];
    
    foreach ($hotels as $hotel) {
        echo "🏨 PROBANDO HOTEL: {$hotel['nombre_hotel']}\n";
        echo "📍 Place ID: {$hotel['google_place_id']}\n\n";
        
        foreach ($testConfigs as $test) {
            echo "⚙️  CONFIGURACIÓN: {$test['name']}\n";
            
            $config = array_merge($test['config'], [
                'hotelId' => $hotel['google_place_id'],
                'hotelName' => $hotel['nombre_hotel']
            ]);
            
            echo "   - Plataformas: " . implode(', ', $config['reviewPlatforms']) . "\n";
            echo "   - Período: {$config['reviewDates']['from']} a {$config['reviewDates']['to']}\n";
            echo "   - Máximo: {$config['maxReviews']} reseñas\n";
            
            $startTime = time();
            $result = $apifyClient->runHotelExtractionSync($config, 45); // 45 segundos timeout
            $executionTime = time() - $startTime;
            
            echo "   - Tiempo: {$executionTime} segundos\n";
            
            if (isset($result['success']) && $result['success']) {
                $reviewCount = isset($result['data']) ? count($result['data']) : 0;
                echo "   - ✅ Resultado: {$reviewCount} reseñas extraídas\n";
                
                if ($reviewCount > 0) {
                    echo "   - 🎉 ¡ÉXITO! Encontramos reseñas reales\n";
                    
                    // Mostrar muestra
                    $sample = array_slice($result['data'], 0, 2);
                    foreach ($sample as $i => $review) {
                        echo "      Reseña " . ($i + 1) . ":\n";
                        echo "      - ID: " . ($review['id'] ?? $review['reviewId'] ?? 'N/A') . "\n";
                        echo "      - Autor: " . ($review['reviewerName'] ?? $review['author'] ?? 'N/A') . "\n";
                        echo "      - Rating: " . ($review['rating'] ?? 'N/A') . "\n";
                        echo "      - Plataforma: " . ($review['platform'] ?? $review['source'] ?? 'N/A') . "\n";
                        echo "\n";
                    }
                } else {
                    echo "   - ⚠️  Sin reseñas encontradas\n";
                }
            } else {
                $error = $result['error'] ?? 'Error desconocido';
                echo "   - ❌ Error: {$error}\n";
            }
            
            echo "\n";
            
            // Pausa entre tests
            sleep(2);
        }
        
        echo "─────────────────────────────────────────\n\n";
        
        // Solo probar 1 hotel por ahora para no gastar créditos
        break;
    }
    
    echo "🎯 RECOMENDACIONES:\n";
    echo "1. Si todas las pruebas devuelven 0 reseñas:\n";
    echo "   - Verificar Google Place IDs en Google Maps\n";
    echo "   - El hotel puede no tener reseñas públicas\n";
    echo "   - Intentar con hoteles más conocidos\n\n";
    
    echo "2. Si alguna configuración funcionó:\n";
    echo "   - Usar esa configuración para todos los hoteles\n";
    echo "   - Configurar extracción automática\n";
    echo "   - Limpiar reseñas demo\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR EN LAS PRUEBAS:\n";
    echo "   - " . $e->getMessage() . "\n\n";
}

echo "=== FIN DE PRUEBAS ===\n";
?>