<?php
/**
 * Sistema de logging para debug - Actualizado para escribir a BD
 */

class DebugLogger {
    private static $logFile = 'debug.log';
    
    public static function log($message, $level = 'INFO', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        // Escribir al archivo (fallback)
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // También escribir al error log de PHP
        error_log("DEBUG [$level] $message$contextStr");
        
        // Escribir a la tabla debug_logs si existe
        self::logToDatabase($message, $level, $context);
    }
    
    private static function logToDatabase($message, $level, $context) {
        try {
            if (!class_exists('EnvironmentLoader')) {
                require_once __DIR__ . '/../env-loader.php';
            }
            
            $pdo = EnvironmentLoader::createDatabaseConnection();
            if (!$pdo) return;
            
            // Verificar si la tabla existe
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'debug_logs'");
            if ($tableCheck->rowCount() === 0) return;
            
            $stmt = $pdo->prepare("
                INSERT INTO debug_logs (message, level, context, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $message,
                $level,
                json_encode($context)
            ]);
            
        } catch (Exception $e) {
            // Fallar silenciosamente para no interrumpir el flujo principal
            error_log("DebugLogger database write failed: " . $e->getMessage());
        }
    }
    
    public static function error($message, $context = []) {
        self::log($message, 'ERROR', $context);
    }
    
    public static function info($message, $context = []) {
        self::log($message, 'INFO', $context);
    }
    
    public static function debug($message, $context = []) {
        self::log($message, 'DEBUG', $context);
    }
    
    public static function getLogs($lines = 50) {
        if (!file_exists(self::$logFile)) {
            return "No hay logs disponibles";
        }
        
        $logs = file(self::$logFile);
        $logs = array_slice($logs, -$lines);
        return implode('', $logs);
    }
    
    public static function clearLogs() {
        if (file_exists(self::$logFile)) {
            unlink(self::$logFile);
        }
    }
}
?>