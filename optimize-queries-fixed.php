<?php
/**
 * Optimización de Performance - MariaDB Compatible
 * Versión corregida para MariaDB con sintaxis compatible
 */

require_once 'env-loader.php';

class MariaDBQueryOptimizer 
{
    private $pdo;
    private $logFile;
    
    public function __construct() 
    {
        $this->pdo = createDatabaseConnection();
        $this->logFile = __DIR__ . '/storage/logs/mariadb-optimization.log';
    }
    
    /**
     * Crear índices compatibles con MariaDB
     */
    public function createMariaDBIndexes() 
    {
        $this->log("🚀 Creando índices optimizados para MariaDB...");
        
        $indexes = [
            // Índices simples en columnas principales
            'idx_rating' => 'CREATE INDEX idx_rating ON reviews (rating)',
            'idx_normalized_rating' => 'CREATE INDEX idx_normalized_rating ON reviews (normalized_rating)',
            'idx_extraction_source' => 'CREATE INDEX idx_extraction_source ON reviews (extraction_source)',
            'idx_source_platform' => 'CREATE INDEX idx_source_platform ON reviews (source_platform)',
            'idx_platform' => 'CREATE INDEX idx_platform ON reviews (platform)',
            'idx_scraped_at' => 'CREATE INDEX idx_scraped_at ON reviews (scraped_at)',
            'idx_hotel_scraped' => 'CREATE INDEX idx_hotel_scraped ON reviews (hotel_id, scraped_at)',
            
            // Índice para búsquedas de texto (ya existe)
            // 'idx_review_text_search' => 'CREATE FULLTEXT INDEX idx_review_text_search ON reviews (review_text, liked_text, disliked_text)',
            
            // Índices compuestos para queries comunes
            'idx_hotel_rating' => 'CREATE INDEX idx_hotel_rating ON reviews (hotel_id, rating)',
            'idx_platform_date' => 'CREATE INDEX idx_platform_date ON reviews (platform, scraped_at)',
            'idx_source_date' => 'CREATE INDEX idx_source_date ON reviews (extraction_source, scraped_at)',
            
            // Índice para respuestas
            'idx_property_response' => 'CREATE INDEX idx_property_response ON reviews (property_response(100))',
            'idx_response_from_owner' => 'CREATE INDEX idx_response_from_owner ON reviews (response_from_owner(100))'
        ];
        
        $created = 0;
        $skipped = 0;
        
        foreach ($indexes as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Índice creado: $name");
                $created++;
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                    $this->log("⚠️  Índice ya existe: $name");
                    $skipped++;
                } else {
                    $this->log("❌ Error: $name - " . $e->getMessage());
                }
            }
        }
        
        $this->log("📊 Índices - Creados: $created, Omitidos: $skipped");
        return ['created' => $created, 'skipped' => $skipped];
    }
    
    /**
     * Crear vistas MariaDB compatibles
     */
    public function createMariaDBViews() 
    {
        $this->log("📋 Creando vistas optimizadas...");
        
        $views = [
            'reviews_stats_summary' => "
                CREATE OR REPLACE VIEW reviews_stats_summary AS
                SELECT 
                    COALESCE(extraction_source, 'legacy') as source,
                    COALESCE(source_platform, platform) as platform,
                    COUNT(*) as total_reviews,
                    AVG(COALESCE(rating, normalized_rating)) as avg_rating,
                    MIN(scraped_at) as first_review,
                    MAX(scraped_at) as latest_review,
                    COUNT(CASE WHEN COALESCE(property_response, response_from_owner) IS NOT NULL THEN 1 ELSE NULL END) as reviews_with_response,
                    COUNT(CASE WHEN COALESCE(rating, normalized_rating) >= 8.0 THEN 1 ELSE NULL END) as high_rating_count,
                    COUNT(CASE WHEN scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE NULL END) as recent_reviews
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
                WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(scraped_at), COALESCE(extraction_source, 'legacy')
                ORDER BY activity_date DESC
            "
        ];
        
        $created = 0;
        
        foreach ($views as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Vista creada: $name");
                $created++;
            } catch (PDOException $e) {
                $this->log("❌ Error vista $name: " . $e->getMessage());
            }
        }
        
        return ['created' => $created];
    }
    
    /**
     * Optimizar API para usar vistas
     */
    public function optimizeApiQueries() 
    {
        $this->log("⚡ Optimizando consultas de API...");
        
        // Crear stored procedures para queries frecuentes
        $procedures = [
            'get_stats_summary' => "
                CREATE OR REPLACE PROCEDURE get_stats_summary()
                BEGIN
                    SELECT * FROM reviews_stats_summary;
                END
            ",
            
            'get_recent_activity' => "
                CREATE OR REPLACE PROCEDURE get_recent_activity(IN days_back INT)
                BEGIN
                    SELECT * FROM reviews_recent_activity 
                    WHERE activity_date >= DATE_SUB(CURDATE(), INTERVAL days_back DAY)
                    ORDER BY activity_date DESC;
                END
            ",
            
            'get_reviews_optimized' => "
                CREATE OR REPLACE PROCEDURE get_reviews_optimized(
                    IN hotel_filter INT,
                    IN platform_filter VARCHAR(50),
                    IN rating_min DECIMAL(3,2),
                    IN rating_max DECIMAL(3,2),
                    IN limit_count INT,
                    IN offset_count INT
                )
                BEGIN
                    SELECT 
                        unique_id,
                        COALESCE(user_name, reviewer_name) as guest_name,
                        COALESCE(source_platform, platform) as platform_name,
                        COALESCE(rating, normalized_rating) as unified_rating,
                        review_date,
                        scraped_at,
                        COALESCE(extraction_source, 'legacy') as data_source,
                        hotel_id,
                        is_verified,
                        sentiment_score
                    FROM reviews_unified
                    WHERE (hotel_filter IS NULL OR hotel_id = hotel_filter)
                      AND (platform_filter IS NULL OR platform_name = platform_filter)
                      AND (rating_min IS NULL OR unified_rating >= rating_min)
                      AND (rating_max IS NULL OR unified_rating <= rating_max)
                    ORDER BY scraped_at DESC
                    LIMIT limit_count OFFSET offset_count;
                END
            "
        ];
        
        $created = 0;
        
        foreach ($procedures as $name => $sql) {
            try {
                $this->pdo->exec($sql);
                $this->log("✅ Procedure creado: $name");
                $created++;
            } catch (PDOException $e) {
                $this->log("❌ Error procedure $name: " . $e->getMessage());
            }
        }
        
        return ['created' => $created];
    }
    
    /**
     * Test de performance mejorado
     */
    public function testOptimizedPerformance() 
    {
        $this->log("🧪 Testing performance post-optimización...");
        
        $tests = [
            'Vista stats' => 'SELECT * FROM reviews_stats_summary',
            'Vista actividad' => 'SELECT * FROM reviews_recent_activity LIMIT 10',
            'Query básica con índices' => 'SELECT hotel_id, rating FROM reviews WHERE hotel_id = 6 AND rating > 8',
            'Búsqueda de texto optimizada' => 'SELECT * FROM reviews WHERE MATCH(review_text) AGAINST("excelente" IN NATURAL LANGUAGE MODE) LIMIT 5',
            'Unificado con vistas' => 'SELECT * FROM reviews_unified WHERE unified_rating > 9 LIMIT 10'
        ];
        
        $results = [];
        
        foreach ($tests as $name => $query) {
            try {
                $start = microtime(true);
                $stmt = $this->pdo->query($query);
                $result = $stmt->fetchAll();
                $time = round((microtime(true) - $start) * 1000, 2);
                
                $status = $time < 50 ? '🟢' : ($time < 150 ? '🟡' : '🔴');
                $results[$name] = [
                    'time' => $time,
                    'rows' => count($result),
                    'status' => $status
                ];
                
                $this->log("$status $name: {$time}ms (" . count($result) . " rows)");
                
            } catch (Exception $e) {
                $this->log("❌ Error en $name: " . $e->getMessage());
            }
        }
        
        return $results;
    }
    
    private function log($message) 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message\n";
        echo $logEntry;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $optimizer = new MariaDBQueryOptimizer();
        
        echo "🚀 OPTIMIZACIÓN MARIADB - REVIEWS UNIFICADAS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        // 1. Crear índices
        $indexResult = $optimizer->createMariaDBIndexes();
        
        // 2. Crear vistas
        $viewResult = $optimizer->createMariaDBViews();
        
        // 3. Crear stored procedures
        $procedureResult = $optimizer->optimizeApiQueries();
        
        // 4. Test performance
        echo "\n📊 TESTING PERFORMANCE:\n";
        $performanceResults = $optimizer->testOptimizedPerformance();
        
        // Resumen final
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "✅ OPTIMIZACIÓN COMPLETADA\n\n";
        echo "📊 Índices creados: " . $indexResult['created'] . "\n";
        echo "📋 Vistas creadas: " . $viewResult['created'] . "\n";
        echo "🔧 Procedures creados: " . $procedureResult['created'] . "\n";
        
        // Calcular mejora promedio
        $fastQueries = 0;
        foreach ($performanceResults as $result) {
            if ($result['time'] < 50) $fastQueries++;
        }
        
        $percentFast = round(($fastQueries / count($performanceResults)) * 100);
        echo "⚡ Queries optimizadas: $percentFast% son < 50ms\n";
        
        echo "\n🚀 NEXT STEPS:\n";
        echo "1. ✅ Sistema optimizado y listo\n";  
        echo "2. 📋 Usar vistas en lugar de queries complejas\n";
        echo "3. 🔍 Monitorear performance durante 24h\n";
        
    } catch (Exception $e) {
        echo "❌ Error durante optimización: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>