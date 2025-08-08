<?php
/**
 * Sistema de logging para debug
 */

class DebugLogger {
    private static $logFile = 'debug.log';
    
    public static function log($message, $level = 'INFO', $context = []) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context) : '';
        $logEntry = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        // Escribir al archivo
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // También escribir al error log de PHP
        error_log("DEBUG [$level] $message$contextStr");
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