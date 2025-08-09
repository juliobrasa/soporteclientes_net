<?php
/**
 * Corrector completo de problemas JSON_EXTRACT para compatibilidad MySQL
 */

echo "🔧 CORRECCIÓN DE PROBLEMAS JSON_EXTRACT\n";
echo str_repeat("=", 50) . "\n\n";

class JSONExtractFixer 
{
    private $filesToCheck = [
        'api-extraction.php',
        'debug-logger.php', 
        'admin-reports.php',
        'api/reviews.php'
    ];
    
    private $fixes = [];
    
    public function fixAll() 
    {
        echo "🔍 Buscando usos de JSON_EXTRACT...\n\n";
        
        foreach ($this->filesToCheck as $file) {
            if (file_exists($file)) {
                $this->analyzeAndFix($file);
            } else {
                echo "  ⚠️ Archivo no encontrado: $file\n";
            }
        }
        
        $this->generateCompatibilityScript();
        $this->displayResults();
    }
    
    private function analyzeAndFix($file) 
    {
        echo "📄 Analizando: $file\n";
        
        $content = file_get_contents($file);
        $originalContent = $content;
        
        // Patrón para encontrar JSON_EXTRACT
        $pattern = '/JSON_EXTRACT\s*\(\s*([^,]+),\s*[\'"]([^\'"]+)[\'"]\s*\)/';
        
        if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
            echo "  🔍 Encontradas " . count($matches[0]) . " ocurrencias de JSON_EXTRACT\n";
            
            // Reemplazar de atrás hacia adelante para preservar offsets
            $matches = array_reverse($matches[0]);
            
            foreach ($matches as $match) {
                $oldCode = $match[0];
                $offset = $match[1];
                
                // Extraer columna y path JSON
                preg_match('/JSON_EXTRACT\s*\(\s*([^,]+),\s*[\'"]([^\'"]+)[\'"]\s*\)/', $oldCode, $parts);
                $column = trim($parts[1]);
                $jsonPath = $parts[2];
                
                // Generar reemplazo compatible
                $newCode = $this->generateCompatibleQuery($column, $jsonPath);
                
                // Reemplazar en contenido
                $content = substr_replace($content, $newCode, $offset, strlen($oldCode));
                
                $this->fixes[] = [
                    'file' => $file,
                    'original' => $oldCode,
                    'replacement' => $newCode,
                    'json_path' => $jsonPath
                ];
            }
            
            // Guardar archivo corregido
            if ($content !== $originalContent) {
                file_put_contents($file . '.json-fixed', $content);
                echo "  ✅ Archivo corregido guardado: $file.json-fixed\n";
            }
        } else {
            echo "  ✅ No se encontraron usos de JSON_EXTRACT\n";
        }
        
        echo "\n";
    }
    
    private function generateCompatibleQuery($column, $jsonPath) 
    {
        // Extraer la clave del path JSON (ej: '$.job_id' -> 'job_id')
        $key = str_replace(['$.', '$[', ']', '"'], '', $jsonPath);
        
        // Generar query compatible usando LIKE patterns
        switch ($key) {
            case 'job_id':
                return "($column LIKE CONCAT('%\"job_id\":\"', ?, '\"%') OR $column LIKE CONCAT('%\"job_id\":', ?, '%'))";
                
            case 'hotel_id':
                return "($column LIKE CONCAT('%\"hotel_id\":\"', ?, '\"%') OR $column LIKE CONCAT('%\"hotel_id\":', ?, '%'))";
                
            case 'run_id':
                return "($column LIKE CONCAT('%\"run_id\":\"', ?, '\"%') OR $column LIKE CONCAT('%\"run_id\":', ?, '%'))";
                
            case 'status':
                return "($column LIKE CONCAT('%\"status\":\"', ?, '\"%'))";
                
            default:
                // Pattern genérico para cualquier clave
                return "($column LIKE CONCAT('%\"$key\":\"', ?, '\"%') OR $column LIKE CONCAT('%\"$key\":', ?, '%'))";
        }
    }
    
    private function generateCompatibilityScript() 
    {
        $scriptContent = "<?php\n";
        $scriptContent .= "/**\n";
        $scriptContent .= " * Script de migración para normalizar columnas JSON\n";
        $scriptContent .= " * Ejecutar después de aplicar correcciones JSON_EXTRACT\n";
        $scriptContent .= " */\n\n";
        
        $scriptContent .= "require_once 'env-loader.php';\n\n";
        
        $scriptContent .= "class JSONColumnNormalizer {\n";
        $scriptContent .= "    private \$pdo;\n\n";
        
        $scriptContent .= "    public function __construct() {\n";
        $scriptContent .= "        \$this->pdo = EnvironmentLoader::createDatabaseConnection();\n";
        $scriptContent .= "    }\n\n";
        
        $scriptContent .= "    public function normalizeDebugLogs() {\n";
        $scriptContent .= "        // Agregar columnas normalizadas si no existen\n";
        $scriptContent .= "        \$alterQueries = [\n";
        $scriptContent .= "            'ALTER TABLE debug_logs ADD COLUMN job_id_extracted VARCHAR(255) NULL',\n";
        $scriptContent .= "            'ALTER TABLE debug_logs ADD COLUMN hotel_id_extracted INT NULL',\n";
        $scriptContent .= "            'ALTER TABLE debug_logs ADD COLUMN run_id_extracted VARCHAR(255) NULL'\n";
        $scriptContent .= "        ];\n\n";
        
        $scriptContent .= "        foreach (\$alterQueries as \$query) {\n";
        $scriptContent .= "            try {\n";
        $scriptContent .= "                \$this->pdo->exec(\$query);\n";
        $scriptContent .= "            } catch (PDOException \$e) {\n";
        $scriptContent .= "                // Columna ya existe\n";
        $scriptContent .= "            }\n";
        $scriptContent .= "        }\n\n";
        
        $scriptContent .= "        // Llenar columnas normalizadas desde JSON existente\n";
        $scriptContent .= "        \$updateQueries = [\n";
        $scriptContent .= "            \"UPDATE debug_logs SET job_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\\$.job_id')) WHERE context LIKE '%job_id%' AND job_id_extracted IS NULL\",\n";
        $scriptContent .= "            \"UPDATE debug_logs SET hotel_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\\$.hotel_id')) WHERE context LIKE '%hotel_id%' AND hotel_id_extracted IS NULL\",\n";
        $scriptContent .= "            \"UPDATE debug_logs SET run_id_extracted = JSON_UNQUOTE(JSON_EXTRACT(context, '\\$.run_id')) WHERE context LIKE '%run_id%' AND run_id_extracted IS NULL\"\n";
        $scriptContent .= "        ];\n\n";
        
        $scriptContent .= "        foreach (\$updateQueries as \$query) {\n";
        $scriptContent .= "            try {\n";
        $scriptContent .= "                \$affected = \$this->pdo->exec(\$query);\n";
        $scriptContent .= "                echo \"Normalizadas \$affected filas\\n\";\n";
        $scriptContent .= "            } catch (PDOException \$e) {\n";
        $scriptContent .= "                echo \"Error: \" . \$e->getMessage() . \"\\n\";\n";
        $scriptContent .= "            }\n";
        $scriptContent .= "        }\n\n";
        
        $scriptContent .= "        // Crear índices para performance\n";
        $scriptContent .= "        \$indexQueries = [\n";
        $scriptContent .= "            'CREATE INDEX idx_debug_logs_job_id ON debug_logs (job_id_extracted)',\n";
        $scriptContent .= "            'CREATE INDEX idx_debug_logs_hotel_id ON debug_logs (hotel_id_extracted)',\n";
        $scriptContent .= "            'CREATE INDEX idx_debug_logs_run_id ON debug_logs (run_id_extracted)'\n";
        $scriptContent .= "        ];\n\n";
        
        $scriptContent .= "        foreach (\$indexQueries as \$query) {\n";
        $scriptContent .= "            try {\n";
        $scriptContent .= "                \$this->pdo->exec(\$query);\n";
        $scriptContent .= "            } catch (PDOException \$e) {\n";
        $scriptContent .= "                // Índice ya existe\n";
        $scriptContent .= "            }\n";
        $scriptContent .= "        }\n";
        $scriptContent .= "    }\n";
        $scriptContent .= "}\n\n";
        
        $scriptContent .= "// Ejecutar normalización\n";
        $scriptContent .= "if (basename(__FILE__) === basename(\$_SERVER['SCRIPT_NAME'])) {\n";
        $scriptContent .= "    \$normalizer = new JSONColumnNormalizer();\n";
        $scriptContent .= "    \$normalizer->normalizeDebugLogs();\n";
        $scriptContent .= "    echo \"✅ Normalización completada\\n\";\n";
        $scriptContent .= "}\n";
        
        file_put_contents(__DIR__ . '/normalize-json-columns.php', $scriptContent);
        echo "📄 Script de normalización generado: normalize-json-columns.php\n\n";
    }
    
    private function displayResults() 
    {
        echo "📊 RESUMEN DE CORRECCIONES:\n";
        echo str_repeat("-", 40) . "\n";
        
        if (empty($this->fixes)) {
            echo "✅ No se encontraron problemas JSON_EXTRACT\n\n";
            return;
        }
        
        $fileCount = count(array_unique(array_column($this->fixes, 'file')));
        echo "Archivos procesados: $fileCount\n";
        echo "Correcciones aplicadas: " . count($this->fixes) . "\n\n";
        
        foreach ($this->fixes as $fix) {
            echo "🔧 {$fix['file']}:\n";
            echo "   ❌ {$fix['original']}\n";
            echo "   ✅ {$fix['replacement']}\n\n";
        }
        
        echo "🎯 PASOS SIGUIENTES:\n";
        echo "1. Revisar archivos *.json-fixed generados\n";
        echo "2. Aplicar cambios si están correctos\n";
        echo "3. Ejecutar normalize-json-columns.php para crear columnas normalizadas\n";
        echo "4. Actualizar queries para usar COALESCE con columnas normalizadas\n";
        echo "5. Monitorear performance y compatibilidad\n\n";
    }
}

// Ejecutar correcciones si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $fixer = new JSONExtractFixer();
    $fixer->fixAll();
}
?>