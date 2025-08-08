<?php
/**
 * ==========================================================================
 * HEALTH CHECK - VERIFICACIÓN DE ESTADO DEL SISTEMA
 * Monitorea el estado de las extracciones y APIs
 * ==========================================================================
 */

require_once __DIR__ . '/admin-config.php';

class HealthChecker {
    private $pdo;
    private $logFile;
    private $alerts = [];
    
    public function __construct() {
        $this->pdo = getDBConnection();
        $this->logFile = __DIR__ . '/logs/health-check.log';
        
        // Crear directorio de logs si no existe
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Ejecutar verificación completa de salud
     */
    public function runHealthCheck() {
        $this->log("=== INICIO HEALTH CHECK ===");
        
        $checks = [
            'database' => $this->checkDatabase(),
            'recent_extractions' => $this->checkRecentExtractions(),
            'extraction_errors' => $this->checkExtractionErrors(),
            'disk_space' => $this->checkDiskSpace(),
            'log_files' => $this->checkLogFiles()
        ];
        
        $overallHealth = $this->calculateOverallHealth($checks);
        
        // Enviar alertas si es necesario
        if ($overallHealth['status'] === 'critical') {
            $this->sendCriticalAlert($overallHealth, $checks);
        }
        
        $this->log("=== FIN HEALTH CHECK - Estado: {$overallHealth['status']} ===");
        
        return [
            'timestamp' => date('Y-m-d H:i:s'),
            'overall_health' => $overallHealth,
            'individual_checks' => $checks,
            'alerts' => $this->alerts
        ];
    }
    
    /**
     * Verificar estado de la base de datos
     */
    private function checkDatabase() {
        try {
            // Test conexión
            $stmt = $this->pdo->query("SELECT 1");
            
            // Verificar tablas importantes
            $tables = ['hoteles', 'reviews', 'cron_extraction_log'];
            $missingTables = [];
            
            foreach ($tables as $table) {
                $stmt = $this->pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                if (!$stmt->fetch()) {
                    $missingTables[] = $table;
                }
            }
            
            if (!empty($missingTables)) {
                return [
                    'status' => 'critical',
                    'message' => 'Tablas faltantes: ' . implode(', ', $missingTables)
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => 'Base de datos funcionando correctamente'
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Error de base de datos: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar extracciones recientes
     */
    private function checkRecentExtractions() {
        try {
            // Verificar si hubo extracción en las últimas 24 horas
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as recent_reviews,
                       MAX(scraped_at) as last_extraction
                FROM reviews 
                WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (empty($result['last_extraction'])) {
                return [
                    'status' => 'warning',
                    'message' => 'No hay extracciones registradas'
                ];
            }
            
            $hoursSinceLastExtraction = (time() - strtotime($result['last_extraction'])) / 3600;
            
            if ($hoursSinceLastExtraction > 48) {
                return [
                    'status' => 'critical',
                    'message' => "Última extracción hace {$hoursSinceLastExtraction} horas"
                ];
            } elseif ($hoursSinceLastExtraction > 24) {
                return [
                    'status' => 'warning',
                    'message' => "Última extracción hace {$hoursSinceLastExtraction} horas"
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => "{$result['recent_reviews']} reseñas extraídas en las últimas 24h"
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'critical',
                'message' => 'Error verificando extracciones: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar errores en extracciones
     */
    private function checkExtractionErrors() {
        try {
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as failed_jobs
                FROM cron_extraction_log 
                WHERE status = 'failed' 
                AND execution_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ");
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $failedJobs = (int)$result['failed_jobs'];
            
            if ($failedJobs > 5) {
                return [
                    'status' => 'critical',
                    'message' => "{$failedJobs} trabajos fallidos en los últimos 7 días"
                ];
            } elseif ($failedJobs > 2) {
                return [
                    'status' => 'warning',
                    'message' => "{$failedJobs} trabajos fallidos en los últimos 7 días"
                ];
            }
            
            return [
                'status' => 'healthy',
                'message' => "Solo {$failedJobs} errores en los últimos 7 días"
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'No se pudo verificar errores: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Verificar espacio en disco
     */
    private function checkDiskSpace() {
        $freeBytes = disk_free_space(__DIR__);
        $totalBytes = disk_total_space(__DIR__);
        
        if ($freeBytes === false || $totalBytes === false) {
            return [
                'status' => 'warning',
                'message' => 'No se pudo verificar espacio en disco'
            ];
        }
        
        $freePercent = ($freeBytes / $totalBytes) * 100;
        $freeGB = round($freeBytes / (1024 * 1024 * 1024), 2);
        
        if ($freePercent < 10) {
            return [
                'status' => 'critical',
                'message' => "Espacio en disco crítico: {$freeGB}GB ({$freePercent}%)"
            ];
        } elseif ($freePercent < 20) {
            return [
                'status' => 'warning',
                'message' => "Espacio en disco bajo: {$freeGB}GB ({$freePercent}%)"
            ];
        }
        
        return [
            'status' => 'healthy',
            'message' => "Espacio en disco: {$freeGB}GB disponibles ({$freePercent}%)"
        ];
    }
    
    /**
     * Verificar archivos de log
     */
    private function checkLogFiles() {
        $logDir = __DIR__ . '/logs';
        $issues = [];
        
        // Verificar si el directorio existe
        if (!is_dir($logDir)) {
            return [
                'status' => 'warning',
                'message' => 'Directorio de logs no existe'
            ];
        }
        
        // Verificar archivos de log grandes
        $logFiles = glob($logDir . '/*.log');
        foreach ($logFiles as $logFile) {
            $size = filesize($logFile);
            if ($size > 100 * 1024 * 1024) { // 100MB
                $sizeMB = round($size / (1024 * 1024), 2);
                $issues[] = basename($logFile) . " ({$sizeMB}MB)";
            }
        }
        
        if (!empty($issues)) {
            return [
                'status' => 'warning',
                'message' => 'Archivos de log grandes: ' . implode(', ', $issues)
            ];
        }
        
        return [
            'status' => 'healthy',
            'message' => count($logFiles) . ' archivos de log en tamaño normal'
        ];
    }
    
    /**
     * Calcular salud general
     */
    private function calculateOverallHealth($checks) {
        $criticalCount = 0;
        $warningCount = 0;
        $healthyCount = 0;
        
        foreach ($checks as $check) {
            switch ($check['status']) {
                case 'critical':
                    $criticalCount++;
                    break;
                case 'warning':
                    $warningCount++;
                    break;
                case 'healthy':
                    $healthyCount++;
                    break;
            }
        }
        
        if ($criticalCount > 0) {
            return [
                'status' => 'critical',
                'score' => 0,
                'summary' => "{$criticalCount} críticos, {$warningCount} advertencias"
            ];
        } elseif ($warningCount > 2) {
            return [
                'status' => 'degraded',
                'score' => 50,
                'summary' => "{$warningCount} advertencias detectadas"
            ];
        } elseif ($warningCount > 0) {
            return [
                'status' => 'warning',
                'score' => 75,
                'summary' => "{$warningCount} advertencias menores"
            ];
        }
        
        return [
            'status' => 'healthy',
            'score' => 100,
            'summary' => 'Todos los sistemas funcionando correctamente'
        ];
    }
    
    /**
     * Enviar alerta crítica
     */
    private function sendCriticalAlert($overallHealth, $checks) {
        $alertMessage = "ALERTA CRÍTICA - Sistema de Extracciones\n\n";
        $alertMessage .= "Estado: {$overallHealth['status']}\n";
        $alertMessage .= "Resumen: {$overallHealth['summary']}\n\n";
        $alertMessage .= "Detalles:\n";
        
        foreach ($checks as $name => $check) {
            if ($check['status'] === 'critical' || $check['status'] === 'warning') {
                $alertMessage .= "- {$name}: {$check['message']}\n";
            }
        }
        
        $alertMessage .= "\nFecha: " . date('Y-m-d H:i:s');
        
        // Log la alerta
        $this->log($alertMessage, 'CRITICAL');
        
        // Aquí puedes agregar envío de email, Slack, etc.
        $this->alerts[] = [
            'type' => 'critical',
            'message' => $alertMessage,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Registrar log
     */
    private function log($message, $level = 'INFO') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        if (php_sapi_name() === 'cli') {
            echo $logMessage;
        }
    }
}

// Ejecutar si se llama directamente desde CLI
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $checker = new HealthChecker();
    $result = $checker->runHealthCheck();
    
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
    
    exit($result['overall_health']['status'] === 'critical' ? 1 : 0);
}
?>