<?php
/**
 * ==========================================================================
 * HERRAMIENTAS PARA ENCONTRAR PLACE IDs REALES DE HOTELES
 * Guía paso a paso para obtener Place IDs válidos
 * ==========================================================================
 */

echo "=== BÚSQUEDA DE PLACE IDs REALES ===\n\n";

try {
    // Conectar a base de datos
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Obtener todos los hoteles activos
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, google_place_id, activo
        FROM hoteles 
        WHERE activo = 1 
        ORDER BY id
    ");
    
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🏨 HOTELES QUE NECESITAN PLACE IDs REALES:\n\n";
    
    foreach ($hotels as $i => $hotel) {
        $num = $i + 1;
        echo "📍 HOTEL {$num}: {$hotel['nombre_hotel']}\n";
        echo "   - ID en BD: {$hotel['id']}\n";
        echo "   - Place ID actual: {$hotel['google_place_id']} (FALSO)\n";
        echo "   - Estado: " . ($hotel['activo'] ? 'Activo' : 'Inactivo') . "\n\n";
        
        // Generar URLs de búsqueda en Google Maps
        $searchQuery = urlencode($hotel['nombre_hotel'] . " Cancún México");
        $googleMapsSearch = "https://www.google.com/maps/search/" . $searchQuery;
        
        echo "   🔍 BÚSQUEDAS SUGERIDAS:\n";
        echo "   1. Google Maps: {$googleMapsSearch}\n";
        
        // Búsquedas alternativas
        $alternativeSearches = [
            $hotel['nombre_hotel'] . " hotel Cancún",
            $hotel['nombre_hotel'] . " Quintana Roo México",
            "Hotel " . $hotel['nombre_hotel'] . " Cancún"
        ];
        
        foreach ($alternativeSearches as $j => $altSearch) {
            $altUrl = "https://www.google.com/maps/search/" . urlencode($altSearch);
            echo "   " . ($j + 2) . ". Alternativa: {$altUrl}\n";
        }
        
        echo "\n   📋 INSTRUCCIONES:\n";
        echo "   1. Haz clic en una de las URLs de arriba\n";
        echo "   2. Busca el hotel correcto en los resultados\n";
        echo "   3. Haz clic en el hotel para abrir su ficha\n";
        echo "   4. Copia la URL de la barra de direcciones\n";
        echo "   5. Busca el Place ID en la URL (formato: ChIJ...)\n";
        echo "   6. Actualiza con el comando SQL de abajo\n\n";
        
        echo "   🔧 COMANDO SQL PARA ACTUALIZAR:\n";
        echo "   UPDATE hoteles SET google_place_id = 'NUEVO_PLACE_ID_AQUI' WHERE id = {$hotel['id']};\n";
        
        echo "\n" . str_repeat("─", 80) . "\n\n";
    }
    
    echo "🎯 GUÍA DETALLADA PARA OBTENER PLACE IDs:\n\n";
    
    echo "📚 MÉTODO 1 - DESDE GOOGLE MAPS (RECOMENDADO):\n";
    echo "1. Ve a https://www.google.com/maps/\n";
    echo "2. Busca: 'Hotel [NOMBRE] Cancún México'\n";
    echo "3. Haz clic en el resultado correcto\n";
    echo "4. Copia la URL completa (será algo como maps.google.com/maps/place/...)\n";
    echo "5. El Place ID está en la URL o usa el Método 2\n\n";
    
    echo "📚 MÉTODO 2 - EXTRAER PLACE ID DE LA URL:\n";
    echo "Si la URL es como: https://www.google.com/maps/place/Hotel+Name/@21.123,-86.456,17z/data=!3m1!4b1!4m6!3m5!1s0x8f4c123456789abc:0x1234567890abcdef!8m2!3d21.123!4d-86.456!16s%2Fg%2F11abc123def\n";
    echo "El Place ID es la parte después de '1s': 0x8f4c123456789abc:0x1234567890abcdef\n";
    echo "Pero necesitamos convertirlo. Es más fácil usar el Método 3.\n\n";
    
    echo "📚 MÉTODO 3 - USANDO PLACE ID FINDER:\n";
    echo "1. Ve a: https://developers.google.com/maps/documentation/places/web-service/place-id\n";
    echo "2. O usa: https://developers.google.com/maps/documentation/javascript/examples/places-placeid-finder\n";
    echo "3. Busca tu hotel y obtendrás directamente el Place ID en formato ChIJ...\n\n";
    
    echo "📚 MÉTODO 4 - DESDE URL DE GOOGLE MAPS:\n";
    echo "1. Abre Google Maps y busca tu hotel\n";
    echo "2. Haz clic derecho en el marcador del hotel\n";
    echo "3. Selecciona 'What's here?' o '¿Qué hay aquí?'\n";
    echo "4. Aparecerá el Place ID en la parte inferior\n\n";
    
    echo "✅ EJEMPLOS DE PLACE IDs VÁLIDOS:\n";
    echo "   - Hard Rock Hotel Cancún: ChIJXcF3OJwYYI8RyKpI2yPHQ5U\n";
    echo "   - Hotel Xcaret México: ChIJL7BlcshLYI8RN5PpV2lhOy8\n";
    echo "   - Marriott Cancún: ChIJ7YLxDlQVYI8RN5p9wCEn5R8\n";
    echo "   - Hyatt Zilara Cancún: ChIJm7qSPWsXYI8RhPFhZ9wKLy4\n\n";
    
    echo "❌ PLACE IDs FALSOS (NO USAR):\n";
    echo "   - ChIJkav1FjPTYURcancunKAV8-M (contiene el nombre del hotel)\n";
    echo "   - ChIJkavplus1FjPTYURplusKav-N (demasiado simple)\n";
    echo "   - ChIJlum1FjPTYURLumahotelcun-P (nombre incluido)\n\n";
    
    // Crear script SQL automático
    echo "📄 SCRIPT SQL PARA COPIAR Y PEGAR:\n\n";
    echo "-- Actualizar Place IDs reales (REEMPLAZA LOS PLACE_ID_X con los reales)\n\n";
    
    foreach ($hotels as $hotel) {
        echo "UPDATE hoteles SET google_place_id = 'PLACE_ID_" . $hotel['id'] . "_AQUI' WHERE id = {$hotel['id']}; -- {$hotel['nombre_hotel']}\n";
    }
    
    echo "\n-- Verificar actualizaciones\n";
    echo "SELECT id, nombre_hotel, google_place_id FROM hoteles WHERE activo = 1 ORDER BY id;\n\n";
    
    echo "🔍 VERIFICAR PLACE IDs (SCRIPT AUTOMÁTICO):\n";
    echo "Una vez que actualices los Place IDs, ejecuta:\n";
    echo "php verify-place-ids.php\n\n";
    
    echo "🚀 INICIAR EXTRACCIONES:\n";
    echo "Cuando tengas Place IDs reales, ejecuta:\n";
    echo "php multi-platform-scraper.php\n\n";
    
    echo "💡 CONSEJOS:\n";
    echo "- Busca primero los hoteles más grandes/conocidos\n";
    echo "- Si un hotel no aparece en Google Maps, puede que no esté registrado\n";
    echo "- Los Place IDs reales SIEMPRE empiezan con ChIJ y son muy largos\n";
    echo "- Nunca contienen el nombre del hotel en forma legible\n";
    echo "- Puedes probar con 1-2 hoteles primero antes de hacer todos\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN BÚSQUEDA PLACE IDs ===\n";
?>