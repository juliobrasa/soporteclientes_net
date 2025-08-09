<?php
/**
 * Crear tabla debug_logs si no existe
 */

require_once 'env-loader.php';

echo "🔧 CREANDO TABLA DEBUG_LOGS\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    
    // Crear tabla debug_logs
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS debug_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        level VARCHAR(20) NOT NULL DEFAULT 'INFO',
        context JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_level (level),
        INDEX idx_created_at (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($createTableSQL);
    echo "✅ Tabla debug_logs creada o verificada exitosamente\n\n";
    
    // Insertar un log de prueba
    $stmt = $pdo->prepare("
        INSERT INTO debug_logs (message, level, context) 
        VALUES (?, ?, ?)
    ");
    
    $stmt->execute([
        'Tabla debug_logs creada/verificada',
        'INFO',
        json_encode(['test' => true, 'timestamp' => date('Y-m-d H:i:s')])
    ]);
    
    echo "✅ Log de prueba insertado\n";
    
    // Verificar que funciona
    $testQuery = $pdo->query("SELECT COUNT(*) as count FROM debug_logs");
    $result = $testQuery->fetch();
    
    echo "📊 Total logs en tabla: {$result['count']}\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>