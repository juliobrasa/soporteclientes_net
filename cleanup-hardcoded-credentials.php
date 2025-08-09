<?php
/**
 * Script para limpiar credenciales hardcoded del código fuente
 */

echo "🧹 LIMPIEZA DE CREDENCIALES HARDCODED\n";
echo str_repeat("=", 45) . "\n\n";

class HardcodedCredentialCleaner 
{
    private $filesToCheck = [
        'admin-config.php',
        'api/config.php',
        'env-loader.php',
        'apify-config.php'
    ];
    
    private $credentialsFound = [];
    
    public function cleanAll() 
    {
        echo "🔍 Buscando credenciales hardcoded...\n\n";
        
        foreach ($this->filesToCheck as $file) {
            if (file_exists($file)) {
                $this->analyzeFile($file);
            } else {
                echo "  ⚠️ Archivo no encontrado: $file\n";
            }
        }
        
        $this->generateCleanupReport();
        $this->provideFinalInstructions();
    }
    
    private function analyzeFile($file) 
    {
        echo "📄 Analizando: $file\n";
        
        $content = file_get_contents($file);
        $issues = [];
        
        // Patrones de credenciales peligrosas
        $patterns = [
            'hardcoded_password' => '/DB_PASS[\'"]?\s*=\s*[\'"]([^\'";]+)[\'"];?/',
            'hardcoded_user' => '/DB_USER[\'"]?\s*=\s*[\'"]([^\'";]+)[\'"];?/',
            'password_array' => '/[\'"]password[\'"]?\s*=>\s*[\'"]([^\'";]+)[\'"]/',
            'mysql_connect' => '/mysql.*password[\'"]?\s*=>\s*[\'"]([^\'";]+)[\'"]/',
            'plain_credentials' => '/[\'"]QCF8RhS\*\}\\.Oj0u\(v[\'"]/',
        ];
        
        foreach ($patterns as $type => $pattern) {
            if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $i => $match) {
                    $lineNum = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $credential = isset($matches[1][$i]) ? $matches[1][$i][0] : 'N/A';
                    
                    $issues[] = [
                        'type' => $type,
                        'line' => $lineNum,
                        'context' => trim($match[0]),
                        'credential' => $credential
                    ];
                }
            }
        }
        
        if (!empty($issues)) {
            echo "  ❌ Credenciales encontradas:\n";
            foreach ($issues as $issue) {
                echo "    - Línea {$issue['line']}: {$issue['type']} - {$issue['context']}\n";
            }
            $this->credentialsFound[$file] = $issues;
        } else {
            echo "  ✅ Ninguna credencial hardcoded detectada\n";
        }
        
        echo "\n";
    }
    
    private function generateCleanupReport() 
    {
        if (empty($this->credentialsFound)) {
            echo "🎉 ¡No se encontraron credenciales hardcoded!\n\n";
            return;
        }
        
        echo "📊 REPORTE DE CREDENCIALES ENCONTRADAS:\n";
        echo str_repeat("-", 40) . "\n";
        
        $totalIssues = 0;
        foreach ($this->credentialsFound as $file => $issues) {
            echo "🔴 $file (" . count($issues) . " problemas):\n";
            foreach ($issues as $issue) {
                echo "   Línea {$issue['line']}: {$issue['context']}\n";
                $totalIssues++;
            }
            echo "\n";
        }
        
        echo "Total de problemas: $totalIssues\n\n";
        
        // Generar script de limpieza automático
        $this->generateCleanupScript();
    }
    
    private function generateCleanupScript() 
    {
        $scriptContent = "#!/bin/bash\n";
        $scriptContent .= "# Script automático de limpieza de credenciales\n";
        $scriptContent .= "# Generado: " . date('Y-m-d H:i:s') . "\n\n";
        
        $scriptContent .= "echo '🧹 Ejecutando limpieza automática de credenciales...'\n\n";
        
        foreach ($this->credentialsFound as $file => $issues) {
            $scriptContent .= "# Limpiar $file\n";
            $scriptContent .= "echo 'Limpiando $file...'\n";
            
            foreach ($issues as $issue) {
                if ($issue['type'] === 'hardcoded_password') {
                    $scriptContent .= "sed -i 's/" . preg_quote($issue['context'], '/') . "/# CREDENCIAL REMOVIDA - usar EnvironmentLoader/g' $file\n";
                }
            }
            
            $scriptContent .= "\n";
        }
        
        $scriptContent .= "echo '✅ Limpieza completada'\n";
        
        $scriptFile = __DIR__ . '/cleanup-credentials.sh';
        file_put_contents($scriptFile, $scriptContent);
        chmod($scriptFile, 0755);
        
        echo "🤖 Script de limpieza automática generado: cleanup-credentials.sh\n";
        echo "   Ejecutar con: ./cleanup-credentials.sh\n\n";
    }
    
    private function provideFinalInstructions() 
    {
        echo "📋 INSTRUCCIONES FINALES DE LIMPIEZA:\n";
        echo str_repeat("-", 40) . "\n";
        
        echo "1. 🔧 APLICAR SCRIPT SQL:\n";
        echo "   mysql -u root -p < backup-credentials/rotation-script-*.sql\n\n";
        
        echo "2. 🔄 ACTUALIZAR .env.local:\n";
        echo "   cp backup-credentials/.env.local.new .env.local\n\n";
        
        echo "3. 🧹 LIMPIAR CÓDIGO FUENTE:\n";
        if (!empty($this->credentialsFound)) {
            echo "   ./cleanup-credentials.sh\n";
        } else {
            echo "   ✅ No se requiere limpieza adicional\n";
        }
        echo "\n";
        
        echo "4. 🧪 PROBAR APLICACIÓN:\n";
        echo "   - Verificar conexión BD\n";
        echo "   - Probar login admin\n";
        echo "   - Ejecutar extracción de prueba\n\n";
        
        echo "5. 🗑️ ELIMINAR ARCHIVOS TEMPORALES:\n";
        echo "   rm -rf backup-credentials/\n";
        echo "   rm rotate-db-credentials.php\n";
        echo "   rm cleanup-hardcoded-credentials.php\n\n";
        
        echo "6. 📤 COMMIT CAMBIOS SEGUROS:\n";
        echo "   git add .\n";
        echo "   git commit -m 'Remove hardcoded credentials and rotate DB access'\n";
        echo "   git push\n\n";
        
        echo "⚠️ VERIFICACIONES FINALES:\n";
        echo "- Confirmar que credenciales antiguas no funcionan\n";
        echo "- Buscar en historial Git: git log --all --grep='password'\n";
        echo "- Auditar logs de acceso a BD\n";
        echo "- Verificar permisos de archivos: find . -name '*.php' -perm 777\n\n";
    }
}

// Ejecutar limpieza si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $cleaner = new HardcodedCredentialCleaner();
    $cleaner->cleanAll();
}
?>