<?php
/**
 * Optimización de Performance - Queries Unificadas
 * 
 * Script para optimizar el rendimiento del sistema unificado mediante:
 * - Análisis de índices actuales
 * - Creación de índices optimizados
 * - Vistas materializadas para queries frecuentes  
 * - Configuración de caché de queries
 */

require_once 'env-loader.php';

class QueryOptimizer 
{
    private $pdo;
    private $logFile;
    private $beforeStats = [];
    private $afterStats = [];
    
    public function __construct() 
    {
        $this->pdo = createDatabaseConnection();
        $this->logFile = __DIR__ . '/storage/logs/performance-optimization.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() 
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Ejecutar optimización completa
     */
    public function optimize() 
    {
        $this->log("🚀 Iniciando optimización de performance...");
        
        // 1. Baseline performance
        $this->log("📊 Midiendo performance inicial...");
        $this->beforeStats = $this->measurePerformance();
        
        // 2. Analizar índices existentes
        $this->log("🔍 Analizando índices existentes...");
        $currentIndexes = $this->analyzeCurrentIndexes();
        
        // 3. Crear índices optimizados
        $this->log("⚡ Creando índices optimizados...");
        $this->createOptimizedIndexes();
        
        // 4. Crear vistas materializadas
        $this->log("📋 Creando vistas para queries frecuentes...");
        $this->createOptimizedViews();
        
        // 5. Optimizar configuración de MySQL
        $this->log("🔧 Aplicando configuraciones de MySQL...");
        $this->optimizeMysqlConfig();
        
        // 6. Medir performance después
        $this->log("📈 Midiendo performance post-optimización...");
        $this->afterStats = $this->measurePerformance();
        
        // 7. Generar reporte
        $report = $this->generateReport();
        $this->saveReport($report);
        
        $this->log("✅ Optimización completada - Ver reporte detallado");
        
        return $report;
    }
    
    /**
     * Medir performance de queries críticas
     */
    private function measurePerformance() 
    {
        $queries = [
            'unified_rating_avg' => [
                'sql' => 'SELECT AVG(COALESCE(rating, normalized_rating)) as avg_rating FROM reviews',
                'description' => 'Promedio de rating unificado'
            ],
            'unified_guest_list' => [
                'sql' => 'SELECT COALESCE(user_name, reviewer_name) as guest, COALESCE(source_platform, platform) as platform FROM reviews LIMIT 100',
                'description' => 'Lista de huéspedes con plataforma unificada'
            ],
            'high_rating_filter' => [
                'sql' => 'SELECT * FROM reviews WHERE COALESCE(rating, normalized_rating) > 8.0 LIMIT 50',
                'description' => 'Filtro por rating alto'
            ],
            'source_statistics' => [
                'sql' => 'SELECT COALESCE(extraction_source, "legacy") as source, COUNT(*) as count, AVG(COALESCE(rating, normalized_rating)) as avg_rating FROM reviews GROUP BY COALESCE(extraction_source, "legacy")',
                'description' => 'Estadísticas por fuente'
            ],
            'platform_breakdown' => [
                'sql' => 'SELECT COALESCE(source_platform, platform) as platform, COUNT(*) as count FROM reviews GROUP BY COALESCE(source_platform, platform)',
                'description' => 'Distribución por plataforma'
            ],
            'recent_reviews' => [
                'sql' => 'SELECT * FROM reviews WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAYS) ORDER BY scraped_at DESC LIMIT 20',
                'description' => 'Reviews recientes (últimos 7 días)'
            ],
            'text_search' => [
                'sql' => 'SELECT * FROM reviews WHERE COALESCE(review_text, liked_text, disliked_text) LIKE "%excelente%" LIMIT 10',
                'description' => 'Búsqueda en texto de review'
            ],
            'response_filter' => [
                'sql' => 'SELECT * FROM reviews WHERE COALESCE(property_response, response_from_owner) IS NOT NULL LIMIT 25',
                'description' => 'Reviews con respuesta del hotel'
            ]
        ];
        
        $stats = [];
        
        foreach ($queries as $name => $queryInfo) {
            try {
                // Warm up
                $this->pdo->query($queryInfo['sql']);
                
                // Measure
                $start = microtime(true);
                $stmt = $this->pdo->query($queryInfo['sql']);
                $result = $stmt->fetchAll();
                $time = (microtime(true) - $start) * 1000;
                
                $stats[$name] = [
                    'time_ms' => round($time, 2),
                    'rows' => count($result),
                    'description' => $queryInfo['description']
                ];
                
            } catch (Exception $e) {
                $stats[$name] = [
                    'error' => $e->getMessage(),
                    'description' => $queryInfo['description']
                ];
            }
        }
        
        return $stats;
    }
    
    /**
     * Analizar índices actuales
     */
    private function analyzeCurrentIndexes() 
    {
        $stmt = $this->pdo->query("SHOW INDEX FROM reviews");
        $indexes = $stmt->fetchAll();
        
        $indexAnalysis = [
            'total_indexes' => count($indexes),
            'unique_indexes' => 0,
            'composite_indexes' => 0,
            'indexes_by_column' => []
        ];
        
        foreach ($indexes as $index) {
            if ($index['Non_unique'] == 0) {
                $indexAnalysis['unique_indexes']++;
            }
            
            if (!isset($indexAnalysis['indexes_by_column'][$index['Column_name']])) {
                $indexAnalysis['indexes_by_column'][$index['Column_name']] = [];
            }
            
            $indexAnalysis['indexes_by_column'][$index['Column_name']][] = $index['Key_name'];
        }
        
        $this->log("📊 Índices actuales: " . $indexAnalysis['total_indexes']);
        
        return $indexAnalysis;
    }
    
    /**
     * Crear índices optimizados para queries unificadas
     */
    private function createOptimizedIndexes() 
    {
        $indexes = [
            // Índices para campos unificados más consultados
            'idx_unified_rating' => 'CREATE INDEX idx_unified_rating ON reviews (
                (COALESCE(rating, normalized_rating))
            )',
            
            // Índice compuesto para filtros frecuentes
            'idx_hotel_rating_date' => 'CREATE INDEX idx_hotel_rating_date ON reviews (
                hotel_id, 
                (COALESCE(rating, normalized_rating)), 
                scraped_at
            )',
            
            // Índice para fuentes de extracción
            'idx_extraction_source_unified' => 'CREATE INDEX idx_extraction_source_unified ON reviews (
                (COALESCE(extraction_source, "legacy")),
                scraped_at
            )',
            
            // Índice para plataformas unificadas
            'idx_platform_unified' => 'CREATE INDEX idx_platform_unified ON reviews (
                (COALESCE(source_platform, platform))
            )',
            
            // Índice para búsquedas de texto
            'idx_review_text_search' => 'CREATE FULLTEXT INDEX idx_review_text_search ON reviews (
                review_text, liked_text, disliked_text
            )',
            
            // Índice para reviews con respuesta
            'idx_has_response' => 'CREATE INDEX idx_has_response ON reviews (
                (CASE WHEN COALESCE(property_response, response_from_owner) IS NOT NULL THEN 1 ELSE 0 END)
            )',
            
            // Índice para unique_id (si no existe)
            'idx_unique_id' => 'CREATE UNIQUE INDEX idx_unique_id ON reviews (unique_id)',
            
            // Índice compuesto para API paginación
            'idx_api_pagination' => 'CREATE INDEX idx_api_pagination ON reviews (
                hotel_id,
                scraped_at DESC,
                id
            )'
        ];
        
        $created = 0;
        $skipped = 0;
        
        foreach ($indexes as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Índice creado: $name");
                $created++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false || 
                    strpos($e->getMessage(), 'already exists') !== false) {
                    $this->log("⚠️  Índice ya existe: $name");
                    $skipped++;
                } else {
                    $this->log("❌ Error creando índice $name: " . $e->getMessage());
                }
            }
        }
        
        $this->log("📊 Índices creados: $created, omitidos: $skipped");
        
        return ['created' => $created, 'skipped' => $skipped];
    }
    
    /**
     * Crear vistas optimizadas para queries frecuentes
     */
    private function createOptimizedViews() 
    {
        $views = [
            'reviews_unified' => "
                CREATE OR REPLACE VIEW reviews_unified AS
                SELECT 
                    id,
                    unique_id,
                    hotel_id,
                    COALESCE(user_name, reviewer_name) as guest_name,
                    COALESCE(source_platform, platform) as platform_name,
                    COALESCE(rating, normalized_rating) as unified_rating,
                    COALESCE(review_text, liked_text, disliked_text) as full_review_text,
                    COALESCE(property_response, response_from_owner) as hotel_response,
                    COALESCE(extraction_source, 'legacy') as data_source,
                    scraped_at,
                    review_date,
                    sentiment_score,
                    language_detected as language,
                    is_verified,
                    helpful_votes,
                    tags
                FROM reviews
            ",
            
            'reviews_stats_summary' => "
                CREATE OR REPLACE VIEW reviews_stats_summary AS
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    COALESCE(source_platform, platform) as platform,
                    COUNT(*) as total_reviews,
                    AVG(COALESCE(rating, normalized_rating)) as avg_rating,
                    MIN(scraped_at) as first_review,
                    MAX(scraped_at) as latest_review,
                    COUNT(CASE WHEN COALESCE(property_response, response_from_owner) IS NOT NULL THEN 1 END) as reviews_with_response,
                    COUNT(CASE WHEN COALESCE(rating, normalized_rating) >= 8.0 THEN 1 END) as high_rating_count,
                    COUNT(CASE WHEN scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAYS) THEN 1 END) as recent_reviews
                FROM reviews
                GROUP BY COALESCE(extraction_source, 'legacy'), COALESCE(source_platform, platform)
            ",
            
            'reviews_recent_activity' => "
                CREATE OR REPLACE VIEW reviews_recent_activity AS
                SELECT 
                    DATE(scraped_at) as activity_date,
                    COALESCE(extraction_source, 'legacy') as source,
                    COUNT(*) as reviews_count,
                    AVG(COALESCE(rating, normalized_rating)) as avg_daily_rating
                FROM reviews 
                WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAYS)
                GROUP BY DATE(scraped_at), COALESCE(extraction_source, 'legacy')
                ORDER BY activity_date DESC
            ",
            
            'reviews_high_quality' => "
                CREATE OR REPLACE VIEW reviews_high_quality AS
                SELECT *
                FROM reviews_unified
                WHERE unified_rating >= 8.0 
                  AND full_review_text IS NOT NULL
                  AND LENGTH(full_review_text) > 50
                ORDER BY unified_rating DESC, scraped_at DESC
            "
        ];
        
        $created = 0;
        
        foreach ($views as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Vista creada: $name");
                $created++;
            } catch (PDOException $e) {
                $this->log("❌ Error creando vista $name: " . $e->getMessage());
            }
        }
        
        $this->log("📋 Vistas creadas: $created");
        
        return ['created' => $created];
    }
    
    /**
     * Optimizar configuración de MySQL
     */
    private function optimizeMysqlConfig() 
    {
        $optimizations = [
            // Habilitar query cache si está disponible
            'query_cache_optimization' => "SET GLOBAL query_cache_size = 1048576", // 1MB
            
            // Optimizar configuraciones para COALESCE
            'join_buffer_optimization' => "SET SESSION join_buffer_size = 262144", // 256KB
            
            // Optimizar sort buffer
            'sort_buffer_optimization' => "SET SESSION sort_buffer_size = 524288" // 512KB
        ];
        
        $applied = 0;
        
        foreach ($optimizations as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Optimización aplicada: $name");
                $applied++;
            } catch (PDOException $e) {
                $this->log("⚠️  No se pudo aplicar $name: " . $e->getMessage());
            }
        }
        
        return ['applied' => $applied];
    }
    
    /**
     * Generar reporte de optimización
     */
    private function generateReport() 
    {
        $report = [
            'timestamp' => date('c'),
            'optimization_summary' => [
                'status' => 'completed',
                'duration' => 'N/A',
                'improvements' => []
            ],
            'performance_comparison' => [],
            'recommendations' => []
        ];
        
        // Comparar performance antes/después
        if (!empty($this->beforeStats) && !empty($this->afterStats)) {
            foreach ($this->beforeStats as $queryName => $beforeData) {
                if (isset($this->afterStats[$queryName]) && 
                    isset($beforeData['time_ms']) && 
                    isset($this->afterStats[$queryName]['time_ms'])) {
                    
                    $improvement = $beforeData['time_ms'] - $this->afterStats[$queryName]['time_ms'];
                    $improvementPercent = ($improvement / $beforeData['time_ms']) * 100;
                    
                    $report['performance_comparison'][$queryName] = [
                        'before_ms' => $beforeData['time_ms'],
                        'after_ms' => $this->afterStats[$queryName]['time_ms'],
                        'improvement_ms' => round($improvement, 2),
                        'improvement_percent' => round($improvementPercent, 2),
                        'status' => $improvement > 0 ? 'improved' : ($improvement < 0 ? 'degraded' : 'unchanged')
                    ];
                }
            }
            
            // Calcular mejoras generales
            $totalImprovement = 0;
            $improvedQueries = 0;
            foreach ($report['performance_comparison'] as $comparison) {
                if ($comparison['improvement_percent'] > 0) {
                    $totalImprovement += $comparison['improvement_percent'];
                    $improvedQueries++;
                }
            }
            
            if ($improvedQueries > 0) {
                $report['optimization_summary']['average_improvement'] = round($totalImprovement / $improvedQueries, 2);
                $report['optimization_summary']['improved_queries'] = $improvedQueries;
            }
        }
        
        // Generar recomendaciones
        $report['recommendations'] = $this->generatePerformanceRecommendations();
        
        return $report;
    }
    
    /**
     * Generar recomendaciones de performance
     */
    private function generatePerformanceRecommendations() 
    {
        $recommendations = [];
        
        // Analizar si hay consultas lentas
        foreach ($this->afterStats as $queryName => $stats) {
            if (isset($stats['time_ms']) && $stats['time_ms'] > 100) {
                $recommendations[] = [
                    'type' => 'slow_query',
                    'query' => $queryName,
                    'issue' => "Query '$queryName' toma {$stats['time_ms']}ms",
                    'suggestion' => 'Consider adding more specific indexes or optimizing the query structure'
                ];
            }
        }
        
        // Verificar uso de vistas
        $recommendations[] = [
            'type' => 'usage_optimization',
            'issue' => 'API should use optimized views',
            'suggestion' => 'Update API to use reviews_unified and reviews_stats_summary views for better performance'
        ];
        
        // Monitoreo continuo
        $recommendations[] = [
            'type' => 'monitoring',
            'issue' => 'Performance monitoring needed',
            'suggestion' => 'Set up regular performance monitoring to track query times over time'
        ];
        
        return $recommendations;
    }
    
    /**
     * Guardar reporte
     */
    private function saveReport($report) 
    {
        $reportFile = __DIR__ . '/storage/reports/performance-optimization-report.json';
        $reportDir = dirname($reportFile);
        
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $this->log("📄 Reporte guardado: $reportFile");
    }
    
    /**
     * Crear queries de mantenimiento
     */
    public function generateMaintenanceQueries() 
    {
        $maintenanceFile = __DIR__ . '/maintenance-queries.sql';
        
        $queries = "-- Queries de Mantenimiento para Performance\n";
        $queries .= "-- Generado: " . date('Y-m-d H:i:s') . "\n\n";
        
        $queries .= "-- 1. Analizar y optimizar tablas\n";
        $queries .= "ANALYZE TABLE reviews;\n";
        $queries .= "OPTIMIZE TABLE reviews;\n\n";
        
        $queries .= "-- 2. Verificar uso de índices\n";
        $queries .= "SHOW INDEX FROM reviews;\n\n";
        
        $queries .= "-- 3. Queries de estadísticas rápidas usando vistas\n";
        $queries .= "SELECT * FROM reviews_stats_summary;\n";
        $queries .= "SELECT * FROM reviews_recent_activity LIMIT 7;\n\n";
        
        $queries .= "-- 4. Identificar queries lentas (requiere configuración del slow query log)\n";
        $queries .= "-- SHOW VARIABLES LIKE 'slow_query_log';\n";
        $queries .= "-- SET GLOBAL slow_query_log = 'ON';\n";
        $queries .= "-- SET GLOBAL long_query_time = 1; -- 1 segundo\n\n";
        
        $queries .= "-- 5. Limpieza de datos duplicados (ejecutar con precaución)\n";
        $queries .= "-- SELECT unique_id, COUNT(*) as count FROM reviews GROUP BY unique_id HAVING count > 1;\n\n";
        
        file_put_contents($maintenanceFile, $queries);
        $this->log("🔧 Queries de mantenimiento: $maintenanceFile");
    }
    
    private function log($message) 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        echo $logEntry;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}

// Ejecutar optimización si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'optimize';
    
    try {
        $optimizer = new QueryOptimizer();
        
        switch ($action) {
            case 'optimize':
            case '--optimize':
                $report = $optimizer->optimize();
                
                echo "\n📊 RESUMEN DE OPTIMIZACIÓN:\n";
                echo str_repeat("=", 50) . "\n";
                
                if (isset($report['optimization_summary']['average_improvement'])) {
                    echo "⚡ Mejora promedio: " . $report['optimization_summary']['average_improvement'] . "%\n";
                    echo "📈 Queries mejoradas: " . $report['optimization_summary']['improved_queries'] . "\n";
                }
                
                echo "\n🚀 NEXT STEPS:\n";
                echo "1. Actualizar API para usar vistas optimizadas\n";
                echo "2. Monitorear performance durante 24h\n";
                echo "3. Ejecutar queries de mantenimiento semanalmente\n";
                
                break;
                
            case 'maintenance':
            case '--maintenance':
                $optimizer->generateMaintenanceQueries();
                echo "✅ Queries de mantenimiento generadas\n";
                break;
                
            case 'test':
            case '--test':
                echo "🧪 Testing current performance...\n";
                $stats = $optimizer->measurePerformance();
                foreach ($stats as $name => $data) {
                    if (isset($data['time_ms'])) {
                        $status = $data['time_ms'] < 50 ? '🟢' : ($data['time_ms'] < 150 ? '🟡' : '🔴');
                        echo "$status $name: {$data['time_ms']}ms ({$data['rows']} rows)\n";
                    }
                }
                break;
                
            default:
                echo "Uso: php optimize-queries.php [optimize|maintenance|test]\n";
                echo "  optimize    - Ejecutar optimización completa\n";
                echo "  maintenance - Generar queries de mantenimiento\n";
                echo "  test        - Probar performance actual\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error durante optimización: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>