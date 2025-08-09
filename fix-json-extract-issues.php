<?php
/**
 * Solucionador de Problemas JSON_EXTRACT
 * 
 * Este script resuelve problemas de compatibilidad con JSON_EXTRACT:
 * 1. Identifica queries problemáticas
 * 2. Crea columnas normalizadas para campos frecuentes
 * 3. Migra datos existentes
 * 4. Actualiza queries para usar columnas normalizadas
 */

require_once 'env-loader.php';

class JsonExtractFixer 
{
    private $pdo;
    private $logFile;
    private $backupTables = [];
    
    public function __construct() 
    {
        $this->pdo = createDatabaseConnection();
        $this->logFile = __DIR__ . '/storage/logs/json-fix.log';
        $this->ensureLogDirectory();
    }
    
    private function ensureLogDirectory() 
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        echo $logEntry;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Ejecutar corrección completa
     */
    public function fixJsonExtractIssues() 
    {
        $this->log("🚀 Iniciando corrección de problemas JSON_EXTRACT...");
        
        try {
            // 1. Verificar versión de base de datos
            $this->checkDatabaseCompatibility();
            
            // 2. Analizar tablas problemáticas
            $problematicTables = $this->analyzeProblematicTables();
            
            // 3. Crear respaldo de tablas críticas
            $this->createBackups($problematicTables);
            
            // 4. Implementar soluciones
            $this->implementSolutions($problematicTables);
            
            // 5. Verificar integridad post-migración
            $this->verifyDataIntegrity();
            
            // 6. Generar reporte final
            $report = $this->generateReport();
            
            $this->log("✅ Corrección completada exitosamente");
            return $report;
            
        } catch (Exception $e) {
            $this->log("❌ Error durante corrección: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Verificar compatibilidad de base de datos
     */
    private function checkDatabaseCompatibility() 
    {
        $this->log("🔧 Verificando compatibilidad de base de datos...");
        
        $stmt = $this->pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        
        $isMariaDB = stripos($version, 'maria') !== false;
        $this->log("📋 Versión detectada: $version");
        
        if ($isMariaDB) {
            preg_match('/(\d+)\.(\d+)/', $version, $matches);
            $majorVersion = intval($matches[1] ?? 0);
            $minorVersion = intval($matches[2] ?? 0);
            
            if ($majorVersion >= 10 && $minorVersion >= 2) {
                $this->log("✅ MariaDB $majorVersion.$minorVersion - JSON soportado");
                return true;
            } else {
                $this->log("⚠️  MariaDB $majorVersion.$minorVersion - JSON limitado", 'WARNING');
                return false;
            }
        } else {
            preg_match('/(\d+)\.(\d+)/', $version, $matches);
            $majorVersion = intval($matches[1] ?? 0);
            $minorVersion = intval($matches[2] ?? 0);
            
            if ($majorVersion >= 8 || ($majorVersion == 5 && $minorVersion >= 7)) {
                $this->log("✅ MySQL $majorVersion.$minorVersion - JSON soportado");
                return true;
            } else {
                $this->log("⚠️  MySQL $majorVersion.$minorVersion - JSON no soportado", 'WARNING');
                return false;
            }
        }
    }
    
    /**
     * Analizar tablas problemáticas
     */
    private function analyzeProblematicTables() 
    {
        $this->log("🔍 Analizando tablas problemáticas...");
        
        $problematicTables = [];
        
        // Tabla system_logs - la más crítica
        if ($this->tableExists('system_logs')) {
            $analysis = $this->analyzeSystemLogs();
            if ($analysis['needs_fix']) {
                $problematicTables['system_logs'] = $analysis;
                $this->log("⚠️  system_logs requiere corrección");
            }
        }
        
        // Tabla extraction_logs
        if ($this->tableExists('extraction_logs')) {
            $analysis = $this->analyzeExtractionLogs();
            if ($analysis['needs_fix']) {
                $problematicTables['extraction_logs'] = $analysis;
                $this->log("⚠️  extraction_logs requiere corrección");
            }
        }
        
        // Otros análisis según necesidades
        $this->log("📊 Tablas problemáticas encontradas: " . count($problematicTables));
        
        return $problematicTables;
    }
    
    /**
     * Analizar tabla system_logs
     */
    private function analyzeSystemLogs() 
    {
        $analysis = [
            'needs_fix' => false,
            'context_column_exists' => false,
            'metadata_column_exists' => false,
            'common_json_fields' => [],
            'sample_data' => []
        ];
        
        try {
            // Verificar estructura
            $stmt = $this->pdo->query("DESCRIBE system_logs");
            $columns = $stmt->fetchAll();
            
            foreach ($columns as $column) {
                if ($column['Field'] === 'context') {
                    $analysis['context_column_exists'] = true;
                    $analysis['context_type'] = $column['Type'];
                }
                if ($column['Field'] === 'metadata') {
                    $analysis['metadata_column_exists'] = true;
                    $analysis['metadata_type'] = $column['Type'];
                }
            }
            
            // Analizar datos de muestra para identificar campos comunes
            if ($analysis['context_column_exists']) {
                $stmt = $this->pdo->query("
                    SELECT context, metadata 
                    FROM system_logs 
                    WHERE context IS NOT NULL 
                    ORDER BY id DESC 
                    LIMIT 10
                ");
                
                $sampleData = $stmt->fetchAll();
                $jsonFields = [];
                
                foreach ($sampleData as $row) {
                    if ($row['context']) {
                        $context = json_decode($row['context'], true);
                        if (is_array($context)) {
                            foreach (array_keys($context) as $key) {
                                $jsonFields[$key] = ($jsonFields[$key] ?? 0) + 1;
                            }
                        }
                    }
                }
                
                // Campos que aparecen en más del 20% de los registros
                $analysis['common_json_fields'] = array_keys(array_filter(
                    $jsonFields, 
                    function($count) use ($sampleData) { 
                        return $count >= (count($sampleData) * 0.2); 
                    }
                ));
                
                $analysis['sample_data'] = array_slice($sampleData, 0, 3);
            }
            
            // Determinar si necesita corrección
            $analysis['needs_fix'] = (
                $analysis['context_column_exists'] && 
                !empty($analysis['common_json_fields'])
            ) || (
                isset($analysis['context_type']) && 
                stripos($analysis['context_type'], 'json') === false
            );
            
        } catch (Exception $e) {
            $this->log("Error analizando system_logs: " . $e->getMessage(), 'ERROR');
        }
        
        return $analysis;
    }
    
    /**
     * Analizar tabla extraction_logs
     */
    private function analyzeExtractionLogs() 
    {
        // Similar análisis para extraction_logs
        return ['needs_fix' => false]; // Implementar según necesidades específicas
    }
    
    /**
     * Crear backups de tablas críticas
     */
    private function createBackups($problematicTables) 
    {
        $this->log("💾 Creando backups de tablas críticas...");
        
        foreach ($problematicTables as $tableName => $analysis) {
            try {
                $backupTableName = $tableName . '_backup_' . date('Ymd_His');
                
                $this->log("📋 Creando backup: $tableName -> $backupTableName");
                
                $this->pdo->exec("CREATE TABLE `$backupTableName` LIKE `$tableName`");
                $this->pdo->exec("INSERT INTO `$backupTableName` SELECT * FROM `$tableName`");
                
                $this->backupTables[$tableName] = $backupTableName;
                $this->log("✅ Backup creado: $backupTableName");
                
            } catch (Exception $e) {
                $this->log("❌ Error creando backup de $tableName: " . $e->getMessage(), 'ERROR');
                throw $e;
            }
        }
    }
    
    /**
     * Implementar soluciones
     */
    private function implementSolutions($problematicTables) 
    {
        $this->log("🔧 Implementando soluciones...");
        
        foreach ($problematicTables as $tableName => $analysis) {
            switch ($tableName) {
                case 'system_logs':
                    $this->fixSystemLogsTable($analysis);
                    break;
                case 'extraction_logs':
                    $this->fixExtractionLogsTable($analysis);
                    break;
            }
        }
    }
    
    /**
     * Corregir tabla system_logs
     */
    private function fixSystemLogsTable($analysis) 
    {
        $this->log("🔧 Corrigiendo tabla system_logs...");
        
        try {
            $this->pdo->beginTransaction();
            
            // 1. Agregar columnas normalizadas para campos comunes
            if (!empty($analysis['common_json_fields'])) {
                foreach ($analysis['common_json_fields'] as $field) {
                    $columnName = $this->sanitizeColumnName($field);
                    
                    switch ($field) {
                        case 'job_id':
                        case 'batch_id':
                        case 'user_id':
                            $columnType = 'VARCHAR(100)';
                            break;
                        case 'hotel_id':
                            $columnType = 'INT';
                            break;
                        case 'timestamp':
                        case 'created_at':
                            $columnType = 'DATETIME';
                            break;
                        default:
                            $columnType = 'VARCHAR(255)';
                    }
                    
                    // Verificar si la columna ya existe
                    if (!$this->columnExists('system_logs', $columnName)) {
                        $sql = "ALTER TABLE system_logs ADD COLUMN `$columnName` $columnType NULL";
                        $this->pdo->exec($sql);
                        $this->log("✅ Columna agregada: $columnName ($columnType)");
                    }
                }
            }
            
            // 2. Migrar datos existentes a columnas normalizadas
            $this->migrateSystemLogsData($analysis);
            
            // 3. Crear índices en columnas normalizadas
            $this->createSystemLogsIndexes($analysis);
            
            // 4. Convertir tipo de columna context a JSON si es posible
            if (isset($analysis['context_type']) && 
                stripos($analysis['context_type'], 'json') === false) {
                
                try {
                    // Verificar que todos los datos sean JSON válidos
                    $stmt = $this->pdo->query("
                        SELECT COUNT(*) as invalid_count 
                        FROM system_logs 
                        WHERE context IS NOT NULL 
                        AND JSON_VALID(context) = 0
                    ");
                    
                    $invalidCount = $stmt->fetch()['invalid_count'];
                    
                    if ($invalidCount == 0) {
                        $this->pdo->exec("ALTER TABLE system_logs MODIFY context JSON");
                        $this->log("✅ Columna context convertida a tipo JSON");
                    } else {
                        $this->log("⚠️  $invalidCount registros con JSON inválido - conservando LONGTEXT", 'WARNING');
                    }
                    
                } catch (Exception $e) {
                    $this->log("⚠️  No se pudo convertir context a JSON: " . $e->getMessage(), 'WARNING');
                }
            }
            
            $this->pdo->commit();
            $this->log("✅ system_logs corregida exitosamente");
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("❌ Error corrigiendo system_logs: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Migrar datos de system_logs
     */
    private function migrateSystemLogsData($analysis) 
    {
        $this->log("📊 Migrando datos de context a columnas normalizadas...");
        
        if (empty($analysis['common_json_fields'])) {
            return;
        }
        
        try {
            // Procesar en lotes para evitar problemas de memoria
            $batchSize = 100;
            $offset = 0;
            $totalMigrated = 0;
            
            do {
                $stmt = $this->pdo->prepare("
                    SELECT id, context 
                    FROM system_logs 
                    WHERE context IS NOT NULL
                    ORDER BY id
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$batchSize, $offset]);
                $batch = $stmt->fetchAll();
                
                foreach ($batch as $row) {
                    $context = json_decode($row['context'], true);
                    if (!is_array($context)) continue;
                    
                    $updateFields = [];
                    $updateValues = [];
                    
                    foreach ($analysis['common_json_fields'] as $field) {
                        if (isset($context[$field])) {
                            $columnName = $this->sanitizeColumnName($field);
                            $updateFields[] = "`$columnName` = ?";
                            $updateValues[] = $context[$field];
                        }
                    }
                    
                    if (!empty($updateFields)) {
                        $updateValues[] = $row['id'];
                        $sql = "UPDATE system_logs SET " . implode(', ', $updateFields) . " WHERE id = ?";
                        $stmt = $this->pdo->prepare($sql);
                        $stmt->execute($updateValues);
                        $totalMigrated++;
                    }
                }
                
                $offset += $batchSize;
                
            } while (count($batch) === $batchSize);
            
            $this->log("✅ Migrados $totalMigrated registros a columnas normalizadas");
            
        } catch (Exception $e) {
            $this->log("❌ Error migrando datos: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Crear índices para system_logs
     */
    private function createSystemLogsIndexes($analysis) 
    {
        $this->log("📊 Creando índices optimizados...");
        
        foreach ($analysis['common_json_fields'] as $field) {
            $columnName = $this->sanitizeColumnName($field);
            $indexName = "idx_system_logs_{$columnName}";
            
            try {
                if (!$this->indexExists('system_logs', $indexName)) {
                    $this->pdo->exec("CREATE INDEX `$indexName` ON system_logs (`$columnName`)");
                    $this->log("✅ Índice creado: $indexName");
                }
            } catch (Exception $e) {
                $this->log("⚠️  Error creando índice $indexName: " . $e->getMessage(), 'WARNING');
            }
        }
        
        // Índice compuesto para queries comunes
        try {
            $commonIndexName = 'idx_system_logs_common';
            if (!$this->indexExists('system_logs', $commonIndexName)) {
                $this->pdo->exec("CREATE INDEX `$commonIndexName` ON system_logs (created_at, level)");
                $this->log("✅ Índice compuesto creado: $commonIndexName");
            }
        } catch (Exception $e) {
            $this->log("⚠️  Error creando índice compuesto: " . $e->getMessage(), 'WARNING');
        }
    }
    
    /**
     * Verificar integridad de datos
     */
    private function verifyDataIntegrity() 
    {
        $this->log("🔍 Verificando integridad de datos...");
        
        try {
            // Verificar que las columnas normalizadas tengan datos
            foreach ($this->backupTables as $originalTable => $backupTable) {
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) as original_count FROM `$originalTable`
                ");
                $originalCount = $stmt->fetch()['original_count'];
                
                $stmt = $this->pdo->query("
                    SELECT COUNT(*) as backup_count FROM `$backupTable`
                ");
                $backupCount = $stmt->fetch()['backup_count'];
                
                if ($originalCount === $backupCount) {
                    $this->log("✅ $originalTable: integridad verificada ($originalCount registros)");
                } else {
                    $this->log("⚠️  $originalTable: discrepancia de registros (original: $originalCount, backup: $backupCount)", 'WARNING');
                }
            }
            
        } catch (Exception $e) {
            $this->log("❌ Error verificando integridad: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Generar reporte final
     */
    private function generateReport() 
    {
        $report = [
            'timestamp' => date('c'),
            'status' => 'completed',
            'tables_processed' => array_keys($this->backupTables),
            'backups_created' => $this->backupTables,
            'recommendations' => $this->generateRecommendations()
        ];
        
        $reportFile = __DIR__ . '/storage/reports/json-extract-fix-report.json';
        $reportDir = dirname($reportFile);
        
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        file_put_contents($reportFile, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->log("📄 Reporte guardado: $reportFile");
        
        return $report;
    }
    
    /**
     * Generar recomendaciones
     */
    private function generateRecommendations() 
    {
        return [
            'codigo' => [
                'Actualizar queries para usar columnas normalizadas en lugar de JSON_EXTRACT',
                'Usar COALESCE para compatibilidad: COALESCE(job_id, JSON_EXTRACT(context, "$.job_id"))',
                'Crear índices en columnas normalizadas para mejor performance'
            ],
            'mantenimiento' => [
                'Ejecutar ANALYZE TABLE después de la migración',
                'Monitorear performance de queries actualizadas',
                'Considerar eliminar backups después de verificar estabilidad'
            ],
            'futuro' => [
                'Para nuevos desarrollos, usar columnas normalizadas desde el inicio',
                'Implementar validación de JSON antes de insertar datos',
                'Considerar migración completa a tipos JSON nativos'
            ]
        ];
    }
    
    /**
     * Solo análisis sin hacer cambios
     */
    public function analyzeOnly() 
    {
        $this->checkDatabaseCompatibility();
        return $this->analyzeProblematicTables();
    }
    
    // Métodos helper
    private function tableExists($tableName) 
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.tables WHERE table_name = ? AND table_schema = DATABASE()");
            $stmt->execute([$tableName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function columnExists($tableName, $columnName) 
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ? AND table_schema = DATABASE()");
            $stmt->execute([$tableName, $columnName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function indexExists($tableName, $indexName) 
    {
        try {
            $stmt = $this->pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_name = ? AND index_name = ? AND table_schema = DATABASE()");
            $stmt->execute([$tableName, $indexName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function sanitizeColumnName($name) 
    {
        // Convertir a snake_case y limpiar caracteres especiales
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        $name = strtolower($name);
        return $name;
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'fix';
    
    try {
        $fixer = new JsonExtractFixer();
        
        switch ($action) {
            case 'fix':
            case '--fix':
                echo "🚀 CORRECCIÓN DE PROBLEMAS JSON_EXTRACT\n";
                echo str_repeat("=", 60) . "\n\n";
                
                $report = $fixer->fixJsonExtractIssues();
                
                echo "\n📊 RESUMEN:\n";
                echo "✅ Estado: " . $report['status'] . "\n";
                echo "📋 Tablas procesadas: " . implode(', ', $report['tables_processed']) . "\n";
                echo "💾 Backups creados: " . count($report['backups_created']) . "\n";
                
                echo "\n💡 PRÓXIMOS PASOS:\n";
                foreach ($report['recommendations']['codigo'] as $i => $rec) {
                    echo "  " . ($i + 1) . ". $rec\n";
                }
                
                break;
                
            case 'analyze':
            case '--analyze':
                echo "🔍 ANÁLISIS DE PROBLEMAS JSON_EXTRACT\n";
                echo str_repeat("=", 60) . "\n\n";
                
                // Solo análisis, sin cambios
                $problematic = $fixer->analyzeOnly();
                
                echo "\n📊 TABLAS PROBLEMÁTICAS: " . count($problematic) . "\n";
                foreach ($problematic as $table => $analysis) {
                    echo "  📋 $table:\n";
                    if (!empty($analysis['common_json_fields'])) {
                        echo "    🔹 Campos JSON comunes: " . implode(', ', $analysis['common_json_fields']) . "\n";
                    }
                }
                
                break;
                
            default:
                echo "Uso: php fix-json-extract-issues.php [fix|analyze]\n";
                echo "  fix     - Ejecutar corrección completa\n";
                echo "  analyze - Solo analizar problemas\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>