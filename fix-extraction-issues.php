<?php
/**
 * Corrección de Problemas de Extracción Críticos
 * 
 * Corrige:
 * 1. Inconsistencia Booking-only entre sync y async
 * 2. Mapeo de plataformas a flags del actor multi-OTAs
 * 3. Seguimiento incompleto de jobs async
 * 4. Duplicidad de funciones JS
 */

require_once 'env-loader.php';
require_once 'apify-config.php';

echo "🔧 CORRECCIÓN DE PROBLEMAS DE EXTRACCIÓN\n";
echo str_repeat("=", 60) . "\n\n";

class ExtractionFixer 
{
    private $pdo;
    private $fixes = [];
    
    public function __construct() 
    {
        try {
            $this->pdo = EnvironmentLoader::createDatabaseConnection();
        } catch (Exception $e) {
            echo "⚠️ BD no disponible, ejecutando correcciones de archivos únicamente\n";
            $this->pdo = null;
        }
    }
    
    public function fixAllIssues() 
    {
        echo "🔍 Corrigiendo problemas de extracción...\n\n";
        
        // 1. Corregir inconsistencia Booking-only
        $this->fixBookingOnlyConsistency();
        
        // 2. Corregir mapeo de plataformas
        $this->fixPlatformMapping();
        
        // 3. Mejorar seguimiento de jobs async
        $this->fixAsyncJobTracking();
        
        // 4. Eliminar funciones JS duplicadas
        $this->fixDuplicateJSFunctions();
        
        return $this->fixes;
    }
    
    /**
     * 1. Corregir inconsistencia Booking-only entre sync y async
     */
    private function fixBookingOnlyConsistency() 
    {
        echo "🔧 Corrigiendo inconsistencia Booking-only...\n";
        
        $apiExtractionFile = __DIR__ . '/api-extraction.php';
        $content = file_get_contents($apiExtractionFile);
        
        // Buscar la función handleStartExtraction para corregir lógica async
        $searchPattern = '/DebugLogger::info\(\'Detección de plataformas para inicio asíncrono\'.+?if \(\$onlyBooking\) \{.+?\} else \{.+?\}/s';
        
        $correctedAsyncLogic = "
        // Detección consistente Booking-only para async (igual que sync)
        \$onlyBooking = count(array_unique(array_map('strtolower', \$platforms))) === 1 && strtolower(\$platforms[0]) === 'booking';
        
        DebugLogger::info('Detección de plataformas para inicio asíncrono', [
            'platforms' => \$platforms,
            'only_booking' => \$onlyBooking,
            'will_use_actor' => \$onlyBooking ? 'booking_specific' : 'multi_otas'
        ]);

        if (\$onlyBooking) {
            DebugLogger::info('Usando actor específico de Booking (async) - CONSISTENCIA CON SYNC');
            \$apifyResponse = \$apifyClient->startBookingExtractionAsync(\$extractionConfig);
        } else {
            DebugLogger::info('Usando actor multi-plataforma (async) - CON MAPEO CORRECTO');
            // IMPORTANTE: Usar buildExtractionInput para mapear platforms -> enableX flags
            \$extractionInput = ExtractionInputBuilder::buildExtractionInput(\$extractionConfig);
            \$extractionConfig = array_merge(\$extractionConfig, \$extractionInput);
            \$apifyResponse = \$apifyClient->startHotelExtraction(\$extractionConfig);
        }";
        
        if (preg_match($searchPattern, $content)) {
            $newContent = preg_replace($searchPattern, $correctedAsyncLogic, $content);
            file_put_contents($apiExtractionFile . '.booking-fixed', $newContent);
            
            $this->fixes[] = [
                'issue' => 'Booking-only inconsistency',
                'description' => 'Lógica async ahora es consistente con sync para detección Booking-only',
                'file' => $apiExtractionFile,
                'status' => 'fixed'
            ];
            
            echo "  ✅ Lógica async corregida para Booking-only\n";
        }
    }
    
    /**
     * 2. Corregir mapeo de plataformas a flags del actor multi-OTAs
     */
    private function fixPlatformMapping() 
    {
        echo "🔧 Corrigiendo mapeo de plataformas...\n";
        
        // Verificar que extraction-utils.php está corregido
        $extractionUtilsFile = __DIR__ . '/extraction-utils.php';
        if (file_exists($extractionUtilsFile)) {
            $content = file_get_contents($extractionUtilsFile);
            
            // Verificar que la función respeta la selección del usuario
            if (strpos($content, 'TODAS DESHABILITADAS') !== false && 
                strpos($content, 'Aplicar solo las plataformas seleccionadas') !== false) {
                
                echo "  ✅ ExtractionInputBuilder ya corregido\n";
                
                $this->fixes[] = [
                    'issue' => 'Platform mapping',
                    'description' => 'buildExtractionInput respeta selección del usuario (default false, enable solo seleccionadas)',
                    'file' => $extractionUtilsFile,
                    'status' => 'already_fixed'
                ];
            }
        }
        
        // También corregir apify-data-processor.php para usar ExtractionInputBuilder
        $processorFile = __DIR__ . '/apify-data-processor.php';
        if (file_exists($processorFile)) {
            $content = file_get_contents($processorFile);
            
            $oldPattern = '/\$platformFlags = ApifyConfig::generateExtractionInput\(\$platforms\);/';
            $newCode = "// Usar ExtractionInputBuilder corregido\n        require_once 'extraction-utils.php';\n        \$platformFlags = ExtractionInputBuilder::buildExtractionInput(['platforms' => \$platforms]);";
            
            if (preg_match($oldPattern, $content)) {
                $newContent = preg_replace($oldPattern, $newCode, $content);
                file_put_contents($processorFile . '.mapping-fixed', $newContent);
                
                echo "  ✅ ApifyDataProcessor actualizado para usar ExtractionInputBuilder\n";
                
                $this->fixes[] = [
                    'issue' => 'Platform mapping in processor',
                    'description' => 'ApifyDataProcessor ahora usa ExtractionInputBuilder corregido',
                    'file' => $processorFile,
                    'status' => 'fixed'
                ];
            }
        }
    }
    
    /**
     * 3. Mejorar seguimiento de jobs async
     */
    private function fixAsyncJobTracking() 
    {
        echo "🔧 Mejorando seguimiento de jobs async...\n";
        
        try {
            // 3.1. Verificar y agregar campos faltantes en apify_extraction_runs
            $this->addMissingColumnsToApifyRuns();
            
            // 3.2. Crear función para actualizar extraction_jobs cuando async termina
            $this->createAsyncJobUpdateFunction();
            
            // 3.3. Mejorar vínculo job_id <-> run_id
            $this->improveJobRunLinking();
            
        } catch (Exception $e) {
            echo "  ❌ Error en seguimiento async: " . $e->getMessage() . "\n";
        }
    }
    
    private function addMissingColumnsToApifyRuns() 
    {
        if (!$this->pdo) {
            echo "  ⚠️ BD no disponible para modificar columnas\n";
            return;
        }
        
        // Verificar columnas existentes
        $stmt = $this->pdo->query("DESCRIBE apify_extraction_runs");
        $columns = array_column($stmt->fetchAll(), 'Field');
        
        $requiredColumns = [
            'job_id' => 'INT NULL',
            'started_at' => 'TIMESTAMP NULL',
            'finished_at' => 'TIMESTAMP NULL',
            'reviews_extracted' => 'INT DEFAULT 0',
            'progress' => 'DECIMAL(5,2) DEFAULT 0'
        ];
        
        foreach ($requiredColumns as $column => $definition) {
            if (!in_array($column, $columns)) {
                $sql = "ALTER TABLE apify_extraction_runs ADD COLUMN $column $definition";
                $this->pdo->exec($sql);
                echo "  ✅ Columna agregada: $column\n";
            }
        }
        
        // Agregar índices para optimizar consultas
        $indexes = [
            'idx_job_id' => 'job_id',
            'idx_hotel_started' => 'hotel_id, started_at',
            'idx_status_started' => 'status, started_at'
        ];
        
        foreach ($indexes as $indexName => $columns) {
            try {
                $sql = "CREATE INDEX $indexName ON apify_extraction_runs ($columns)";
                $this->pdo->exec($sql);
                echo "  ✅ Índice creado: $indexName\n";
            } catch (PDOException $e) {
                // Índice ya existe
            }
        }
        
        $this->fixes[] = [
            'issue' => 'Async job tracking columns',
            'description' => 'Agregadas columnas job_id, started_at, finished_at, progress en apify_extraction_runs',
            'table' => 'apify_extraction_runs',
            'status' => 'fixed'
        ];
    }
    
    private function createAsyncJobUpdateFunction() 
    {
        $functionCode = "
/**
 * Actualizar extraction_jobs cuando el run async termina
 */
function updateExtractionJobFromRun(\$runId, \$status, \$reviewsCount = 0, \$progress = 100) {
    global \$pdo;
    
    try {
        // Buscar job_id asociado al run
        \$stmt = \$pdo->prepare(\"
            SELECT job_id, hotel_id 
            FROM apify_extraction_runs 
            WHERE apify_run_id = ? AND job_id IS NOT NULL
        \");
        \$stmt->execute([\$runId]);
        \$run = \$stmt->fetch();
        
        if (!\$run) {
            error_log(\"No se encontró job_id para run: \$runId\");
            return false;
        }
        
        \$jobId = \$run['job_id'];
        
        // Actualizar extraction_jobs
        \$mappedStatus = [
            'SUCCEEDED' => 'completed',
            'FAILED' => 'failed', 
            'ABORTED' => 'failed',
            'TIMED-OUT' => 'timeout'
        ][\$status] ?? 'pending';
        
        \$stmt = \$pdo->prepare(\"
            UPDATE extraction_jobs 
            SET status = ?, 
                progress = ?, 
                reviews_extracted = ?,
                completed_at = CASE WHEN ? IN ('completed', 'failed', 'timeout') THEN NOW() ELSE completed_at END,
                updated_at = NOW()
            WHERE id = ?
        \");
        
        \$stmt->execute([\$mappedStatus, \$progress, \$reviewsCount, \$mappedStatus, \$jobId]);
        
        error_log(\"Job \$jobId actualizado: status=\$mappedStatus, reviews=\$reviewsCount\");
        return true;
        
    } catch (Exception \$e) {
        error_log(\"Error actualizando job desde run: \" . \$e->getMessage());
        return false;
    }
}";
        
        file_put_contents(__DIR__ . '/async-job-updater.php', "<?php\n" . $functionCode . "\n?>");
        echo "  ✅ Función de actualización async creada: async-job-updater.php\n";
        
        $this->fixes[] = [
            'issue' => 'Async job updates',
            'description' => 'Función updateExtractionJobFromRun() creada para sincronizar extraction_jobs',
            'file' => __DIR__ . '/async-job-updater.php',
            'status' => 'created'
        ];
    }
    
    private function improveJobRunLinking() 
    {
        // Mejorar la inserción en api-extraction.php para guardar job_id en apify_extraction_runs
        $apiFile = __DIR__ . '/api-extraction.php';
        $content = file_get_contents($apiFile);
        
        // Buscar la inserción en apify_extraction_runs y mejorarla
        $oldInsertPattern = '/INSERT INTO apify_extraction_runs \(\s*hotel_id, apify_run_id, status, platforms_requested,/';
        
        if (preg_match($oldInsertPattern, $content)) {
            $improvedInsert = "
        // MEJORA: Vincular job_id con run_id para seguimiento completo
        \$jobStmt = \$pdo->prepare(\"
            INSERT INTO extraction_jobs (
                hotel_id, status, progress, platforms, created_at, started_at
            ) VALUES (?, 'running', 0, ?, NOW(), NOW())
        \");
        \$jobStmt->execute([\$hotelId, json_encode(\$platforms)]);
        \$jobId = \$pdo->lastInsertId();
        
        DebugLogger::info('Job creado para seguimiento', ['job_id' => \$jobId]);
        
        // Insertar run con job_id vinculado
        \$stmt = \$pdo->prepare(\"
            INSERT INTO apify_extraction_runs (
                job_id, hotel_id, apify_run_id, status, platforms_requested,
                max_reviews_per_platform, cost_estimate, apify_response,
                started_at, created_at
            ) VALUES (?, ?, ?, 'running', ?, ?, ?, ?, NOW(), NOW())
        \");
        
        \$stmt->execute([
            \$jobId,            // job_id vinculado
            \$hotelId,
            \$runId,
            json_encode(\$platforms),
            \$maxReviews,
            \$costEstimate,
            json_encode(\$apifyResponse)
        ]);" . "\n        
        // Ya no insertar en extraction_jobs por separado - se hace arriba";
            
            $newContent = str_replace(
                '// Guardar en base de datos
        try {
            $stmt = $pdo->prepare("
                INSERT INTO apify_extraction_runs (
                    hotel_id, apify_run_id, status, platforms_requested,',
                '// Guardar en base de datos con vínculo job_id mejorado
        try {' . $improvedInsert,
                $content
            );
            
            file_put_contents($apiFile . '.linking-improved', $newContent);
            echo "  ✅ Vínculo job_id <-> run_id mejorado\n";
            
            $this->fixes[] = [
                'issue' => 'Job-Run linking',
                'description' => 'Vínculo directo job_id <-> run_id implementado en inserción',
                'file' => $apiFile,
                'status' => 'improved'
            ];
        }
    }
    
    /**
     * 4. Eliminar funciones JS duplicadas
     */
    private function fixDuplicateJSFunctions() 
    {
        echo "🔧 Eliminando funciones JS duplicadas...\n";
        
        $adminExtractionFile = __DIR__ . '/admin-extraction.php';
        if (!file_exists($adminExtractionFile)) {
            echo "  ⚠️ admin-extraction.php no encontrado\n";
            return;
        }
        
        $content = file_get_contents($adminExtractionFile);
        
        // Buscar funciones getStatusBadge duplicadas
        preg_match_all('/function\s+getStatusBadge\s*\([^}]+\}(?:[^}]+\})?/s', $content, $matches, PREG_OFFSET_CAPTURE);
        
        if (count($matches[0]) > 1) {
            echo "  🔍 Encontradas " . count($matches[0]) . " funciones getStatusBadge\n";
            
            // Mantener la primera función, eliminar las duplicadas
            $newContent = $content;
            for ($i = count($matches[0]) - 1; $i > 0; $i--) {
                $functionStart = $matches[0][$i][1];
                $functionCode = $matches[0][$i][0];
                $functionEnd = $functionStart + strlen($functionCode);
                
                // Eliminar la función duplicada
                $newContent = substr($newContent, 0, $functionStart) . 
                             "/* Función duplicada eliminada - ver función principal arriba */" .
                             substr($newContent, $functionEnd);
            }
            
            file_put_contents($adminExtractionFile . '.deduplicated', $newContent);
            echo "  ✅ " . (count($matches[0]) - 1) . " funciones duplicadas eliminadas\n";
            
            $this->fixes[] = [
                'issue' => 'Duplicate JS functions',
                'description' => count($matches[0]) - 1 . ' funciones getStatusBadge duplicadas eliminadas',
                'file' => $adminExtractionFile,
                'status' => 'fixed'
            ];
        } else {
            echo "  ✅ No se encontraron funciones duplicadas\n";
        }
    }
}

// Ejecutar correcciones si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $fixer = new ExtractionFixer();
    $fixes = $fixer->fixAllIssues();
    
    echo "\n📊 RESUMEN DE CORRECCIONES:\n";
    foreach ($fixes as $fix) {
        $status = $fix['status'] === 'fixed' ? '✅' : ($fix['status'] === 'created' ? '🆕' : '✅');
        echo "  $status {$fix['issue']}: {$fix['description']}\n";
    }
    
    echo "\n🔧 PRÓXIMOS PASOS:\n";
    echo "1. Revisar archivos .booking-fixed, .mapping-fixed, .linking-improved, .deduplicated\n";
    echo "2. Aplicar cambios después de validación\n";
    echo "3. Rotar credenciales BD si aún no se hizo\n";
    echo "4. Probar funcionalidad Booking-only en sync y async\n";
}

?>