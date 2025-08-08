<?php
require_once 'admin-config.php';

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        die("Error conectando a la base de datos");
    }
    
    echo "=== ESTRUCTURA DE TABLA HOTELES ===\n\n";
    
    // Verificar tabla hoteles
    $stmt = $pdo->query("DESCRIBE hoteles");
    $columns = $stmt->fetchAll();
    
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} " . 
             ($column['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . "\n";
    }
    
    echo "\n=== VERIFICAR COLUMNAS NECESARIAS ===\n\n";
    
    // Verificar y crear columnas necesarias
    $columnsToCheck = [
        'booking_url' => 'TEXT NULL',
        'google_place_id' => 'VARCHAR(200) NULL',
        'tripadvisor_url' => 'TEXT NULL'
    ];
    
    foreach ($columnsToCheck as $columnName => $columnDefinition) {
        $hasColumn = false;
        foreach ($columns as $column) {
            if ($column['Field'] === $columnName) {
                $hasColumn = true;
                break;
            }
        }
        
        if (!$hasColumn) {
            echo "❌ La columna '{$columnName}' no existe. Creándola...\n";
            $sql = "ALTER TABLE hoteles ADD COLUMN {$columnName} {$columnDefinition}";
            $pdo->exec($sql);
            echo "✅ Columna '{$columnName}' creada correctamente\n";
        } else {
            echo "✅ La columna '{$columnName}' ya existe\n";
        }
    }
    
    echo "\n=== DATOS DE HOTELES ===\n\n";
    
    // Mostrar hoteles existentes
    $stmt = $pdo->query("
        SELECT 
            id, 
            nombre_hotel, 
            activo,
            booking_url,
            google_place_id,
            tripadvisor_url
        FROM hoteles 
        ORDER BY id DESC
        LIMIT 10
    ");
    $hotels = $stmt->fetchAll();
    
    echo "Últimos 10 hoteles:\n";
    foreach ($hotels as $hotel) {
        $status = $hotel['activo'] ? '✅' : '❌';
        echo "{$status} ID {$hotel['id']}: {$hotel['nombre_hotel']}\n";
        
        if ($hotel['booking_url']) {
            echo "  📍 Booking: " . substr($hotel['booking_url'], 0, 50) . "...\n";
        }
        
        if ($hotel['google_place_id']) {
            echo "  🗺️  Google: {$hotel['google_place_id']}\n";
        }
        
        if ($hotel['tripadvisor_url']) {
            echo "  ✈️  TripAdvisor: " . substr($hotel['tripadvisor_url'], 0, 50) . "...\n";
        }
        
        echo "\n";
    }
    
    // Estadísticas
    $stmt = $pdo->query("
        SELECT 
            COUNT(*) as total_hoteles,
            COUNT(CASE WHEN activo = 1 THEN 1 END) as activos,
            COUNT(CASE WHEN booking_url IS NOT NULL AND booking_url != '' THEN 1 END) as con_booking,
            COUNT(CASE WHEN google_place_id IS NOT NULL AND google_place_id != '' THEN 1 END) as con_google,
            COUNT(CASE WHEN tripadvisor_url IS NOT NULL AND tripadvisor_url != '' THEN 1 END) as con_tripadvisor
        FROM hoteles
    ");
    $stats = $stmt->fetch();
    
    echo "=== ESTADÍSTICAS ===\n";
    echo "- Total hoteles: {$stats['total_hoteles']}\n";
    echo "- Hoteles activos: {$stats['activos']}\n";
    echo "- Con URL de Booking: {$stats['con_booking']}\n";
    echo "- Con Google Place ID: {$stats['con_google']}\n";
    echo "- Con URL de TripAdvisor: {$stats['con_tripadvisor']}\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>