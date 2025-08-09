<?php
require_once 'admin-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Error conectando a la base de datos");
    }
    
    echo "=== ESTRUCTURA DE TABLAS ===\n\n";
    
    // Verificar tabla extraction_jobs
    echo "Tabla extraction_jobs:\n";
    $stmt = $pdo->query("DESCRIBE extraction_jobs");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    echo "\n";
    
    // Verificar si existe columna platform
    $hasPlatform = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'platform') {
            $hasplatform = true;
            break;
        }
    }
    
    if (!$hasplatform) {
        echo "❌ La columna 'platform' no existe. Creándola...\n";
        $pdo->exec("ALTER TABLE extraction_jobs ADD COLUMN platform VARCHAR(50) DEFAULT 'general' AFTER hotel_id");
        echo "✅ Columna 'platform' creada correctamente\n\n";
    } else {
        echo "✅ La columna 'platform' ya existe\n\n";
    }
    
    // Verificar si existe columna apify_run_id
    $hasApifyRunId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'apify_run_id') {
            $hasApifyRunId = true;
            break;
        }
    }
    
    if (!$hasApifyRunId) {
        echo "❌ La columna 'apify_run_id' no existe. Creándola...\n";
        $pdo->exec("ALTER TABLE extraction_jobs ADD COLUMN apify_run_id VARCHAR(100) NULL AFTER platform");
        echo "✅ Columna 'apify_run_id' creada correctamente\n\n";
    } else {
        echo "✅ La columna 'apify_run_id' ya existe\n\n";
    }
    
    // Verificar tabla hoteles
    echo "Verificando URLs de Booking en hoteles:\n";
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_hoteles,
            COUNT(CASE WHEN booking_url IS NOT NULL AND booking_url != '' THEN 1 END) as con_booking_url,
            COUNT(CASE WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 END) as con_google_place_id
        FROM hoteles WHERE activo = 1
    ");
    $stats = $stmt->fetch();
    
    echo "- Total hoteles activos: {$stats['total_hoteles']}\n";
    echo "- Con URL de Booking: {$stats['con_booking_url']}\n";
    echo "- Con Google Place ID: {$stats['con_google_place_id']}\n\n";
    
    // Mostrar algunos ejemplos
    echo "Ejemplos de hoteles con URLs de Booking:\n";
    $stmt = $pdo->query("
        SELECT id, nombre_hotel, booking_url 
        FROM hoteles 
        WHERE booking_url IS NOT NULL AND booking_url != '' AND activo = 1
        LIMIT 5
    ");
    $hotels = $stmt->fetchAll();
    
    foreach ($hotels as $hotel) {
        echo "- ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
        echo "  URL: {$hotel['booking_url']}\n";
    }
    
    if (empty($hotels)) {
        echo "❌ No se encontraron hoteles con URLs de Booking configuradas\n";
        echo "💡 Necesitas configurar las URLs de Booking en la gestión de hoteles\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>