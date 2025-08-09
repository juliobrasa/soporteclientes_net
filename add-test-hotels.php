<?php
/**
 * Script para añadir hoteles de prueba cuando no aparecen en el extractor
 */

echo "🏨 CREADOR DE HOTELES DE PRUEBA\n";
echo str_repeat("=", 40) . "\n\n";

// Este script se debe ejecutar después de aplicar las nuevas credenciales de BD
echo "⚠️ INSTRUCCIONES:\n";
echo "1. Primero aplicar las nuevas credenciales de BD:\n";
echo "   cp backup-credentials/.env.local.new .env.local\n\n";

echo "2. Ejecutar script SQL en MySQL:\n";
echo "   mysql -u root -p < backup-credentials/rotation-script-*.sql\n\n";

echo "3. Después ejecutar este script para crear hoteles de prueba\n\n";

// Verificar si las credenciales están actualizadas
if (!file_exists('.env.local')) {
    echo "❌ Archivo .env.local no encontrado\n";
    exit(1);
}

$envContent = file_get_contents('.env.local');
if (strpos($envContent, 'QCF8RhS*}.Oj0u(v') !== false) {
    echo "⚠️ USANDO CREDENCIALES ANTIGUAS\n";
    echo "Las credenciales de BD necesitan rotación por seguridad.\n";
    echo "Para continuar, ejecuta:\n";
    echo "1. mysql -u root -p < backup-credentials/rotation-script-*.sql\n";
    echo "2. cp backup-credentials/.env.local.new .env.local\n";
    echo "3. php add-test-hotels.php\n\n";
    exit(1);
}

try {
    require_once 'env-loader.php';
    $pdo = EnvironmentLoader::createDatabaseConnection();
    echo "✅ Conexión establecida con nuevas credenciales\n\n";
    
    // Verificar si ya hay hoteles
    $stmt = $pdo->query("SELECT COUNT(*) FROM hoteles");
    $count = $stmt->fetchColumn();
    
    if ($count > 0) {
        echo "ℹ️ Ya hay $count hoteles en la BD.\n";
        echo "¿Quieres crear hoteles de prueba adicionales? (y/n): ";
        $input = trim(fgets(STDIN));
        if (strtolower($input) !== 'y') {
            echo "Operación cancelada.\n";
            exit(0);
        }
    }
    
    // Crear hoteles de prueba
    $hoteles = [
        [
            'nombre' => 'Hotel Xcaret México',
            'destino' => 'Playa del Carmen, Riviera Maya',
            'url_booking' => 'https://www.booking.com/hotel/mx/xcaret.html',
            'place_id' => 'ChIJExample1'
        ],
        [
            'nombre' => 'Grand Velas Riviera Maya',
            'destino' => 'Playa del Carmen, México',
            'url_booking' => 'https://www.booking.com/hotel/mx/grand-velas-riviera-maya.html',
            'place_id' => 'ChIJExample2'
        ],
        [
            'nombre' => 'Rosewood Mayakoba',
            'destino' => 'Riviera Maya, México',
            'url_booking' => 'https://www.booking.com/hotel/mx/rosewood-mayakoba.html',
            'place_id' => 'ChIJExample3'
        ],
        [
            'nombre' => 'Hotel Villa Rolandi',
            'destino' => 'Isla Mujeres, México',
            'url_booking' => 'https://www.booking.com/hotel/mx/villa-rolandi.html',
            'place_id' => 'ChIJExample4'
        ],
        [
            'nombre' => 'Secrets Maroma Beach',
            'destino' => 'Riviera Maya, México', 
            'url_booking' => 'https://www.booking.com/hotel/mx/secrets-maroma-beach.html',
            'place_id' => 'ChIJExample5'
        ]
    ];
    
    $stmt = $pdo->prepare("
        INSERT INTO hoteles (nombre_hotel, hoja_destino, url_booking, google_place_id, activo, created_at) 
        VALUES (?, ?, ?, ?, 1, NOW())
    ");
    
    $insertados = 0;
    foreach ($hoteles as $hotel) {
        try {
            $stmt->execute([
                $hotel['nombre'],
                $hotel['destino'], 
                $hotel['url_booking'],
                $hotel['place_id']
            ]);
            $insertados++;
            echo "✅ Creado: {$hotel['nombre']}\n";
        } catch (PDOException $e) {
            echo "⚠️ Error creando {$hotel['nombre']}: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🎉 Hoteles de prueba creados: $insertados\n";
    echo "Ahora deberían aparecer en el panel de extracción.\n\n";
    
    // Verificar que estén activos
    $stmt = $pdo->query("SELECT COUNT(*) FROM hoteles WHERE activo = 1");
    $activos = $stmt->fetchColumn();
    echo "Total hoteles activos: $activos\n";
    
    // Mostrar los primeros hoteles
    $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1 ORDER BY id DESC LIMIT 5");
    $hoteles = $stmt->fetchAll();
    
    echo "\nHoteles disponibles para extracción:\n";
    foreach ($hoteles as $h) {
        echo "  - {$h['id']}: {$h['nombre_hotel']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nPosibles soluciones:\n";
    echo "1. Verificar que las credenciales de BD estén rotadas\n";
    echo "2. Ejecutar el script SQL de rotación\n";
    echo "3. Verificar que el usuario de BD tenga permisos\n";
}
?>