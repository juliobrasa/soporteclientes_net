<?php
/**
 * Verificar estructura de tablas de extracción
 */

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    echo "📊 ESTRUCTURA DE TABLAS DE EXTRACCIÓN:\n\n";
    
    // Verificar si las tablas existen
    $tables = ['extraction_logs', 'extraction_config'];
    
    foreach ($tables as $table) {
        echo "🔍 Tabla: {$table}\n";
        
        $checkStmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
        if ($checkStmt->rowCount() > 0) {
            echo "   ✅ Existe\n";
            
            $descStmt = $pdo->query("DESCRIBE {$table}");
            $columns = $descStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "   📋 Columnas:\n";
            foreach ($columns as $col) {
                echo "     - {$col['Field']}: {$col['Type']}\n";
            }
            
            // Mostrar algunos registros si existen
            $dataStmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $dataStmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   📊 Registros: {$count}\n";
            
        } else {
            echo "   ❌ No existe\n";
        }
        
        echo "\n";
    }
    
    // Verificar configuración actual
    echo "⚙️  CONFIGURACIONES ACTUALES:\n";
    
    $configStmt = $pdo->query("
        SELECT ec.*, h.nombre_hotel 
        FROM extraction_config ec 
        JOIN hoteles h ON ec.hotel_id = h.id 
        LIMIT 5
    ");
    
    $configs = $configStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($configs as $config) {
        echo "   - {$config['nombre_hotel']} ({$config['platform']}): ";
        echo "próxima {$config['next_extraction']}, ";
        echo "max {$config['max_reviews_per_run']} reseñas\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>