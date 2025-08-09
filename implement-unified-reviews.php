<?php
/**
 * Script para implementar el esquema unificado de reviews
 * - Crea tabla reviews_final
 * - Migra datos existentes
 * - Crea vistas de compatibilidad
 * - Genera reporte de migración
 */

require_once 'env-loader.php';
require_once 'ReviewsSchemaAdapter.php';

echo "🚀 IMPLEMENTACIÓN DE ESQUEMA UNIFICADO DE REVIEWS\n";
echo str_repeat("=", 60) . "\n\n";

$startTime = time();

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    echo "✅ Conexión a BD establecida\n\n";
    
    // Paso 1: Crear tabla reviews_final
    echo "📋 1. CREANDO ESQUEMA FINAL...\n";
    echo str_repeat("-", 40) . "\n";
    
    $schemaSQL = file_get_contents('reviews-final-schema.sql');
    if (!$schemaSQL) {
        throw new Exception("No se pudo leer el archivo de esquema");
    }
    
    // Ejecutar SQL por bloques (separar por ';')
    $statements = array_filter(explode(';', $schemaSQL));
    $executed = 0;
    
    foreach ($statements as $sql) {
        $sql = trim($sql);
        if (empty($sql) || strpos($sql, '/*') === 0 || strpos($sql, '--') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($sql);
            $executed++;
        } catch (PDOException $e) {
            // Ignorar errores de "ya existe"
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate') === false) {
                echo "⚠️ Error ejecutando SQL: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "✅ Esquema creado ($executed declaraciones ejecutadas)\n\n";
    
    // Paso 2: Verificar tabla principal existe
    echo "📋 2. VERIFICANDO TABLA PRINCIPAL...\n";
    echo str_repeat("-", 40) . "\n";
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'reviews_final'");
    if ($stmt->rowCount() == 0) {
        throw new Exception("Tabla reviews_final no fue creada correctamente");
    }
    
    $stmt = $pdo->query("DESCRIBE reviews_final");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tabla reviews_final creada con " . count($columns) . " columnas\n";
    echo "Columnas principales: " . implode(', ', array_slice($columns, 0, 10)) . "...\n\n";
    
    // Paso 3: Inicializar adaptador
    echo "📋 3. INICIALIZANDO ADAPTADOR...\n";
    echo str_repeat("-", 40) . "\n";
    
    $adapter = new ReviewsSchemaAdapter($pdo, true);
    echo "✅ ReviewsSchemaAdapter inicializado\n\n";
    
    // Paso 4: Migrar datos desde tablas existentes
    echo "📋 4. MIGRANDO DATOS EXISTENTES...\n";
    echo str_repeat("-", 40) . "\n";
    
    // Obtener tablas de reviews para migrar (excluyendo backups y vistas)
    $stmt = $pdo->query("SHOW TABLES LIKE '%review%'");
    $allReviewTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Filtrar tablas que no queremos migrar
    $skipTables = [
        'reviews_final',           // Tabla destino
        'reviews_backup_%',        // Backups
        'reviews_stats%',          // Estadísticas
        'reviews_recent_activity', // Vista materializada
        'review_alerts',           // Solo alertas
        'review_analysis',         // Solo análisis
        'review_extractions',      // Solo logs de extracción
        'review_platforms',        // Solo configuración
        'hotel_review_metrics'     // Solo métricas
    ];
    
    $tablesToMigrate = [];
    foreach ($allReviewTables as $table) {
        $shouldSkip = false;
        foreach ($skipTables as $pattern) {
            if (fnmatch($pattern, $table)) {
                $shouldSkip = true;
                break;
            }
        }
        if (!$shouldSkip) {
            $tablesToMigrate[] = $table;
        }
    }
    
    echo "Tablas a migrar: " . implode(', ', $tablesToMigrate) . "\n\n";
    
    $totalMigrated = 0;
    $totalErrors = 0;
    $migrationResults = [];
    
    foreach ($tablesToMigrate as $table) {
        echo "🔄 Migrando desde $table...\n";
        
        try {
            $result = $adapter->migrateFromLegacyTable($table, 50);
            $totalMigrated += $result['migrated'];
            $totalErrors += $result['errors'];
            $migrationResults[$table] = $result;
            echo "✅ $table: {$result['migrated']} migradas, {$result['errors']} errores\n";
        } catch (Exception $e) {
            echo "❌ Error migrando $table: " . $e->getMessage() . "\n";
            $migrationResults[$table] = ['migrated' => 0, 'errors' => 1, 'error' => $e->getMessage()];
        }
        
        echo "\n";
    }
    
    echo "📊 RESUMEN DE MIGRACIÓN:\n";
    echo "Total migradas: $totalMigrated\n";
    echo "Total errores: $totalErrors\n\n";
    
    // Paso 5: Crear vistas de compatibilidad
    echo "📋 5. CREANDO VISTAS DE COMPATIBILIDAD...\n";
    echo str_repeat("-", 40) . "\n";
    
    $compatibilityViews = [
        'reviews_legacy_compat' => "
            CREATE OR REPLACE VIEW reviews_legacy_compat AS
            SELECT 
                id, unique_id, hotel_id,
                user_name, user_name as reviewer_name,
                review_text, liked_text, disliked_text,
                rating, normalized_rating,
                source_platform, source_platform as platform,
                property_response, property_response as response_from_owner,
                review_date, scraped_at,
                hotel_name, hotel_destination,
                extraction_run_id, extraction_status,
                is_verified, helpful_votes, review_language,
                created_at, updated_at
            FROM reviews_final
        ",
        'reviews_unified_compat' => "
            CREATE OR REPLACE VIEW reviews_unified_compat AS
            SELECT 
                id, unique_id, hotel_id,
                user_name as guest_name,
                source_platform as platform_name,
                normalized_rating as unified_rating,
                COALESCE(review_text, CONCAT_WS(' ', liked_text, disliked_text)) as full_review_text,
                property_response as hotel_response,
                extraction_source as data_source,
                scraped_at, review_date, sentiment_score,
                review_language as language,
                is_verified, helpful_votes, tags
            FROM reviews_final
        ",
        'recent_reviews_compat' => "
            CREATE OR REPLACE VIEW recent_reviews_compat AS
            SELECT 
                COALESCE(hotel_name, 'N/A') as hotel_name,
                COALESCE(hotel_destination, 'N/A') as hotel_destination,
                user_name, rating, review_title,
                liked_text, disliked_text, review_date,
                traveler_type as traveler_type_spanish
            FROM reviews_final 
            WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        "
    ];
    
    foreach ($compatibilityViews as $viewName => $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Vista $viewName creada\n";
        } catch (PDOException $e) {
            echo "❌ Error creando vista $viewName: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // Paso 6: Generar estadísticas finales
    echo "📋 6. ESTADÍSTICAS FINALES...\n";
    echo str_repeat("-", 40) . "\n";
    
    // Contar registros en reviews_final
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews_final");
    $finalCount = $stmt->fetchColumn();
    echo "Total reviews en reviews_final: $finalCount\n";
    
    // Reviews por plataforma
    $stmt = $pdo->query("
        SELECT source_platform, COUNT(*) as count 
        FROM reviews_final 
        GROUP BY source_platform 
        ORDER BY count DESC
    ");
    $byPlatform = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nDistribución por plataforma:\n";
    foreach ($byPlatform as $row) {
        echo "  - {$row['source_platform']}: {$row['count']} reviews\n";
    }
    
    // Reviews por hotel (top 5)
    $stmt = $pdo->query("
        SELECT hotel_id, COUNT(*) as count 
        FROM reviews_final 
        GROUP BY hotel_id 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $byHotel = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nTop 5 hoteles por reviews:\n";
    foreach ($byHotel as $row) {
        echo "  - Hotel {$row['hotel_id']}: {$row['count']} reviews\n";
    }
    
    // Reviews con rating
    $stmt = $pdo->query("SELECT COUNT(*) FROM reviews_final WHERE rating IS NOT NULL");
    $withRating = $stmt->fetchColumn();
    $withoutRating = $finalCount - $withRating;
    
    echo "\nCalidad de datos:\n";
    echo "  - Con rating: $withRating (" . round(($withRating/$finalCount)*100, 1) . "%)\n";
    echo "  - Sin rating: $withoutRating (" . round(($withoutRating/$finalCount)*100, 1) . "%)\n";
    
    // Rating promedio
    $stmt = $pdo->query("SELECT AVG(normalized_rating) as avg_rating FROM reviews_final WHERE normalized_rating IS NOT NULL");
    $avgRating = round($stmt->fetchColumn(), 2);
    echo "  - Rating promedio normalizado: $avgRating/10\n";
    
    $endTime = time();
    $duration = $endTime - $startTime;
    
    echo "\n🎉 IMPLEMENTACIÓN COMPLETADA\n";
    echo str_repeat("=", 50) . "\n";
    echo "Tiempo total: {$duration} segundos\n";
    echo "Reviews migradas: $totalMigrated\n";
    echo "Errores: $totalErrors\n";
    echo "Esquema final: reviews_final con $finalCount registros\n";
    echo "Vistas de compatibilidad: " . count($compatibilityViews) . " creadas\n";
    
    // Generar reporte JSON
    $report = [
        'timestamp' => date('Y-m-d H:i:s'),
        'duration_seconds' => $duration,
        'total_migrated' => $totalMigrated,
        'total_errors' => $totalErrors,
        'final_count' => $finalCount,
        'migration_results' => $migrationResults,
        'platform_distribution' => $byPlatform,
        'top_hotels' => $byHotel,
        'quality_stats' => [
            'with_rating' => $withRating,
            'without_rating' => $withoutRating,
            'avg_rating' => $avgRating
        ],
        'views_created' => array_keys($compatibilityViews)
    ];
    
    file_put_contents('migration-report-' . date('Y-m-d-H-i-s') . '.json', json_encode($report, JSON_PRETTY_PRINT));
    echo "\n📄 Reporte guardado: migration-report-" . date('Y-m-d-H-i-s') . ".json\n";
    
} catch (Exception $e) {
    echo "❌ ERROR FATAL: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n💡 PRÓXIMOS PASOS:\n";
echo "1. Revisar el reporte de migración generado\n";
echo "2. Actualizar código para usar 'reviews_final'\n";
echo "3. Probar vistas de compatibilidad con código existente\n";
echo "4. Considerar eliminar tablas legacy después de validación\n";
echo "5. Configurar procesos ETL para usar el nuevo esquema\n\n";
?>