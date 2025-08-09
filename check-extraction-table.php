<?php
require_once 'env-loader.php';

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    
    echo "🔍 VERIFICACIÓN DE TABLA apify_extraction_runs\n\n";
    
    // Verificar si la tabla existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'apify_extraction_runs'");
    if ($stmt->rowCount() === 0) {
        echo "❌ La tabla 'apify_extraction_runs' NO EXISTE\n";
        echo "   Necesita ejecutar las migraciones Laravel\n";
        exit(1);
    }
    
    echo "✅ La tabla 'apify_extraction_runs' existe\n\n";
    
    // Verificar estructura de la tabla
    $stmt = $pdo->query("DESCRIBE apify_extraction_runs");
    $columns = $stmt->fetchAll();
    
    echo "📋 ESTRUCTURA DE LA TABLA:\n";
    $hasStartedAt = false;
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']}) - {$col['Null']} - {$col['Default']}\n";
        if ($col['Field'] === 'started_at') {
            $hasStartedAt = true;
        }
    }
    
    if (!$hasStartedAt) {
        echo "\n❌ FALTA LA COLUMNA 'started_at'\n";
        echo "   Esta columna es necesaria para las consultas por fecha\n";
    } else {
        echo "\n✅ La columna 'started_at' está presente\n";
    }
    
    // Verificar datos existentes
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM apify_extraction_runs");
    $count = $stmt->fetchColumn();
    echo "\n📊 Registros en la tabla: {$count}\n";
    
    if ($count > 0) {
        // Mostrar últimos registros
        $stmt = $pdo->query("SELECT * FROM apify_extraction_runs ORDER BY id DESC LIMIT 3");
        $records = $stmt->fetchAll();
        
        echo "\n📋 ÚLTIMOS REGISTROS:\n";
        foreach ($records as $record) {
            echo "  • ID: {$record['id']} | Run: {$record['apify_run_id']} | Status: {$record['status']} | Started: " . ($record['started_at'] ?? 'NULL') . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>