<?php
/**
 * Solución Completa para Problemas JSON_EXTRACT
 * 
 * Esta solución implementa mejores prácticas para evitar problemas con JSON_EXTRACT:
 * 1. Función wrapper segura para JSON_EXTRACT
 * 2. Migración preventiva de tablas problemáticas
 * 3. Helper functions para queries JSON compatibles
 */

require_once 'env-loader.php';

class JsonCompatibilityHelper 
{
    private static $dbVersion = null;
    private static $supportsJson = null;
    
    /**
     * Verificar si la BD soporta JSON nativo
     */
    public static function databaseSupportsJson() 
    {
        if (self::$supportsJson !== null) {
            return self::$supportsJson;
        }
        
        try {
            $pdo = createDatabaseConnection();
            $stmt = $pdo->query("SELECT VERSION() as version");
            $version = $stmt->fetch()['version'];
            self::$dbVersion = $version;
            
            $isMariaDB = stripos($version, 'maria') !== false;
            
            if ($isMariaDB) {
                preg_match('/(\d+)\.(\d+)/', $version, $matches);
                $major = intval($matches[1] ?? 0);
                $minor = intval($matches[2] ?? 0);
                self::$supportsJson = ($major >= 10 && $minor >= 2);
            } else {
                preg_match('/(\d+)\.(\d+)/', $version, $matches);
                $major = intval($matches[1] ?? 0);
                $minor = intval($matches[2] ?? 0);
                self::$supportsJson = ($major >= 8 || ($major == 5 && $minor >= 7));
            }
            
        } catch (Exception $e) {
            self::$supportsJson = false;
        }
        
        return self::$supportsJson;
    }
    
    /**
     * JSON_EXTRACT seguro con fallback
     */
    public static function safeJsonExtract($pdo, $table, $column, $path, $whereClause = '', $params = []) 
    {
        if (!self::databaseSupportsJson()) {
            // Fallback para versiones que no soportan JSON
            return self::extractJsonWithPhp($pdo, $table, $column, $path, $whereClause, $params);
        }
        
        // Usar JSON_EXTRACT nativo con validación
        try {
            $sql = "
                SELECT *, 
                CASE 
                    WHEN JSON_VALID($column) = 1 
                    THEN JSON_EXTRACT($column, ?) 
                    ELSE NULL 
                END as extracted_value
                FROM $table
                " . ($whereClause ? "WHERE $whereClause" : "");
            
            $allParams = array_merge([$path], $params);
            $stmt = $pdo->prepare($sql);
            $stmt->execute($allParams);
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            // Fallback si JSON_EXTRACT falla
            return self::extractJsonWithPhp($pdo, $table, $column, $path, $whereClause, $params);
        }
    }
    
    /**
     * Extraer JSON usando PHP (fallback)
     */
    private static function extractJsonWithPhp($pdo, $table, $column, $path, $whereClause, $params) 
    {
        $sql = "SELECT * FROM $table" . ($whereClause ? " WHERE $whereClause" : "");
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        
        // Extraer usando PHP
        foreach ($rows as &$row) {
            $jsonData = json_decode($row[$column], true);
            $pathParts = explode('.', ltrim($path, '$.'));
            
            $value = $jsonData;
            foreach ($pathParts as $part) {
                if (is_array($value) && isset($value[$part])) {
                    $value = $value[$part];
                } else {
                    $value = null;
                    break;
                }
            }
            
            $row['extracted_value'] = $value;
        }
        
        return $rows;
    }
    
    /**
     * Crear columnas normalizadas para campos JSON frecuentes
     */
    public static function normalizeJsonColumns($tableName, $jsonColumn, $fieldMappings) 
    {
        try {
            $pdo = createDatabaseConnection();
            $pdo->beginTransaction();
            
            echo "🔧 Normalizando columnas JSON en $tableName...\n";
            
            // 1. Crear columnas normalizadas
            foreach ($fieldMappings as $jsonPath => $columnDef) {
                $columnName = $columnDef['name'];
                $columnType = $columnDef['type'];
                
                if (!self::columnExists($pdo, $tableName, $columnName)) {
                    $sql = "ALTER TABLE `$tableName` ADD COLUMN `$columnName` $columnType NULL";
                    $pdo->exec($sql);
                    echo "  ✅ Columna agregada: $columnName ($columnType)\n";
                }
            }
            
            // 2. Migrar datos existentes
            $stmt = $pdo->query("SELECT id, $jsonColumn FROM $tableName WHERE $jsonColumn IS NOT NULL");
            $rows = $stmt->fetchAll();
            
            $migrated = 0;
            foreach ($rows as $row) {
                $jsonData = json_decode($row[$jsonColumn], true);
                if (!is_array($jsonData)) continue;
                
                $updateFields = [];
                $updateValues = [];
                
                foreach ($fieldMappings as $jsonPath => $columnDef) {
                    $pathParts = explode('.', ltrim($jsonPath, '$.'));
                    $value = $jsonData;
                    
                    foreach ($pathParts as $part) {
                        if (is_array($value) && isset($value[$part])) {
                            $value = $value[$part];
                        } else {
                            $value = null;
                            break;
                        }
                    }
                    
                    if ($value !== null) {
                        $updateFields[] = "`{$columnDef['name']}` = ?";
                        $updateValues[] = $value;
                    }
                }
                
                if (!empty($updateFields)) {
                    $updateValues[] = $row['id'];
                    $sql = "UPDATE $tableName SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $updateStmt = $pdo->prepare($sql);
                    $updateStmt->execute($updateValues);
                    $migrated++;
                }
            }
            
            // 3. Crear índices
            foreach ($fieldMappings as $jsonPath => $columnDef) {
                $columnName = $columnDef['name'];
                $indexName = "idx_{$tableName}_{$columnName}";
                
                if (!self::indexExists($pdo, $tableName, $indexName)) {
                    $sql = "CREATE INDEX `$indexName` ON `$tableName` (`$columnName`)";
                    $pdo->exec($sql);
                    echo "  📊 Índice creado: $indexName\n";
                }
            }
            
            $pdo->commit();
            echo "✅ Normalización completada: $migrated registros migrados\n";
            
            return ['success' => true, 'migrated' => $migrated];
            
        } catch (Exception $e) {
            $pdo->rollBack();
            echo "❌ Error durante normalización: " . $e->getMessage() . "\n";
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generar query optimizada que usa columnas normalizadas con fallback a JSON
     */
    public static function buildOptimizedJsonQuery($table, $jsonColumn, $fieldMappings, $conditions = []) 
    {
        $selectFields = [];
        
        foreach ($fieldMappings as $jsonPath => $columnDef) {
            $normalizedColumn = $columnDef['name'];
            
            // Usar COALESCE para priorizar columna normalizada
            if (self::databaseSupportsJson()) {
                $selectFields[] = "COALESCE(`$normalizedColumn`, JSON_EXTRACT($jsonColumn, '$jsonPath')) as `$normalizedColumn`";
            } else {
                $selectFields[] = "`$normalizedColumn`";
            }
        }
        
        $sql = "SELECT *, " . implode(', ', $selectFields) . " FROM `$table`";
        
        if (!empty($conditions)) {
            $whereConditions = [];
            foreach ($conditions as $field => $value) {
                if (isset($fieldMappings[$field])) {
                    $columnName = $fieldMappings[$field]['name'];
                    $whereConditions[] = "`$columnName` = ?";
                }
            }
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(' AND ', $whereConditions);
            }
        }
        
        return $sql;
    }
    
    // Helper methods
    private static function columnExists($pdo, $tableName, $columnName) 
    {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.columns WHERE table_name = ? AND column_name = ? AND table_schema = DATABASE()");
            $stmt->execute([$tableName, $columnName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private static function indexExists($pdo, $tableName, $indexName) 
    {
        try {
            $stmt = $pdo->prepare("SELECT 1 FROM information_schema.statistics WHERE table_name = ? AND index_name = ? AND table_schema = DATABASE()");
            $stmt->execute([$tableName, $indexName]);
            return $stmt->fetch() !== false;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Implementación específica para system_logs
 */
class SystemLogsOptimizer 
{
    public static function optimizeSystemLogs() 
    {
        echo "🚀 OPTIMIZANDO SYSTEM_LOGS PARA JSON_EXTRACT\n";
        echo str_repeat("=", 50) . "\n\n";
        
        // Definir mapeo de campos JSON frecuentes a columnas normalizadas
        $fieldMappings = [
            '$.job_id' => ['name' => 'job_id_extracted', 'type' => 'VARCHAR(100)'],
            '$.batch_id' => ['name' => 'batch_id_extracted', 'type' => 'VARCHAR(100)'],
            '$.hotel_id' => ['name' => 'hotel_id_extracted', 'type' => 'INT'],
            '$.user_id' => ['name' => 'user_id_extracted', 'type' => 'VARCHAR(100)'],
            '$.operation' => ['name' => 'operation_extracted', 'type' => 'VARCHAR(100)'],
            '$.status' => ['name' => 'status_extracted', 'type' => 'VARCHAR(50)'],
            '$.timestamp' => ['name' => 'event_timestamp', 'type' => 'DATETIME'],
        ];
        
        $result = JsonCompatibilityHelper::normalizeJsonColumns('system_logs', 'context', $fieldMappings);
        
        if ($result['success']) {
            echo "\n💡 EJEMPLOS DE USO OPTIMIZADO:\n\n";
            
            echo "// ❌ Query problemática (puede fallar):\n";
            echo "SELECT * FROM system_logs WHERE JSON_EXTRACT(context, '$.job_id') = 'job123';\n\n";
            
            echo "// ✅ Query optimizada (siempre funciona):\n";
            echo "SELECT * FROM system_logs WHERE job_id_extracted = 'job123';\n\n";
            
            echo "// ✅ Query híbrida (máxima compatibilidad):\n";
            echo "SELECT * FROM system_logs WHERE \n";
            echo "  COALESCE(job_id_extracted, JSON_EXTRACT(context, '$.job_id')) = 'job123';\n\n";
            
            echo "📊 QUERY GENERATOR DISPONIBLE:\n";
            $sampleSql = JsonCompatibilityHelper::buildOptimizedJsonQuery(
                'system_logs', 
                'context', 
                $fieldMappings, 
                ['$.job_id' => 'job123']
            );
            echo "$sampleSql\n\n";
        }
        
        return $result;
    }
}

/**
 * Función helper para debugging de JSON
 */
function debugJsonExtract($table, $column, $path, $limit = 5) 
{
    try {
        $pdo = createDatabaseConnection();
        
        echo "🔍 DEBUG JSON_EXTRACT: $table.$column -> $path\n";
        echo str_repeat("-", 50) . "\n";
        
        // Test si funciona JSON_EXTRACT
        try {
            $stmt = $pdo->prepare("
                SELECT $column, 
                JSON_EXTRACT($column, ?) as extracted,
                JSON_VALID($column) as is_valid_json
                FROM $table 
                WHERE $column IS NOT NULL 
                LIMIT ?
            ");
            $stmt->execute([$path, $limit]);
            $results = $stmt->fetchAll();
            
            foreach ($results as $i => $row) {
                echo "📝 Registro " . ($i + 1) . ":\n";
                echo "  JSON válido: " . ($row['is_valid_json'] ? 'SÍ' : 'NO') . "\n";
                echo "  Valor extraído: " . ($row['extracted'] ?? 'NULL') . "\n";
                echo "  JSON original: " . substr($row[$column], 0, 100) . "...\n\n";
            }
            
        } catch (Exception $e) {
            echo "❌ JSON_EXTRACT falla: " . $e->getMessage() . "\n";
            echo "💡 Recomendación: Usar columnas normalizadas\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'optimize-system-logs';
    
    try {
        switch ($action) {
            case 'optimize-system-logs':
            case '--optimize-system-logs':
                SystemLogsOptimizer::optimizeSystemLogs();
                break;
                
            case 'check-json-support':
            case '--check-json-support':
                echo "🔧 VERIFICACIÓN DE SOPORTE JSON\n";
                echo str_repeat("=", 40) . "\n";
                
                if (JsonCompatibilityHelper::databaseSupportsJson()) {
                    echo "✅ Base de datos soporta JSON nativo\n";
                    echo "📋 Versión: " . JsonCompatibilityHelper::$dbVersion . "\n";
                } else {
                    echo "❌ Base de datos NO soporta JSON nativo\n";
                    echo "📋 Versión: " . JsonCompatibilityHelper::$dbVersion . "\n";
                    echo "💡 Recomendación: Usar solo columnas normalizadas\n";
                }
                break;
                
            case 'debug':
            case '--debug':
                $table = $argv[2] ?? 'system_logs';
                $column = $argv[3] ?? 'context';
                $path = $argv[4] ?? '$.job_id';
                
                debugJsonExtract($table, $column, $path);
                break;
                
            default:
                echo "Uso: php json-extract-solution.php [comando]\n\n";
                echo "Comandos disponibles:\n";
                echo "  optimize-system-logs  - Optimizar tabla system_logs\n";
                echo "  check-json-support   - Verificar soporte JSON\n";
                echo "  debug [tabla] [col] [path] - Debug JSON_EXTRACT\n\n";
                echo "Ejemplos:\n";
                echo "  php json-extract-solution.php optimize-system-logs\n";
                echo "  php json-extract-solution.php debug system_logs context '$.job_id'\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>