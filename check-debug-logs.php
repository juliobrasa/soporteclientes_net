<?php
/**
 * Revisar logs de debug más recientes
 */

require_once 'env-loader.php';

echo "🔍 DEBUG LOGS - ÚLTIMAS ENTRADAS\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    
    // Obtener logs más recientes (últimos 20)
    $stmt = $pdo->query("
        SELECT 
            id,
            message, 
            level, 
            context,
            created_at
        FROM debug_logs 
        ORDER BY created_at DESC, id DESC 
        LIMIT 20
    ");
    
    $logs = $stmt->fetchAll();
    
    if (empty($logs)) {
        echo "❌ No hay logs de debug en la tabla debug_logs\n";
        
        // Verificar si la tabla existe
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'debug_logs'");
        if ($tableCheck->rowCount() == 0) {
            echo "⚠️  La tabla 'debug_logs' no existe\n";
        }
    } else {
        echo "📋 ENCONTRADOS " . count($logs) . " LOGS RECIENTES:\n\n";
        
        foreach ($logs as $index => $log) {
            echo "🔍 LOG #" . ($index + 1) . " [ID: {$log['id']}]\n";
            echo "⏰ Fecha: {$log['created_at']}\n";
            echo "📊 Nivel: {$log['level']}\n";
            echo "💬 Mensaje: {$log['message']}\n";
            
            if (!empty($log['context']) && $log['context'] !== 'null') {
                $context = json_decode($log['context'], true);
                if (is_array($context) && !empty($context)) {
                    echo "📋 Contexto:\n";
                    foreach ($context as $key => $value) {
                        if (is_array($value) || is_object($value)) {
                            echo "   - $key: " . json_encode($value) . "\n";
                        } else {
                            echo "   - $key: $value\n";
                        }
                    }
                }
            }
            echo "\n" . str_repeat("-", 80) . "\n\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error consultando logs: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>