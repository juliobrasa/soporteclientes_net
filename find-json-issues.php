<?php
/**
 * Detector de Problemas JSON_EXTRACT
 * Busca tablas con problemas potenciales de JSON_EXTRACT y compatibilidad
 */

require_once 'env-loader.php';

echo "🔍 DETECTOR DE PROBLEMAS JSON_EXTRACT\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $pdo = createDatabaseConnection();
    
    // 1. Buscar tablas con debug_logs o similar
    echo "📊 1. BUSCANDO TABLAS RELACIONADAS CON LOGS:\n";
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $logTables = [];
    foreach ($tables as $table) {
        if (stripos($table, 'debug') !== false || 
            stripos($table, 'log') !== false ||
            stripos($table, 'job') !== false) {
            $logTables[] = $table;
            echo "  📋 Encontrada: $table\n";
        }
    }
    
    if (empty($logTables)) {
        echo "  ✅ No se encontraron tablas de logs específicas\n";
    }
    echo "\n";
    
    // 2. Buscar columnas JSON en todas las tablas
    echo "🔍 2. BUSCANDO COLUMNAS JSON EN TODAS LAS TABLAS:\n";
    $jsonColumns = [];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            
            foreach ($columns as $column) {
                $field = $column['Field'];
                $type = $column['Type'];
                
                // Verificar tipos JSON o campos que podrían contener JSON
                if (stripos($type, 'json') !== false ||
                    stripos($field, 'context') !== false ||
                    stripos($field, 'metadata') !== false ||
                    stripos($field, 'data') !== false ||
                    stripos($type, 'text') !== false && (
                        stripos($field, 'json') !== false ||
                        stripos($field, 'config') !== false
                    )) {
                    
                    $jsonColumns[] = [
                        'table' => $table,
                        'field' => $field,
                        'type' => $type
                    ];
                }
            }
            
        } catch (Exception $e) {
            echo "  ⚠️  Error describiendo tabla $table: " . $e->getMessage() . "\n";
        }
    }
    
    if (!empty($jsonColumns)) {
        echo "  📊 Columnas JSON/potencialmente JSON encontradas:\n";
        foreach ($jsonColumns as $col) {
            echo "    🔹 {$col['table']}.{$col['field']} - {$col['type']}\n";
        }
    } else {
        echo "  ✅ No se encontraron columnas JSON específicas\n";
    }
    echo "\n";
    
    // 3. Verificar versión de MySQL/MariaDB
    echo "🔧 3. INFORMACIÓN DE BASE DE DATOS:\n";
    $stmt = $pdo->query("SELECT VERSION() as version");
    $version = $stmt->fetch()['version'];
    echo "  📋 Versión: $version\n";
    
    // Determinar si es MySQL o MariaDB y versión
    $isMariaDB = stripos($version, 'maria') !== false;
    if ($isMariaDB) {
        preg_match('/(\d+)\.(\d+)/', $version, $matches);
        $majorVersion = intval($matches[1] ?? 0);
        $minorVersion = intval($matches[2] ?? 0);
        echo "  🔹 Tipo: MariaDB $majorVersion.$minorVersion\n";
        
        // MariaDB soporta JSON desde 10.2
        if ($majorVersion >= 10 && $minorVersion >= 2) {
            echo "  ✅ Soporte JSON: DISPONIBLE\n";
        } else {
            echo "  ❌ Soporte JSON: NO DISPONIBLE (requiere MariaDB 10.2+)\n";
        }
    } else {
        preg_match('/(\d+)\.(\d+)/', $version, $matches);
        $majorVersion = intval($matches[1] ?? 0);
        $minorVersion = intval($matches[2] ?? 0);
        echo "  🔹 Tipo: MySQL $majorVersion.$minorVersion\n";
        
        // MySQL soporta JSON desde 5.7
        if ($majorVersion >= 8 || ($majorVersion == 5 && $minorVersion >= 7)) {
            echo "  ✅ Soporte JSON: DISPONIBLE\n";
        } else {
            echo "  ❌ Soporte JSON: NO DISPONIBLE (requiere MySQL 5.7+)\n";
        }
    }
    echo "\n";
    
    // 4. Buscar queries problemáticas en archivos PHP
    echo "🔎 4. BUSCANDO QUERIES JSON_EXTRACT EN CÓDIGO:\n";
    $phpFiles = glob("*.php");
    $apiFiles = glob("api/*.php");
    $allFiles = array_merge($phpFiles, $apiFiles);
    
    $jsonExtractFound = false;
    foreach ($allFiles as $file) {
        $content = file_get_contents($file);
        if (stripos($content, 'JSON_EXTRACT') !== false) {
            echo "  ⚠️  JSON_EXTRACT encontrado en: $file\n";
            $jsonExtractFound = true;
            
            // Extraer las líneas con JSON_EXTRACT
            $lines = explode("\n", $content);
            foreach ($lines as $num => $line) {
                if (stripos($line, 'JSON_EXTRACT') !== false) {
                    echo "    📄 Línea " . ($num + 1) . ": " . trim($line) . "\n";
                }
            }
        }
    }
    
    if (!$jsonExtractFound) {
        echo "  ✅ No se encontraron queries JSON_EXTRACT en archivos PHP\n";
    }
    echo "\n";
    
    // 5. Proponer soluciones
    echo "💡 5. RECOMENDACIONES:\n";
    
    if (!empty($jsonColumns)) {
        echo "  🔧 Para columnas JSON encontradas:\n";
        foreach ($jsonColumns as $col) {
            echo "    📋 {$col['table']}.{$col['field']}:\n";
            
            if (stripos($col['type'], 'json') === false) {
                echo "      ⚠️  Tipo '{$col['type']}' no es JSON nativo\n";
                echo "      💡 Considerar: ALTER TABLE {$col['table']} MODIFY {$col['field']} JSON;\n";
            }
            
            echo "      💡 Alternativa: Crear columnas normalizadas para campos frecuentes\n";
            
            if (stripos($col['field'], 'context') !== false) {
                echo "      📝 Ejemplo: ALTER TABLE {$col['table']} ADD COLUMN job_id VARCHAR(50), ADD COLUMN hotel_id INT;\n";
            }
        }
        echo "\n";
    }
    
    echo "  🛡️  MEJORES PRÁCTICAS:\n";
    echo "    1. ✅ Usar columnas normalizadas para campos consultados frecuentemente\n";
    echo "    2. ✅ Validar versión de BD antes de usar JSON_EXTRACT\n";
    echo "    3. ✅ Crear índices en columnas normalizadas, no en JSON_EXTRACT\n";
    echo "    4. ✅ Usar COALESCE para compatibilidad: COALESCE(job_id, JSON_EXTRACT(context, '$.job_id'))\n";
    echo "    5. ✅ Migrar gradualmente: primero llenar columnas normalizadas, luego cambiar queries\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>