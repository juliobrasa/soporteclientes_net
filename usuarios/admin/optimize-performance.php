<?php
/**
 * ==========================================================================
 * OPTIMIZACIÓN DE RENDIMIENTO - FASE 8
 * Kavia Hoteles Panel de Administración
 * Script de optimización de base de datos y rendimiento
 * ==========================================================================
 */

// Desactivar salida de errores para evitar interferir con JSON
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

// Configuración de base de datos
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

// Función para enviar respuesta JSON
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para manejar errores
function sendError($message, $error = null) {
    $response = [
        'success' => false,
        'error' => $message
    ];
    if ($error) {
        $response['details'] = $error;
    }
    sendResponse($response, 500);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch(PDOException $e) {
    sendError('Error de conexión a la base de datos', $e->getMessage());
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

/**
 * Clase de Optimización de Rendimiento
 */
class PerformanceOptimizer {
    private $pdo;
    private $results = [];
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Ejecutar todas las optimizaciones
     */
    public function runAllOptimizations() {
        $this->results = [
            'start_time' => microtime(true),
            'optimizations' => [],
            'summary' => []
        ];
        
        // 1. Optimizar tablas principales
        $this->optimizeTables();
        
        // 2. Crear índices optimizados
        $this->createOptimizedIndexes();
        
        // 3. Configurar parámetros de MySQL
        $this->optimizeMySQLSettings();
        
        // 4. Limpiar datos obsoletos
        $this->cleanObsoleteData();
        
        // 5. Analizar estadísticas de tablas
        $this->analyzeTableStatistics();
        
        // 6. Verificar configuración de caché
        $this->optimizeCacheSettings();
        
        $this->results['end_time'] = microtime(true);
        $this->results['total_time'] = round($this->results['end_time'] - $this->results['start_time'], 3);
        
        return $this->results;
    }
    
    /**
     * Optimizar tablas principales
     */
    private function optimizeTables() {
        $startTime = microtime(true);
        $tables = ['hoteles', 'reviews', 'ai_providers', 'external_apis', 'extraction_jobs', 'prompts', 'system_logs'];
        
        $optimized = [];
        $errors = [];
        
        foreach ($tables as $table) {
            try {
                // Verificar si la tabla existe
                $stmt = $this->pdo->query("SHOW TABLES LIKE '$table'");
                if ($stmt->rowCount() == 0) {
                    continue; // Tabla no existe, saltar
                }
                
                // OPTIMIZE TABLE
                $this->pdo->query("OPTIMIZE TABLE `$table`");
                
                // ANALYZE TABLE para actualizar estadísticas
                $this->pdo->query("ANALYZE TABLE `$table`");
                
                // Obtener información de la tabla
                $stmt = $this->pdo->query("
                    SELECT 
                        table_name,
                        engine,
                        table_rows,
                        data_length,
                        index_length,
                        data_free
                    FROM information_schema.tables 
                    WHERE table_schema = '$this->db_name' AND table_name = '$table'
                ");
                
                $tableInfo = $stmt->fetch();
                $optimized[] = [
                    'table' => $table,
                    'rows' => $tableInfo['table_rows'] ?? 0,
                    'data_size' => $this->formatBytes($tableInfo['data_length'] ?? 0),
                    'index_size' => $this->formatBytes($tableInfo['index_length'] ?? 0),
                    'free_space' => $this->formatBytes($tableInfo['data_free'] ?? 0)
                ];
                
            } catch (Exception $e) {
                $errors[] = "Error optimizando $table: " . $e->getMessage();
            }
        }
        
        $this->results['optimizations']['tables'] = [
            'status' => 'completed',
            'time' => round(microtime(true) - $startTime, 3),
            'optimized_tables' => $optimized,
            'errors' => $errors,
            'total_tables' => count($optimized)
        ];
    }
    
    /**
     * Crear índices optimizados
     */
    private function createOptimizedIndexes() {
        $startTime = microtime(true);
        $indexes = [];
        $created = 0;
        $errors = [];
        
        // Índices para tabla hoteles (si existe)
        $hotelIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_hoteles_activo ON hoteles(activo)",
            "CREATE INDEX IF NOT EXISTS idx_hoteles_nombre ON hoteles(nombre_hotel)",
            "CREATE INDEX IF NOT EXISTS idx_hoteles_created ON hoteles(created_at)"
        ];
        
        // Índices para tabla reviews (si existe)
        $reviewIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_reviews_hotel ON reviews(hotel_name)",
            "CREATE INDEX IF NOT EXISTS idx_reviews_date ON reviews(review_date)",
            "CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(rating)",
            "CREATE INDEX IF NOT EXISTS idx_reviews_composite ON reviews(hotel_name, review_date)"
        ];
        
        // Índices para tabla ai_providers
        $aiProviderIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_providers_status ON ai_providers(status)",
            "CREATE INDEX IF NOT EXISTS idx_providers_type ON ai_providers(provider_type)",
            "CREATE INDEX IF NOT EXISTS idx_providers_active ON ai_providers(is_active)"
        ];
        
        // Índices para tabla external_apis
        $externalApiIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_external_status ON external_apis(status)",
            "CREATE INDEX IF NOT EXISTS idx_external_type ON external_apis(provider_type)",
            "CREATE INDEX IF NOT EXISTS idx_external_priority ON external_apis(priority)"
        ];
        
        // Índices para tabla extraction_jobs
        $extractionIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_extraction_status ON extraction_jobs(status)",
            "CREATE INDEX IF NOT EXISTS idx_extraction_created ON extraction_jobs(created_at)",
            "CREATE INDEX IF NOT EXISTS idx_extraction_provider ON extraction_jobs(api_provider_id)"
        ];
        
        // Índices para tabla prompts
        $promptIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_prompts_status ON prompts(status)",
            "CREATE INDEX IF NOT EXISTS idx_prompts_category ON prompts(category)",
            "CREATE INDEX IF NOT EXISTS idx_prompts_language ON prompts(language)",
            "CREATE INDEX IF NOT EXISTS idx_prompts_updated ON prompts(updated_at)"
        ];
        
        // Índices para tabla system_logs
        $logIndexes = [
            "CREATE INDEX IF NOT EXISTS idx_logs_level ON system_logs(level)",
            "CREATE INDEX IF NOT EXISTS idx_logs_module ON system_logs(module)",
            "CREATE INDEX IF NOT EXISTS idx_logs_timestamp ON system_logs(timestamp)",
            "CREATE INDEX IF NOT EXISTS idx_logs_composite ON system_logs(level, module, timestamp)"
        ];
        
        $allIndexes = array_merge(
            $hotelIndexes, 
            $reviewIndexes, 
            $aiProviderIndexes,
            $externalApiIndexes,
            $extractionIndexes,
            $promptIndexes,
            $logIndexes
        );
        
        foreach ($allIndexes as $indexQuery) {
            try {
                $this->pdo->query($indexQuery);
                $created++;
                
                // Extraer nombre de la tabla del query
                preg_match('/ON\s+(\w+)/', $indexQuery, $matches);
                $table = $matches[1] ?? 'unknown';
                
                $indexes[] = [
                    'table' => $table,
                    'query' => $indexQuery,
                    'status' => 'created'
                ];
                
            } catch (Exception $e) {
                $errors[] = "Error creando índice: " . $e->getMessage();
            }
        }
        
        $this->results['optimizations']['indexes'] = [
            'status' => 'completed',
            'time' => round(microtime(true) - $startTime, 3),
            'total_indexes' => count($allIndexes),
            'created_indexes' => $created,
            'errors' => $errors,
            'details' => $indexes
        ];
    }
    
    /**
     * Optimizar configuración de MySQL
     */
    private function optimizeMySQLSettings() {
        $startTime = microtime(true);
        $recommendations = [];
        $warnings = [];
        
        try {
            // Verificar variables importantes de MySQL
            $variables = [
                'innodb_buffer_pool_size',
                'query_cache_size',
                'max_connections',
                'innodb_log_file_size',
                'key_buffer_size'
            ];
            
            foreach ($variables as $variable) {
                try {
                    $stmt = $this->pdo->query("SHOW VARIABLES LIKE '$variable'");
                    $result = $stmt->fetch();
                    
                    if ($result) {
                        $value = $result['Value'];
                        $recommendations[] = [
                            'variable' => $variable,
                            'current_value' => $value,
                            'recommendation' => $this->getMySQLRecommendation($variable, $value)
                        ];
                    }
                } catch (Exception $e) {
                    $warnings[] = "No se pudo verificar $variable: " . $e->getMessage();
                }
            }
            
            // Verificar estado de índices
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Handler_read%'");
            $handlerStats = $stmt->fetchAll();
            
            $this->results['optimizations']['mysql_settings'] = [
                'status' => 'completed',
                'time' => round(microtime(true) - $startTime, 3),
                'recommendations' => $recommendations,
                'warnings' => $warnings,
                'handler_stats' => $handlerStats
            ];
            
        } catch (Exception $e) {
            $this->results['optimizations']['mysql_settings'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Limpiar datos obsoletos
     */
    private function cleanObsoleteData() {
        $startTime = microtime(true);
        $cleaned = [];
        
        try {
            // Limpiar logs antiguos (más de 90 días)
            $stmt = $this->pdo->query("
                SELECT COUNT(*) as count 
                FROM system_logs 
                WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)
            ");
            $oldLogs = $stmt->fetch()['count'] ?? 0;
            
            if ($oldLogs > 0) {
                $this->pdo->query("
                    DELETE FROM system_logs 
                    WHERE timestamp < DATE_SUB(NOW(), INTERVAL 90 DAY)
                ");
                $cleaned[] = [
                    'table' => 'system_logs',
                    'action' => 'deleted_old_records',
                    'count' => $oldLogs
                ];
            }
            
            // Limpiar trabajos de extracción completados antiguos (más de 30 días)
            try {
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) as count 
                    FROM extraction_jobs 
                    WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                ");
                $oldJobs = $stmt->fetch()['count'] ?? 0;
                
                if ($oldJobs > 0) {
                    $this->pdo->query("
                        DELETE FROM extraction_jobs 
                        WHERE status = 'completed' AND completed_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
                    ");
                    $cleaned[] = [
                        'table' => 'extraction_jobs',
                        'action' => 'deleted_old_completed',
                        'count' => $oldJobs
                    ];
                }
            } catch (Exception $e) {
                // La tabla no existe, continuar
            }
            
            $this->results['optimizations']['cleanup'] = [
                'status' => 'completed',
                'time' => round(microtime(true) - $startTime, 3),
                'cleaned_data' => $cleaned
            ];
            
        } catch (Exception $e) {
            $this->results['optimizations']['cleanup'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analizar estadísticas de tablas
     */
    private function analyzeTableStatistics() {
        $startTime = microtime(true);
        
        try {
            $stmt = $this->pdo->query("
                SELECT 
                    table_name,
                    engine,
                    table_rows,
                    ROUND(data_length / 1024 / 1024, 2) as data_size_mb,
                    ROUND(index_length / 1024 / 1024, 2) as index_size_mb,
                    ROUND(data_free / 1024 / 1024, 2) as free_space_mb,
                    table_collation
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
                ORDER BY data_length DESC
            ");
            
            $tableStats = $stmt->fetchAll();
            $totalDataSize = 0;
            $totalIndexSize = 0;
            $totalFreeSpace = 0;
            
            foreach ($tableStats as $table) {
                $totalDataSize += $table['data_size_mb'];
                $totalIndexSize += $table['index_size_mb'];
                $totalFreeSpace += $table['free_space_mb'];
            }
            
            $this->results['optimizations']['statistics'] = [
                'status' => 'completed',
                'time' => round(microtime(true) - $startTime, 3),
                'table_stats' => $tableStats,
                'summary' => [
                    'total_tables' => count($tableStats),
                    'total_data_size_mb' => round($totalDataSize, 2),
                    'total_index_size_mb' => round($totalIndexSize, 2),
                    'total_free_space_mb' => round($totalFreeSpace, 2),
                    'database_size_mb' => round($totalDataSize + $totalIndexSize, 2)
                ]
            ];
            
        } catch (Exception $e) {
            $this->results['optimizations']['statistics'] = [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Optimizar configuración de caché
     */
    private function optimizeCacheSettings() {
        $startTime = microtime(true);
        $cacheRecommendations = [];
        
        // Verificar query cache
        try {
            $stmt = $this->pdo->query("SHOW VARIABLES LIKE 'query_cache%'");
            $queryCacheVars = $stmt->fetchAll();
            
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Qcache%'");
            $queryCacheStats = $stmt->fetchAll();
            
            $cacheRecommendations[] = [
                'type' => 'query_cache',
                'variables' => $queryCacheVars,
                'statistics' => $queryCacheStats,
                'recommendation' => 'Query cache puede mejorar rendimiento para consultas repetitivas'
            ];
            
        } catch (Exception $e) {
            $cacheRecommendations[] = [
                'type' => 'query_cache',
                'error' => $e->getMessage()
            ];
        }
        
        // Recomendaciones de caché de aplicación
        $appCacheRecommendations = [
            [
                'type' => 'application_cache',
                'recommendation' => 'Implementar Redis/Memcached para caché de sesiones',
                'benefit' => 'Reduce carga de base de datos en consultas frecuentes'
            ],
            [
                'type' => 'browser_cache',
                'recommendation' => 'Configurar headers de cache HTTP para recursos estáticos',
                'benefit' => 'Mejora tiempo de carga de CSS/JS'
            ],
            [
                'type' => 'opcode_cache',
                'recommendation' => 'Verificar que OPcache esté habilitado en PHP',
                'benefit' => 'Acelera ejecución de scripts PHP'
            ]
        ];
        
        $this->results['optimizations']['cache'] = [
            'status' => 'completed',
            'time' => round(microtime(true) - $startTime, 3),
            'database_cache' => $cacheRecommendations,
            'application_cache' => $appCacheRecommendations
        ];
    }
    
    /**
     * Obtener recomendación para variable de MySQL
     */
    private function getMySQLRecommendation($variable, $value) {
        $recommendations = [
            'innodb_buffer_pool_size' => 'Debería ser 70-80% de la RAM disponible',
            'query_cache_size' => 'Recomendado: 16-32MB para aplicaciones pequeñas',
            'max_connections' => 'Ajustar según el número de usuarios concurrentes esperados',
            'innodb_log_file_size' => 'Recomendado: 25% del innodb_buffer_pool_size',
            'key_buffer_size' => 'Para tablas MyISAM: 25% de la RAM'
        ];
        
        return $recommendations[$variable] ?? 'Verificar documentación oficial de MySQL';
    }
    
    /**
     * Formatear bytes a formato legible
     */
    private function formatBytes($size, $precision = 2) {
        if ($size == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor(log($size, 1024));
        
        return round($size / pow(1024, $factor), $precision) . ' ' . $units[$factor];
    }
    
    /**
     * Obtener estado general del sistema
     */
    public function getSystemStatus() {
        $status = [
            'timestamp' => date('Y-m-d H:i:s'),
            'database' => [],
            'performance' => [],
            'recommendations' => []
        ];
        
        try {
            // Estado de la base de datos
            $stmt = $this->pdo->query("SELECT VERSION() as version");
            $status['database']['mysql_version'] = $stmt->fetch()['version'];
            
            $stmt = $this->pdo->query("SELECT DATABASE() as database_name");
            $status['database']['current_database'] = $stmt->fetch()['database_name'];
            
            $stmt = $this->pdo->query("SHOW STATUS LIKE 'Uptime'");
            $uptime = $stmt->fetch()['Value'];
            $status['database']['uptime_hours'] = round($uptime / 3600, 2);
            
            // Estadísticas de rendimiento
            $stmt = $this->pdo->query("
                SELECT 
                    SUM(data_length + index_length) as total_size,
                    COUNT(*) as total_tables
                FROM information_schema.tables 
                WHERE table_schema = DATABASE()
            ");
            $dbStats = $stmt->fetch();
            
            $status['performance'] = [
                'database_size' => $this->formatBytes($dbStats['total_size']),
                'total_tables' => $dbStats['total_tables'],
                'optimization_needed' => $dbStats['total_size'] > 100 * 1024 * 1024 // > 100MB
            ];
            
            // Recomendaciones básicas
            if ($status['performance']['optimization_needed']) {
                $status['recommendations'][] = 'La base de datos es grande, considerar optimización regular';
            }
            
            $status['recommendations'][] = 'Ejecutar OPTIMIZE TABLE mensualmente';
            $status['recommendations'][] = 'Configurar backups automáticos';
            $status['recommendations'][] = 'Monitorear logs de error de MySQL';
            
        } catch (Exception $e) {
            $status['error'] = $e->getMessage();
        }
        
        return $status;
    }
}

// Procesar acción
try {
    $optimizer = new PerformanceOptimizer($pdo);
    
    switch($action) {
        case 'optimize':
            $results = $optimizer->runAllOptimizations();
            sendResponse([
                'success' => true,
                'message' => 'Optimización completada',
                'data' => $results
            ]);
            break;
            
        case 'status':
        default:
            $status = $optimizer->getSystemStatus();
            sendResponse([
                'success' => true,
                'message' => 'Estado del sistema obtenido',
                'data' => $status
            ]);
            break;
    }
    
} catch(Exception $e) {
    sendError('Error en optimización', $e->getMessage());
}
?>