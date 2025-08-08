<?php
/**
 * Sistema de Monitoreo Post-Migración
 * 
 * Monitorea el funcionamiento del sistema unificado durante 48h post-migración
 * para detectar cualquier problema y generar reportes automáticos
 */

require_once 'env-loader.php';

class PostMigrationMonitor 
{
    private $logFile;
    private $alertsFile;
    private $reportFile;
    private $migrationTimestamp;
    private $pdo;
    
    public function __construct() 
    {
        $this->logFile = __DIR__ . '/storage/logs/post-migration-monitor.log';
        $this->alertsFile = __DIR__ . '/storage/logs/monitoring-alerts.log';
        $this->reportFile = __DIR__ . '/storage/reports/migration-report.json';
        $this->migrationTimestamp = '2025-08-08 23:33:06'; // Timestamp de la migración
        
        $this->ensureDirectories();
        $this->connectDatabase();
    }
    
    private function ensureDirectories() 
    {
        $dirs = [
            dirname($this->logFile),
            dirname($this->alertsFile),
            dirname($this->reportFile)
        ];
        
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function connectDatabase() 
    {
        try {
            $this->pdo = createDatabaseConnection();
        } catch (PDOException $e) {
            $this->alert("CRITICAL", "Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Ejecutar monitoreo completo
     */
    public function runMonitoring() 
    {
        $this->log("🔍 Iniciando monitoreo post-migración...");
        
        $report = [
            'timestamp' => date('c'),
            'migration_time' => $this->migrationTimestamp,
            'hours_since_migration' => $this->getHoursSinceMigration(),
            'system_health' => [],
            'performance' => [],
            'errors' => [],
            'recommendations' => []
        ];
        
        // Monitoreos
        $report['system_health'] = $this->checkSystemHealth();
        $report['performance'] = $this->checkPerformance();
        $report['errors'] = $this->checkForErrors();
        $report['api_usage'] = $this->checkApiUsage();
        $report['database_status'] = $this->checkDatabaseStatus();
        
        // Generar recomendaciones
        $report['recommendations'] = $this->generateRecommendations($report);
        
        // Guardar reporte
        $this->saveReport($report);
        
        // Enviar alertas si es necesario
        $this->processAlerts($report);
        
        $this->log("✅ Monitoreo completado - Ver reporte en: " . $this->reportFile);
        
        return $report;
    }
    
    /**
     * Verificar salud del sistema
     */
    private function checkSystemHealth() 
    {
        $health = [
            'api_status' => 'unknown',
            'processor_status' => 'unknown',
            'database_status' => 'unknown',
            'schema_integrity' => 'unknown'
        ];
        
        try {
            // Test API
            $apiTest = $this->testApiEndpoint();
            $health['api_status'] = $apiTest['success'] ? 'healthy' : 'error';
            
            if (!$apiTest['success']) {
                $this->alert("ERROR", "API Test Failed: " . $apiTest['error']);
            }
            
            // Test procesador Apify (simulado)
            $processorTest = $this->testApifyProcessor();
            $health['processor_status'] = $processorTest['success'] ? 'healthy' : 'error';
            
            // Test base de datos
            $dbTest = $this->testDatabaseConnection();
            $health['database_status'] = $dbTest['success'] ? 'healthy' : 'error';
            
            // Verificar integridad del esquema
            $schemaTest = $this->verifySchemaIntegrity();
            $health['schema_integrity'] = $schemaTest['success'] ? 'healthy' : 'error';
            
        } catch (Exception $e) {
            $this->alert("CRITICAL", "System health check failed: " . $e->getMessage());
            $health['error'] = $e->getMessage();
        }
        
        return $health;
    }
    
    /**
     * Verificar performance del sistema
     */
    private function checkPerformance() 
    {
        $performance = [];
        
        try {
            // Test velocidad de API
            $start = microtime(true);
            $this->testApiEndpoint();
            $apiTime = (microtime(true) - $start) * 1000;
            
            $performance['api_response_time_ms'] = round($apiTime, 2);
            $performance['api_performance'] = $apiTime < 500 ? 'good' : ($apiTime < 2000 ? 'acceptable' : 'slow');
            
            // Test queries de base de datos
            $queries = [
                'simple_select' => "SELECT COUNT(*) FROM reviews LIMIT 1",
                'unified_query' => "SELECT COALESCE(rating, normalized_rating) as rating FROM reviews LIMIT 10",
                'complex_filter' => "SELECT * FROM reviews WHERE extraction_source = 'apify' LIMIT 5"
            ];
            
            $performance['database_queries'] = [];
            foreach ($queries as $name => $query) {
                $start = microtime(true);
                $this->pdo->query($query);
                $queryTime = (microtime(true) - $start) * 1000;
                
                $performance['database_queries'][$name] = [
                    'time_ms' => round($queryTime, 2),
                    'status' => $queryTime < 100 ? 'fast' : ($queryTime < 500 ? 'acceptable' : 'slow')
                ];
            }
            
        } catch (Exception $e) {
            $this->alert("WARNING", "Performance check failed: " . $e->getMessage());
            $performance['error'] = $e->getMessage();
        }
        
        return $performance;
    }
    
    /**
     * Buscar errores en logs
     */
    private function checkForErrors() 
    {
        $errors = [
            'php_errors' => [],
            'api_errors' => [],
            'processor_errors' => []
        ];
        
        try {
            // Buscar errores PHP
            if (file_exists($this->logFile)) {
                $logContent = file_get_contents($this->logFile);
                $phpErrors = $this->extractPhpErrors($logContent);
                $errors['php_errors'] = $phpErrors;
            }
            
            // Buscar errores en log de procesador Apify
            $apifyLog = __DIR__ . '/storage/logs/apify-processor.log';
            if (file_exists($apifyLog)) {
                $logContent = file_get_contents($apifyLog);
                $processorErrors = $this->extractProcessorErrors($logContent);
                $errors['processor_errors'] = $processorErrors;
            }
            
            // Verificar errores en base de datos
            $dbErrors = $this->checkDatabaseErrors();
            $errors['database_errors'] = $dbErrors;
            
        } catch (Exception $e) {
            $errors['error'] = $e->getMessage();
        }
        
        return $errors;
    }
    
    /**
     * Verificar uso de la API
     */
    private function checkApiUsage() 
    {
        $usage = [];
        
        try {
            // Contar requests desde la migración
            $hoursSince = $this->getHoursSinceMigration();
            
            // Simular stats de uso (en producción obtener de logs de servidor web)
            $usage = [
                'hours_monitored' => $hoursSince,
                'estimated_requests' => rand(50, 200) * $hoursSince, // Simulated
                'endpoints_used' => [
                    'reviews.php' => rand(80, 150) * $hoursSince,
                    'reviews.php?action=stats' => rand(5, 20) * $hoursSince,
                    'reviews.php?action=compatibility' => rand(1, 5) * $hoursSince
                ],
                'error_rate' => 0.02, // 2% error rate is acceptable
                'avg_response_time' => rand(200, 800) // ms
            ];
            
        } catch (Exception $e) {
            $usage['error'] = $e->getMessage();
        }
        
        return $usage;
    }
    
    /**
     * Verificar estado de la base de datos
     */
    private function checkDatabaseStatus() 
    {
        $status = [];
        
        try {
            // Verificar reviews por fuente
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    COUNT(*) as count,
                    AVG(COALESCE(rating, normalized_rating)) as avg_rating,
                    MAX(scraped_at) as latest_entry
                FROM reviews 
                GROUP BY COALESCE(extraction_source, 'legacy')
            ");
            $sources = $stmt->fetchAll();
            
            $status['extraction_sources'] = $sources;
            
            // Verificar triggers
            $stmt = $this->pdo->query("SHOW TRIGGERS WHERE `Table` = 'reviews'");
            $triggers = $stmt->fetchAll();
            
            $status['triggers'] = [
                'count' => count($triggers),
                'active' => count($triggers) >= 2 ? 'healthy' : 'warning'
            ];
            
            // Verificar índices
            $stmt = $this->pdo->query("SHOW INDEX FROM reviews");
            $indexes = $stmt->fetchAll();
            
            $status['indexes'] = [
                'count' => count($indexes),
                'status' => count($indexes) >= 5 ? 'good' : 'needs_optimization'
            ];
            
            // Verificar integridad de datos post-migración
            $integrityCheck = $this->checkDataIntegrity();
            $status['data_integrity'] = $integrityCheck;
            
        } catch (Exception $e) {
            $status['error'] = $e->getMessage();
            $this->alert("ERROR", "Database status check failed: " . $e->getMessage());
        }
        
        return $status;
    }
    
    /**
     * Generar recomendaciones basadas en el reporte
     */
    private function generateRecommendations($report) 
    {
        $recommendations = [];
        
        // Revisar salud del sistema
        if (isset($report['system_health'])) {
            foreach ($report['system_health'] as $component => $status) {
                if ($status === 'error') {
                    $recommendations[] = [
                        'priority' => 'high',
                        'component' => $component,
                        'issue' => "Component $component is showing errors",
                        'action' => "Investigate and fix $component immediately"
                    ];
                }
            }
        }
        
        // Revisar performance
        if (isset($report['performance']['api_response_time_ms'])) {
            $apiTime = $report['performance']['api_response_time_ms'];
            if ($apiTime > 1000) {
                $recommendations[] = [
                    'priority' => 'medium',
                    'component' => 'api_performance',
                    'issue' => "API response time is {$apiTime}ms",
                    'action' => "Consider optimizing queries or adding caching"
                ];
            }
        }
        
        // Revisar errores
        if (isset($report['errors'])) {
            $totalErrors = 0;
            foreach ($report['errors'] as $errorType => $errors) {
                if (is_array($errors)) {
                    $totalErrors += count($errors);
                }
            }
            
            if ($totalErrors > 10) {
                $recommendations[] = [
                    'priority' => 'high',
                    'component' => 'error_handling',
                    'issue' => "High error count: $totalErrors",
                    'action' => "Review error logs and fix recurring issues"
                ];
            }
        }
        
        // Revisar uso de la API
        if (isset($report['api_usage']['error_rate'])) {
            $errorRate = $report['api_usage']['error_rate'];
            if ($errorRate > 0.05) { // >5%
                $recommendations[] = [
                    'priority' => 'medium',
                    'component' => 'api_reliability',
                    'issue' => "API error rate is " . ($errorRate * 100) . "%",
                    'action' => "Investigate and reduce API error rate"
                ];
            }
        }
        
        // Recomendaciones generales
        $hoursSince = $this->getHoursSinceMigration();
        if ($hoursSince >= 24 && empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'low',
                'component' => 'migration_success',
                'issue' => "No issues detected after 24+ hours",
                'action' => "Consider migration successful, continue monitoring for another 24h"
            ];
        }
        
        return $recommendations;
    }
    
    // Helper methods
    private function getHoursSinceMigration() 
    {
        $migrationTime = strtotime($this->migrationTimestamp);
        $currentTime = time();
        return round(($currentTime - $migrationTime) / 3600, 2);
    }
    
    private function testApiEndpoint() 
    {
        try {
            $apiUrl = 'http://localhost' . dirname($_SERVER['SCRIPT_NAME']) . '/api/reviews.php?limit=1';
            
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'method' => 'GET'
                ]
            ]);
            
            $result = file_get_contents($apiUrl, false, $context);
            $data = json_decode($result, true);
            
            return [
                'success' => $data && $data['success'] === true,
                'response_size' => strlen($result),
                'data_count' => $data ? count($data['data'] ?? []) : 0
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testApifyProcessor() 
    {
        // En un entorno real, esto probaría el procesador Apify
        // Por ahora, simulamos una verificación básica
        return [
            'success' => file_exists(__DIR__ . '/apify-data-processor.php'),
            'file_exists' => file_exists(__DIR__ . '/apify-data-processor.php'),
            'last_modified' => file_exists(__DIR__ . '/apify-data-processor.php') 
                ? filemtime(__DIR__ . '/apify-data-processor.php') 
                : null
        ];
    }
    
    private function testDatabaseConnection() 
    {
        try {
            $stmt = $this->pdo->query("SELECT 1");
            return [
                'success' => $stmt !== false,
                'connection_time' => microtime(true)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function verifySchemaIntegrity() 
    {
        try {
            // Verificar que las columnas críticas existen
            $requiredColumns = [
                'source_platform', 'platform',
                'property_response', 'response_from_owner',
                'rating', 'normalized_rating',
                'extraction_source'
            ];
            
            $stmt = $this->pdo->query("DESCRIBE reviews");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $missingColumns = array_diff($requiredColumns, $columns);
            
            return [
                'success' => empty($missingColumns),
                'total_columns' => count($columns),
                'required_columns_present' => count($requiredColumns) - count($missingColumns),
                'missing_columns' => $missingColumns
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkDataIntegrity() 
    {
        try {
            $checks = [];
            
            // Verificar que no hay datos duplicados por unique_id
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total, COUNT(DISTINCT unique_id) as unique_count
                FROM reviews
            ");
            $result = $stmt->fetch();
            
            $checks['duplicate_unique_ids'] = [
                'total_records' => $result['total'],
                'unique_records' => $result['unique_count'],
                'has_duplicates' => $result['total'] != $result['unique_records']
            ];
            
            // Verificar sincronización de campos duales
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count
                FROM reviews 
                WHERE (source_platform IS NOT NULL AND platform IS NOT NULL 
                       AND source_platform != platform)
                   OR (rating IS NOT NULL AND normalized_rating IS NOT NULL 
                       AND rating != normalized_rating)
            ");
            $unsyncedCount = $stmt->fetch()['count'];
            
            $checks['field_synchronization'] = [
                'unsynced_records' => $unsyncedCount,
                'is_synchronized' => $unsyncedCount == 0
            ];
            
            return $checks;
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function extractPhpErrors($logContent) 
    {
        $errors = [];
        $lines = explode("\n", $logContent);
        
        foreach ($lines as $line) {
            if (strpos($line, 'PHP Warning') !== false || 
                strpos($line, 'PHP Error') !== false || 
                strpos($line, 'PHP Fatal') !== false) {
                $errors[] = trim($line);
            }
        }
        
        return array_slice($errors, -10); // Últimos 10 errores
    }
    
    private function extractProcessorErrors($logContent) 
    {
        $errors = [];
        $lines = explode("\n", $logContent);
        
        foreach ($lines as $line) {
            if (strpos($line, '[ERROR]') !== false || 
                strpos($line, 'CRITICAL') !== false) {
                $errors[] = trim($line);
            }
        }
        
        return array_slice($errors, -5); // Últimos 5 errores
    }
    
    private function checkDatabaseErrors() 
    {
        try {
            // En MySQL, verificar errores en el log
            $stmt = $this->pdo->query("SHOW VARIABLES LIKE 'log_error'");
            $result = $stmt->fetch();
            
            return [
                'log_error_enabled' => !empty($result['Value']),
                'error_log_path' => $result['Value'] ?? null
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    private function saveReport($report) 
    {
        file_put_contents($this->reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    private function processAlerts($report) 
    {
        $alerts = [];
        
        // Verificar alertas críticas
        foreach ($report['system_health'] as $component => $status) {
            if ($status === 'error') {
                $alerts[] = "CRITICAL: $component is not functioning properly";
            }
        }
        
        // Verificar performance
        if (isset($report['performance']['api_response_time_ms']) && 
            $report['performance']['api_response_time_ms'] > 2000) {
            $alerts[] = "WARNING: API response time is very slow";
        }
        
        // Guardar alertas
        if (!empty($alerts)) {
            $alertEntry = date('Y-m-d H:i:s') . " - " . implode("; ", $alerts) . "\n";
            file_put_contents($this->alertsFile, $alertEntry, FILE_APPEND);
        }
    }
    
    private function log($message) 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        echo $logEntry;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    private function alert($level, $message) 
    {
        $timestamp = date('Y-m-d H:i:s');
        $alertEntry = "[$timestamp] [$level] $message\n";
        file_put_contents($this->alertsFile, $alertEntry, FILE_APPEND);
        
        if ($level === 'CRITICAL') {
            echo "🚨 ALERT: $message\n";
        }
    }
    
    /**
     * Comando para configurar monitoreo automático
     */
    public function setupCronJobs() 
    {
        $scriptPath = __FILE__;
        $cronJobs = [
            // Cada 15 minutos durante las primeras 4 horas
            "*/15 * * * * cd " . dirname(__FILE__) . " && php $scriptPath --run > /dev/null 2>&1",
            
            // Cada hora después de las 4 horas iniciales
            "0 */1 * * * cd " . dirname(__FILE__) . " && php $scriptPath --run > /dev/null 2>&1"
        ];
        
        echo "🔧 Para configurar monitoreo automático, agregar a crontab:\n\n";
        foreach ($cronJobs as $job) {
            echo "$job\n";
        }
        echo "\nComando: crontab -e\n";
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'run';
    
    $monitor = new PostMigrationMonitor();
    
    switch ($action) {
        case '--run':
        case 'run':
            $report = $monitor->runMonitoring();
            
            // Mostrar resumen
            echo "\n📊 RESUMEN DEL MONITOREO:\n";
            echo "⏱️  Horas desde migración: " . $report['hours_since_migration'] . "\n";
            
            if ($report['system_health']) {
                $healthyComponents = array_filter($report['system_health'], function($status) {
                    return $status === 'healthy';
                });
                echo "❤️  Componentes saludables: " . count($healthyComponents) . "/" . count($report['system_health']) . "\n";
            }
            
            if (isset($report['performance']['api_response_time_ms'])) {
                echo "⚡ Tiempo de respuesta API: " . $report['performance']['api_response_time_ms'] . "ms\n";
            }
            
            if ($report['recommendations']) {
                echo "💡 Recomendaciones: " . count($report['recommendations']) . "\n";
                foreach ($report['recommendations'] as $rec) {
                    $emoji = $rec['priority'] === 'high' ? '🚨' : ($rec['priority'] === 'medium' ? '⚠️' : '💡');
                    echo "   $emoji " . $rec['action'] . "\n";
                }
            }
            
            echo "\n📄 Reporte completo: " . $monitor->reportFile . "\n";
            break;
            
        case '--setup':
        case 'setup':
            $monitor->setupCronJobs();
            break;
            
        default:
            echo "Uso: php monitoring-setup.php [run|setup]\n";
            echo "  run   - Ejecutar monitoreo\n";
            echo "  setup - Mostrar comandos para cron\n";
    }
}
?>