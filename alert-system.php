<?php
/**
 * Sistema de Alertas Post-Migración
 * 
 * Sistema automático de alertas para monitorear el estado del sistema
 * después de la migración y detectar problemas de manera proactiva
 */

require_once 'env-loader.php';

class AlertSystem 
{
    private $pdo;
    private $alertLog;
    private $config;
    private $notificationChannels;
    
    public function __construct() 
    {
        $this->pdo = createDatabaseConnection();
        $this->alertLog = __DIR__ . '/storage/logs/alerts.log';
        $this->ensureLogDirectory();
        $this->loadConfiguration();
        $this->initNotificationChannels();
    }
    
    private function ensureLogDirectory() 
    {
        $logDir = dirname($this->alertLog);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function loadConfiguration() 
    {
        $this->config = [
            'thresholds' => [
                'api_response_time_ms' => 2000,        // API slow if > 2 seconds
                'error_rate_percent' => 5,             // Error rate alert if > 5%
                'inactive_source_hours' => 24,         // Alert if source inactive > 24h
                'database_query_time_ms' => 1000,      // DB query slow if > 1 second
                'disk_usage_percent' => 85,            // Disk usage alert if > 85%
                'memory_usage_mb' => 512               // Memory alert if > 512MB
            ],
            'alert_cooldown_minutes' => 30,            // Don't repeat same alert for 30min
            'critical_components' => [
                'api_status',
                'database_connection', 
                'schema_integrity'
            ],
            'monitoring_enabled' => true,
            'notification_enabled' => true
        ];
    }
    
    private function initNotificationChannels() 
    {
        $this->notificationChannels = [
            'log' => true,           // Always log to file
            'email' => false,        // Configure SMTP if needed
            'webhook' => false,      // Configure webhook URL if needed
            'console' => true        // Output to console
        ];
    }
    
    /**
     * Ejecutar verificación completa de alertas
     */
    public function runAlertChecks() 
    {
        if (!$this->config['monitoring_enabled']) {
            return ['status' => 'disabled', 'message' => 'Monitoring disabled'];
        }
        
        $this->log("🚨 Iniciando verificación de alertas...", 'INFO');
        
        $alerts = [];
        
        // Verificaciones críticas
        $alerts = array_merge($alerts, $this->checkCriticalComponents());
        $alerts = array_merge($alerts, $this->checkSystemPerformance());
        $alerts = array_merge($alerts, $this->checkDataIntegrity());
        $alerts = array_merge($alerts, $this->checkSourceActivity());
        $alerts = array_merge($alerts, $this->checkResourceUsage());
        $alerts = array_merge($alerts, $this->checkErrorRates());
        
        // Procesar alertas
        $this->processAlerts($alerts);
        
        $summary = [
            'timestamp' => date('c'),
            'total_alerts' => count($alerts),
            'critical_alerts' => count(array_filter($alerts, fn($a) => $a['level'] === 'critical')),
            'warning_alerts' => count(array_filter($alerts, fn($a) => $a['level'] === 'warning')),
            'info_alerts' => count(array_filter($alerts, fn($a) => $a['level'] === 'info')),
            'alerts' => $alerts,
            'status' => count($alerts) === 0 ? 'healthy' : (
                count(array_filter($alerts, fn($a) => $a['level'] === 'critical')) > 0 ? 'critical' : 'warning'
            )
        ];
        
        $this->log("✅ Verificación completada: {$summary['total_alerts']} alertas generadas", 'INFO');
        
        return $summary;
    }
    
    /**
     * Verificar componentes críticos del sistema
     */
    private function checkCriticalComponents() 
    {
        $alerts = [];
        
        try {
            // Test API
            $apiStatus = $this->testApiEndpoint();
            if (!$apiStatus['success']) {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => 'api_status',
                    'message' => 'API not responding correctly',
                    'details' => $apiStatus['error'] ?? 'Unknown error',
                    'timestamp' => date('c')
                ];
            }
            
            // Test base de datos
            $dbStatus = $this->testDatabaseConnection();
            if (!$dbStatus['success']) {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => 'database_connection',
                    'message' => 'Database connection failed',
                    'details' => $dbStatus['error'] ?? 'Connection error',
                    'timestamp' => date('c')
                ];
            }
            
            // Verificar integridad del esquema
            $schemaStatus = $this->checkSchemaIntegrity();
            if (!$schemaStatus['success']) {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => 'schema_integrity',
                    'message' => 'Schema integrity compromised',
                    'details' => implode(', ', $schemaStatus['issues'] ?? []),
                    'timestamp' => date('c')
                ];
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'critical',
                'component' => 'system_check',
                'message' => 'Critical component check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar performance del sistema
     */
    private function checkSystemPerformance() 
    {
        $alerts = [];
        
        try {
            // Test velocidad de API
            $start = microtime(true);
            $this->testApiEndpoint();
            $apiTime = (microtime(true) - $start) * 1000;
            
            if ($apiTime > $this->config['thresholds']['api_response_time_ms']) {
                $alerts[] = [
                    'level' => 'warning',
                    'component' => 'api_performance',
                    'message' => 'API response time exceeds threshold',
                    'details' => "Response time: {$apiTime}ms (threshold: {$this->config['thresholds']['api_response_time_ms']}ms)",
                    'timestamp' => date('c')
                ];
            }
            
            // Test queries de base de datos
            $queries = [
                'simple_count' => "SELECT COUNT(*) FROM reviews LIMIT 1",
                'unified_query' => "SELECT COALESCE(rating, normalized_rating) FROM reviews LIMIT 10"
            ];
            
            foreach ($queries as $queryName => $query) {
                $start = microtime(true);
                $this->pdo->query($query);
                $queryTime = (microtime(true) - $start) * 1000;
                
                if ($queryTime > $this->config['thresholds']['database_query_time_ms']) {
                    $alerts[] = [
                        'level' => 'warning',
                        'component' => 'database_performance',
                        'message' => "Database query '$queryName' is slow",
                        'details' => "Query time: {$queryTime}ms (threshold: {$this->config['thresholds']['database_query_time_ms']}ms)",
                        'timestamp' => date('c')
                    ];
                }
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'warning',
                'component' => 'performance_check',
                'message' => 'Performance check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar integridad de datos
     */
    private function checkDataIntegrity() 
    {
        $alerts = [];
        
        try {
            // Verificar duplicados
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as total, COUNT(DISTINCT unique_id) as unique_count
                FROM reviews
            ");
            $result = $stmt->fetch();
            
            if ($result['total'] != $result['unique_count']) {
                $duplicates = $result['total'] - $result['unique_count'];
                $alerts[] = [
                    'level' => 'warning',
                    'component' => 'data_integrity',
                    'message' => 'Duplicate unique_id values detected',
                    'details' => "$duplicates duplicate records found",
                    'timestamp' => date('c')
                ];
            }
            
            // Verificar sincronización de campos
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as unsynced_count
                FROM reviews 
                WHERE (source_platform IS NOT NULL AND platform IS NOT NULL 
                       AND source_platform != platform)
                   OR (rating IS NOT NULL AND normalized_rating IS NOT NULL 
                       AND ABS(rating - normalized_rating) > 0.1)
            ");
            $unsyncedCount = $stmt->fetch()['unsynced_count'];
            
            if ($unsyncedCount > 0) {
                $alerts[] = [
                    'level' => 'warning',
                    'component' => 'data_synchronization',
                    'message' => 'Field synchronization issues detected',
                    'details' => "$unsyncedCount records with unsynced fields",
                    'timestamp' => date('c')
                ];
            }
            
            // Verificar datos corruptos
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as corrupt_count
                FROM reviews 
                WHERE (rating IS NOT NULL AND (rating < 0 OR rating > 10))
                   OR (normalized_rating IS NOT NULL AND (normalized_rating < 0 OR normalized_rating > 10))
                   OR unique_id IS NULL
                   OR unique_id = ''
            ");
            $corruptCount = $stmt->fetch()['corrupt_count'];
            
            if ($corruptCount > 0) {
                $alerts[] = [
                    'level' => 'critical',
                    'component' => 'data_corruption',
                    'message' => 'Corrupted data detected',
                    'details' => "$corruptCount records with invalid data",
                    'timestamp' => date('c')
                ];
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'warning',
                'component' => 'data_integrity_check',
                'message' => 'Data integrity check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar actividad de fuentes de extracción
     */
    private function checkSourceActivity() 
    {
        $alerts = [];
        
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    MAX(scraped_at) as last_activity,
                    TIMESTAMPDIFF(HOUR, MAX(scraped_at), NOW()) as hours_since_last,
                    COUNT(*) as total_reviews
                FROM reviews 
                WHERE COALESCE(extraction_source, 'legacy') != 'manual'
                GROUP BY COALESCE(extraction_source, 'legacy')
            ");
            
            $sources = $stmt->fetchAll();
            
            foreach ($sources as $source) {
                if ($source['hours_since_last'] > $this->config['thresholds']['inactive_source_hours']) {
                    $level = $source['hours_since_last'] > 48 ? 'critical' : 'warning';
                    
                    $alerts[] = [
                        'level' => $level,
                        'component' => 'source_activity',
                        'message' => "Source '{$source['source']}' has been inactive",
                        'details' => "No activity for {$source['hours_since_last']} hours (threshold: {$this->config['thresholds']['inactive_source_hours']}h)",
                        'timestamp' => date('c')
                    ];
                }
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'warning',
                'component' => 'source_activity_check',
                'message' => 'Source activity check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar uso de recursos del sistema
     */
    private function checkResourceUsage() 
    {
        $alerts = [];
        
        try {
            // Verificar uso de memoria
            $memoryUsage = memory_get_usage(true) / 1024 / 1024; // MB
            if ($memoryUsage > $this->config['thresholds']['memory_usage_mb']) {
                $alerts[] = [
                    'level' => 'warning',
                    'component' => 'memory_usage',
                    'message' => 'High memory usage detected',
                    'details' => "Memory usage: {$memoryUsage}MB (threshold: {$this->config['thresholds']['memory_usage_mb']}MB)",
                    'timestamp' => date('c')
                ];
            }
            
            // Verificar espacio en disco (si es posible)
            $diskFree = disk_free_space(__DIR__);
            $diskTotal = disk_total_space(__DIR__);
            
            if ($diskFree && $diskTotal) {
                $diskUsagePercent = (($diskTotal - $diskFree) / $diskTotal) * 100;
                
                if ($diskUsagePercent > $this->config['thresholds']['disk_usage_percent']) {
                    $alerts[] = [
                        'level' => 'warning',
                        'component' => 'disk_usage',
                        'message' => 'High disk usage detected',
                        'details' => "Disk usage: {$diskUsagePercent}% (threshold: {$this->config['thresholds']['disk_usage_percent']}%)",
                        'timestamp' => date('c')
                    ];
                }
            }
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'info',
                'component' => 'resource_check',
                'message' => 'Resource usage check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Verificar tasas de error
     */
    private function checkErrorRates() 
    {
        $alerts = [];
        
        try {
            // Verificar errores recientes en logs
            $errorLogPath = __DIR__ . '/storage/logs/apify-processor.log';
            
            if (file_exists($errorLogPath)) {
                $logContent = file_get_contents($errorLogPath);
                $recentLog = $this->getRecentLogEntries($logContent, 24); // Últimas 24 horas
                
                $totalEntries = count($recentLog);
                $errorEntries = count(array_filter($recentLog, fn($line) => 
                    strpos($line, '[ERROR]') !== false || strpos($line, 'CRITICAL') !== false
                ));
                
                if ($totalEntries > 0) {
                    $errorRate = ($errorEntries / $totalEntries) * 100;
                    
                    if ($errorRate > $this->config['thresholds']['error_rate_percent']) {
                        $alerts[] = [
                            'level' => 'warning',
                            'component' => 'error_rate',
                            'message' => 'High error rate detected in logs',
                            'details' => "Error rate: {$errorRate}% ({$errorEntries}/{$totalEntries}) (threshold: {$this->config['thresholds']['error_rate_percent']}%)",
                            'timestamp' => date('c')
                        ];
                    }
                }
            }
            
            // Verificar errores en base de datos (intentos de inserción fallidos, etc.)
            // Esta verificación sería específica según logs de base de datos disponibles
            
        } catch (Exception $e) {
            $alerts[] = [
                'level' => 'info',
                'component' => 'error_rate_check',
                'message' => 'Error rate check failed',
                'details' => $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
        
        return $alerts;
    }
    
    /**
     * Procesar y enviar alertas
     */
    private function processAlerts($alerts) 
    {
        if (empty($alerts)) {
            $this->log("✅ No se generaron alertas - Sistema funcionando correctamente", 'INFO');
            return;
        }
        
        // Filtrar alertas duplicadas (cooldown)
        $filteredAlerts = $this->filterDuplicateAlerts($alerts);
        
        foreach ($filteredAlerts as $alert) {
            // Log la alerta
            $this->logAlert($alert);
            
            // Enviar notificaciones
            $this->sendNotifications($alert);
            
            // Registrar para cooldown
            $this->registerAlert($alert);
        }
    }
    
    private function filterDuplicateAlerts($alerts) 
    {
        $filtered = [];
        
        foreach ($alerts as $alert) {
            $alertKey = $alert['component'] . '|' . $alert['message'];
            
            if (!$this->isAlertInCooldown($alertKey)) {
                $filtered[] = $alert;
            }
        }
        
        return $filtered;
    }
    
    private function isAlertInCooldown($alertKey) 
    {
        $cooldownFile = __DIR__ . '/storage/logs/alert-cooldown.json';
        
        if (!file_exists($cooldownFile)) {
            return false;
        }
        
        $cooldowns = json_decode(file_get_contents($cooldownFile), true) ?: [];
        
        if (!isset($cooldowns[$alertKey])) {
            return false;
        }
        
        $lastAlerted = strtotime($cooldowns[$alertKey]);
        $cooldownMinutes = $this->config['alert_cooldown_minutes'];
        
        return (time() - $lastAlerted) < ($cooldownMinutes * 60);
    }
    
    private function registerAlert($alert) 
    {
        $cooldownFile = __DIR__ . '/storage/logs/alert-cooldown.json';
        $cooldowns = [];
        
        if (file_exists($cooldownFile)) {
            $cooldowns = json_decode(file_get_contents($cooldownFile), true) ?: [];
        }
        
        $alertKey = $alert['component'] . '|' . $alert['message'];
        $cooldowns[$alertKey] = date('c');
        
        file_put_contents($cooldownFile, json_encode($cooldowns, JSON_PRETTY_PRINT));
    }
    
    private function sendNotifications($alert) 
    {
        if (!$this->config['notification_enabled']) {
            return;
        }
        
        // Console notification
        if ($this->notificationChannels['console']) {
            $emoji = $alert['level'] === 'critical' ? '🚨' : ($alert['level'] === 'warning' ? '⚠️' : '💡');
            echo "{$emoji} [{$alert['level']}] {$alert['message']} - {$alert['details']}\n";
        }
        
        // Email notification (si está configurado)
        if ($this->notificationChannels['email'] && $alert['level'] === 'critical') {
            $this->sendEmailAlert($alert);
        }
        
        // Webhook notification (si está configurado)
        if ($this->notificationChannels['webhook']) {
            $this->sendWebhookAlert($alert);
        }
    }
    
    // Helper methods
    private function testApiEndpoint() 
    {
        try {
            // Test simple de la API (método interno)
            $start = microtime(true);
            
            // Simular llamada interna a la API
            $_GET = ['limit' => '1'];
            
            ob_start();
            include __DIR__ . '/api/reviews.php';
            $output = ob_get_clean();
            
            $data = json_decode($output, true);
            $responseTime = (microtime(true) - $start) * 1000;
            
            return [
                'success' => $data && isset($data['success']) && $data['success'],
                'response_time_ms' => $responseTime,
                'error' => $data['error'] ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function testDatabaseConnection() 
    {
        try {
            $start = microtime(true);
            $stmt = $this->pdo->query("SELECT 1");
            $queryTime = (microtime(true) - $start) * 1000;
            
            return [
                'success' => $stmt !== false,
                'query_time_ms' => $queryTime
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    private function checkSchemaIntegrity() 
    {
        try {
            $stmt = $this->pdo->query("DESCRIBE reviews");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $requiredColumns = [
                'source_platform', 'platform',
                'property_response', 'response_from_owner',
                'rating', 'normalized_rating',
                'extraction_source', 'unique_id'
            ];
            
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                return ['success' => true];
            } else {
                return [
                    'success' => false,
                    'issues' => ['Missing columns: ' . implode(', ', $missingColumns)]
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'issues' => [$e->getMessage()]
            ];
        }
    }
    
    private function getRecentLogEntries($logContent, $hours = 24) 
    {
        $lines = explode("\n", $logContent);
        $cutoffTime = time() - ($hours * 3600);
        $recentLines = [];
        
        foreach ($lines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                $lineTime = strtotime($matches[1]);
                if ($lineTime >= $cutoffTime) {
                    $recentLines[] = $line;
                }
            }
        }
        
        return $recentLines;
    }
    
    private function logAlert($alert) 
    {
        $alertEntry = json_encode($alert, JSON_UNESCAPED_UNICODE) . "\n";
        file_put_contents($this->alertLog, $alertEntry, FILE_APPEND);
    }
    
    private function log($message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        echo $logEntry;
        file_put_contents($this->alertLog, $logEntry, FILE_APPEND);
    }
    
    private function sendEmailAlert($alert) 
    {
        // Implementar envío de email si es necesario
        // Requiere configuración SMTP
    }
    
    private function sendWebhookAlert($alert) 
    {
        // Implementar webhook si es necesario
        // Para Slack, Discord, etc.
    }
    
    /**
     * Configurar alertas automáticas via cron
     */
    public static function showCronSetup() 
    {
        echo "🔧 CONFIGURACIÓN DE ALERTAS AUTOMÁTICAS\n";
        echo str_repeat("=", 50) . "\n\n";
        
        echo "Para configurar alertas automáticas, agregar a crontab:\n\n";
        
        echo "# Alertas cada 15 minutos (primeras 4 horas post-migración)\n";
        echo "*/15 * * * * cd " . __DIR__ . " && php alert-system.php --run\n\n";
        
        echo "# Alertas cada hora (después de las 4 horas iniciales)\n";
        echo "0 */1 * * * cd " . __DIR__ . " && php alert-system.php --run\n\n";
        
        echo "# Reporte diario de estado\n";
        echo "0 9 * * * cd " . __DIR__ . " && php alert-system.php --daily-report\n\n";
        
        echo "Comandos:\n";
        echo "  crontab -e          # Editar crontab\n";
        echo "  crontab -l          # Listar cron jobs\n";
        echo "  systemctl start cron # Iniciar servicio cron (si no está activo)\n\n";
        
        echo "Para testing manual:\n";
        echo "  php alert-system.php --run          # Ejecutar verificación\n";
        echo "  php alert-system.php --test-alert   # Generar alerta de prueba\n";
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? '--run';
    
    try {
        $alertSystem = new AlertSystem();
        
        switch ($action) {
            case '--run':
            case 'run':
                $summary = $alertSystem->runAlertChecks();
                
                echo "\n📊 RESUMEN DE ALERTAS:\n";
                echo "Estado general: " . strtoupper($summary['status']) . "\n";
                echo "Total alertas: {$summary['total_alerts']}\n";
                echo "  - Críticas: {$summary['critical_alerts']}\n";
                echo "  - Advertencias: {$summary['warning_alerts']}\n";
                echo "  - Informativas: {$summary['info_alerts']}\n";
                
                if ($summary['critical_alerts'] > 0) {
                    echo "\n🚨 ACCIÓN REQUERIDA: Hay {$summary['critical_alerts']} alertas críticas\n";
                    exit(1);
                } elseif ($summary['warning_alerts'] > 0) {
                    echo "\n⚠️  ATENCIÓN: Hay {$summary['warning_alerts']} advertencias\n";
                    exit(2);
                } else {
                    echo "\n✅ SISTEMA SALUDABLE: No hay alertas críticas\n";
                    exit(0);
                }
                break;
                
            case '--setup':
            case 'setup':
                AlertSystem::showCronSetup();
                break;
                
            case '--test-alert':
                echo "🧪 Generando alerta de prueba...\n";
                $alertSystem = new AlertSystem();
                $testAlert = [
                    'level' => 'info',
                    'component' => 'test_system',
                    'message' => 'Test alert generated successfully',
                    'details' => 'This is a test alert to verify the notification system',
                    'timestamp' => date('c')
                ];
                $alertSystem->processAlerts([$testAlert]);
                echo "✅ Alerta de prueba enviada\n";
                break;
                
            case '--daily-report':
                echo "📄 Generando reporte diario...\n";
                // Implementar reporte diario si es necesario
                break;
                
            default:
                echo "Uso: php alert-system.php [--run|--setup|--test-alert|--daily-report]\n";
                echo "  --run          Ejecutar verificación de alertas\n";
                echo "  --setup        Mostrar comandos para configurar cron\n";  
                echo "  --test-alert   Generar alerta de prueba\n";
                echo "  --daily-report Generar reporte diario\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error en sistema de alertas: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>