<?php
/**
 * ==========================================================================
 * VERIFICAR Y CORREGIR GOOGLE PLACE IDs
 * ==========================================================================
 */

echo "=== VERIFICACIÓN DE GOOGLE PLACE IDs ===\n\n";

try {
    // Obtener hoteles con sus Place IDs
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, google_place_id, activo
        FROM hoteles 
        ORDER BY id
    ");
    
    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "🏨 HOTELES Y SUS PLACE IDs ACTUALES:\n\n";
    
    foreach ($hotels as $hotel) {
        $status = $hotel['activo'] ? '✅ Activo' : '❌ Inactivo';
        $placeId = $hotel['google_place_id'] ?: '❌ Sin Place ID';
        
        echo "ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
        echo "   - Estado: {$status}\n";
        echo "   - Place ID: {$placeId}\n";
        
        // Verificar formato del Place ID
        if ($hotel['google_place_id']) {
            if (preg_match('/^ChIJ[a-zA-Z0-9_-]+$/', $hotel['google_place_id'])) {
                echo "   - Formato: ✅ Válido\n";
                
                // Sugerir URL para verificar
                $mapsUrl = "https://www.google.com/maps/place/?q=place_id:" . $hotel['google_place_id'];
                echo "   - Verificar: {$mapsUrl}\n";
                
            } else {
                echo "   - Formato: ❌ Inválido (no empieza con ChIJ)\n";
            }
        }
        
        echo "\n";
    }
    
    echo "🔍 PLACE IDs DE EJEMPLO PARA HOTELES FAMOSOS:\n\n";
    
    $famousHotels = [
        'Hotel Xcaret (México)' => 'ChIJL7BlcshLYI8RN5PpV2lhOy8',
        'Marriott Cancún' => 'ChIJ7YLxDlQVYI8RN5p9wCEn5R8',
        'Hyatt Zilara Cancún' => 'ChIJm7qSPWsXYI8RhPFhZ9wKLy4',
        'Hard Rock Hotel Cancún' => 'ChIJXcF3OJwYYI8RyKpI2yPHQ5U',
        'Dreams Riviera Cancún' => 'ChIJP7f_2J8YYI8R0GjZhzgPqR4'
    ];
    
    foreach ($famousHotels as $name => $placeId) {
        echo "📍 {$name}\n";
        echo "   Place ID: {$placeId}\n";
        echo "   Verificar: https://www.google.com/maps/place/?q=place_id:{$placeId}\n\n";
    }
    
    echo "🎯 RECOMENDACIONES:\n\n";
    echo "1. VERIFICAR PLACE IDs ACTUALES:\n";
    echo "   - Visita las URLs de arriba para verificar que los hoteles existen\n";
    echo "   - Si la URL no funciona, el Place ID es inválido\n\n";
    
    echo "2. OBTENER PLACE IDs CORRECTOS:\n";
    echo "   a) Ve a Google Maps: https://maps.google.com/\n";
    echo "   b) Busca tu hotel por nombre y ciudad\n";
    echo "   c) Haz clic en el hotel\n";
    echo "   d) Copia la URL (contiene el Place ID)\n";
    echo "   e) O busca 'place_id:' en el código fuente\n\n";
    
    echo "3. HOTELES DE PRUEBA:\n";
    echo "   - Usa los Place IDs de ejemplo para probar\n";
    echo "   - Estos hoteles famosos suelen tener muchas reseñas\n\n";
    
    echo "4. ACTUALIZAR EN LA BASE DE DATOS:\n";
    echo "   - Una vez tengas Place IDs válidos:\n";
    echo "   - UPDATE hoteles SET google_place_id = 'NUEVO_PLACE_ID' WHERE id = X;\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "=== FIN VERIFICACIÓN ===\n";
?>