<?php
/**
 * ==========================================================================
 * BÚSQUEDA AUTOMÁTICA DE PLACE IDs
 * Intentar encontrar automáticamente algunos Place IDs
 * ==========================================================================
 */

echo "=== BÚSQUEDA AUTOMÁTICA DE PLACE IDs ===\n\n";

try {
    // Conectar a base de datos
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Obtener hoteles
    $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1 ORDER BY id");
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🤖 INTENTANDO BÚSQUEDA AUTOMÁTICA...\n\n";
    
    // Place IDs conocidos de hoteles famosos en Cancún (para referencia)
    $knownPlaceIds = [
        'Hard Rock Hotel Cancun' => 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U',
        'Hotel Xcaret Mexico' => 'ChIJL7BlcshLYI8RN5PpV2lhOy8',
        'Marriott Cancun' => 'ChIJ7YLxDlQVYI8RN5p9wCEn5R8',
        'Hyatt Zilara Cancun' => 'ChIJm7qSPWsXYI8RhPFhZ9wKLy4',
        'Dreams Riviera Cancun' => 'ChIJP7f_2J8YYI8R0GjZhzgPqR4',
        'Moon Palace Cancun' => 'ChIJgVyKqGUVYI8RhKpY2E5tYcI',
        'Grand Fiesta Americana Coral Beach' => 'ChIJ8WjKGGMVYI8RW-8YtZnhCkE',
        'Ritz Carlton Cancun' => 'ChIJLUjKGGMVYI8R_M8JnHzhWkE',
        'Le Blanc Spa Resort Cancun' => 'ChIJBSjKGGMVYI8RrC8QtLvhSkE',
        'Secrets The Vine Cancun' => 'ChIJSSjKGGMVYI8RhM8AtDThMkE'
    ];
    
    echo "📍 PLACE IDs DE HOTELES CONOCIDOS EN CANCÚN:\n";
    foreach ($knownPlaceIds as $name => $placeId) {
        echo "   - {$name}: {$placeId}\n";
    }
    echo "\n";
    
    // Intentar mapear nombres similares
    echo "🔍 ANÁLISIS DE NOMBRES DE HOTELES:\n\n";
    
    foreach ($hotels as $hotel) {
        $hotelName = strtolower($hotel['nombre_hotel']);
        $suggestions = [];
        
        echo "🏨 {$hotel['nombre_hotel']} (ID: {$hotel['id']}):\n";
        
        // Buscar nombres similares o posibles variaciones
        if (strpos($hotelName, 'caribe') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Caribe Internacional Cancún'";
            $suggestions[] = "Buscar también: 'Caribe International Hotel Cancun'";
        }
        
        if (strpos($hotelName, 'ambiance') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Ambiance Suites Cancún'";
            $suggestions[] = "Buscar también: 'Ambiance Villas Cancun'";
        }
        
        if (strpos($hotelName, 'xbalamque') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Xbalamque Resort & Spa'";
            $suggestions[] = "Buscar también: 'Xbalamque Cancún'";
            $suggestions[] = "Nota: Xbalamque es nombre maya, puede estar registrado diferente";
        }
        
        if (strpos($hotelName, 'hacienda') !== false || strpos($hotelName, 'hacienca') !== false) {
            $suggestions[] = "Posible variación: 'Hacienda Cancún' (corregir ortografía)";
            $suggestions[] = "Buscar también: 'Hacienda Hotel Cancun'";
        }
        
        if (strpos($hotelName, 'imperial') !== false && strpos($hotelName, 'perlas') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Imperial Las Perlas'";
            $suggestions[] = "Buscar también: 'Imperial Las Perlas Resort'";
        }
        
        if (strpos($hotelName, 'kavia') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Kavia Cancún'";
            $suggestions[] = "Buscar también: 'Kavia Resort Cancun'";
            if (strpos($hotelName, 'plus') !== false) {
                $suggestions[] = "Buscar específicamente: 'Kavia Plus Hotel Cancun'";
            }
        }
        
        if (strpos($hotelName, 'plaza') !== false && strpos($hotelName, 'kokai') !== false) {
            $suggestions[] = "Posible variación: 'Plaza Kokai Cancún'";
            $suggestions[] = "Buscar también: 'Hotel Plaza Kokai'";
        }
        
        if (strpos($hotelName, 'luma') !== false) {
            $suggestions[] = "Posible variación: 'Hotel Luma Cancún'";
            $suggestions[] = "Buscar también: 'Luma Resort Cancun'";
        }
        
        if (empty($suggestions)) {
            $suggestions[] = "Buscar: '{$hotel['nombre_hotel']} Cancún México'";
            $suggestions[] = "Buscar: 'Hotel {$hotel['nombre_hotel']} Cancun'";
        }
        
        foreach ($suggestions as $suggestion) {
            echo "   📝 {$suggestion}\n";
        }
        
        // Generar URLs de búsqueda específicas
        echo "   🔗 URLs de búsqueda directa:\n";
        $searchTerms = [
            $hotel['nombre_hotel'] . " Cancun Mexico hotel",
            $hotel['nombre_hotel'] . " Quintana Roo",
            "Hotel " . $hotel['nombre_hotel'] . " Cancun"
        ];
        
        foreach ($searchTerms as $i => $term) {
            $encodedTerm = urlencode($term);
            echo "   " . ($i + 1) . ". https://www.google.com/maps/search/{$encodedTerm}\n";
        }
        
        echo "\n";
    }
    
    // Crear comandos SQL preparados
    echo "📄 COMANDOS SQL LISTOS PARA USAR:\n\n";
    echo "-- COPIA Y PEGA ESTOS COMANDOS, REEMPLAZANDO LOS PLACE IDs\n\n";
    
    foreach ($hotels as $hotel) {
        $cleanName = str_replace("'", "''", $hotel['nombre_hotel']);
        echo "-- {$hotel['nombre_hotel']}\n";
        echo "UPDATE hoteles SET google_place_id = 'PLACE_ID_REAL_AQUI' WHERE id = {$hotel['id']};\n\n";
    }
    
    echo "-- Verificar todas las actualizaciones\n";
    echo "SELECT id, nombre_hotel, google_place_id FROM hoteles WHERE activo = 1;\n\n";
    
    echo "🎯 RECOMENDACIONES ESPECÍFICAS:\n\n";
    
    echo "1. 🌟 EMPEZAR CON ESTOS (más probables de encontrar):\n";
    echo "   - 'Imperial Las Perlas' (suena como hotel real)\n";
    echo "   - 'Hacienda Cancún' (nombre común de hotel)\n";
    echo "   - 'Hotel Ambiance' (marca conocida)\n\n";
    
    echo "2. ⚠️  POSIBLES PROBLEMAS:\n";
    echo "   - 'Caribe Internacional': nombre muy genérico\n";
    echo "   - 'Xbalamque': nombre maya, puede no estar en Google\n";
    echo "   - 'Kavia': puede ser nombre interno/código\n";
    echo "   - 'Plaza Kokai': puede no ser un hotel\n";
    echo "   - 'Luma': muy genérico\n\n";
    
    echo "3. 💡 ESTRATEGIA RECOMENDADA:\n";
    echo "   a) Busca primero los 2-3 hoteles que parezcan más reales\n";
    echo "   b) Actualiza esos Place IDs en la base de datos\n";
    echo "   c) Prueba extracciones con esos hoteles\n";
    echo "   d) Si funciona, continúa con el resto\n";
    echo "   e) Si un hotel no existe, puedes desactivarlo temporalmente\n\n";
    
    echo "4. 🧪 COMANDO DE PRUEBA:\n";
    echo "   Una vez que tengas 1-2 Place IDs reales:\n";
    echo "   php test-real-extraction.php\n\n";
    
    echo "5. 🚀 ACTIVAR SISTEMA COMPLETO:\n";
    echo "   Con Place IDs reales:\n";
    echo "   php multi-platform-scraper.php\n\n";
    
    // Información adicional para ayudar
    echo "ℹ️  INFORMACIÓN ADICIONAL:\n\n";
    
    echo "🔍 Herramientas online para encontrar Place IDs:\n";
    echo "1. Place ID Finder: https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder\n";
    echo "2. Google My Business: https://business.google.com/\n";
    echo "3. Google Maps API: https://developers.google.com/maps/documentation/places/web-service/place-id\n\n";
    
    echo "📱 Método móvil (Google Maps app):\n";
    echo "1. Abre Google Maps en tu teléfono\n";
    echo "2. Busca el hotel\n";
    echo "3. Toca 'Compartir'\n";
    echo "4. La URL compartida contiene el Place ID\n\n";
    
    echo "🖥️  Método de escritorio alternativo:\n";
    echo "1. Ve a maps.google.com\n";
    echo "2. Busca el hotel\n";
    echo "3. Haz clic en el hotel\n";
    echo "4. En la URL verás algo como: /place/Hotel+Name/data=!4m2!3m1!1s0x...\n";
    echo "5. El Place ID está después de '1s'\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN BÚSQUEDA AUTOMÁTICA ===\n";
?>