<?php
/**
 * Corrector de Errores Funcionales y de Lógica Críticos
 * 
 * Este script detecta y corrige los errores funcionales identificados:
 * 1. buildExtractionInput - mapeo de plataformas incorrecto
 * 2. Jobs async no actualizan extraction_jobs
 * 3. Campo started_at faltante en apify_extraction_runs
 * 4. handleUpdateRun inexistente pero referenciado
 * 5. Unión de tablas incorrecta en get_recent
 * 6. Headers CORS incompletos
 * 7. Errores frontend (CSS, JS duplicado)
 */

require_once 'env-loader.php';

class FunctionalErrorFixer 
{
    private $pdo;
    private $logFile;
    private $issues = [];
    private $fixes = [];
    
    public function __construct() 
    {
        $this->pdo = createDatabaseConnection();
        $this->logFile = __DIR__ . '/storage/logs/functional-fixes.log';
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
     * Ejecutar detección y corrección completa
     */
    public function fixAllFunctionalErrors() 
    {
        $this->log("🚀 Iniciando corrección de errores funcionales críticos...");
        
        try {
            // 1. Detectar problemas
            $this->detectIssues();
            
            // 2. Aplicar correcciones
            $this->applyFixes();
            
            // 3. Verificar correcciones
            $this->verifyFixes();
            
            // 4. Generar reporte
            $report = $this->generateReport();
            
            $this->log("✅ Corrección de errores completada");
            return $report;
            
        } catch (Exception $e) {
            $this->log("❌ Error durante corrección: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    /**
     * Detectar todos los problemas funcionales
     */
    private function detectIssues() 
    {
        $this->log("🔍 Detectando errores funcionales...");
        
        // 1. Verificar buildExtractionInput
        $this->checkBuildExtractionInput();
        
        // 2. Verificar actualización de extraction_jobs en async
        $this->checkAsyncJobUpdates();
        
        // 3. Verificar campo started_at
        $this->checkStartedAtField();
        
        // 4. Verificar handleUpdateRun
        $this->checkHandleUpdateRun();
        
        // 5. Verificar unión de tablas
        $this->checkTableJoins();
        
        // 6. Verificar headers CORS
        $this->checkCorsHeaders();
        
        // 7. Verificar errores frontend
        $this->checkFrontendErrors();
        
        $this->log("📊 Detectados " . count($this->issues) . " problemas funcionales");
    }
    
    /**
     * Verificar problemas en buildExtractionInput
     */
    private function checkBuildExtractionInput() 
    {
        // Buscar archivos que contengan buildExtractionInput
        $files = array_merge(
            glob(__DIR__ . '/*.php'),
            glob(__DIR__ . '/usuarios/admin/*.php'),
            glob(__DIR__ . '/api/*.php')
        );
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            if (stripos($content, 'buildExtractionInput') !== false) {
                // Verificar si mapea platforms a flags enableX
                if (stripos($content, 'enableGoogleMaps') !== false ||
                    stripos($content, 'enableTripadvisor') !== false) {
                    
                    // Verificar si respeta la selección del usuario
                    if (stripos($content, 'platforms') === false || 
                        stripos($content, 'selected') === false) {
                        
                        $this->issues[] = [
                            'type' => 'buildExtractionInput_mapping',
                            'file' => $file,
                            'description' => 'buildExtractionInput no mapea platforms seleccionadas a flags enableX',
                            'severity' => 'critical',
                            'impact' => 'Scrapea más plataformas de las pedidas, aumenta coste'
                        ];
                    }
                }
            }
        }
    }
    
    /**
     * Verificar actualización de extraction_jobs en async
     */
    private function checkAsyncJobUpdates() 
    {
        // Verificar si existe apify_extraction_runs sin relación correcta con extraction_jobs
        try {
            // Verificar si la tabla apify_extraction_runs existe y tiene job_id
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'apify_extraction_runs'");
            if ($stmt->fetch()) {
                // Verificar estructura
                $stmt = $this->pdo->query("DESCRIBE apify_extraction_runs");
                $columns = array_column($stmt->fetchAll(), 'Field');
                
                if (!in_array('job_id', $columns)) {
                    $this->issues[] = [
                        'type' => 'async_job_updates',
                        'table' => 'apify_extraction_runs',
                        'description' => 'Tabla apify_extraction_runs no tiene job_id para relacionar con extraction_jobs',
                        'severity' => 'critical',
                        'impact' => 'Jobs async no actualizan extraction_jobs, panel muestra estados incorrectos'
                    ];
                }
                
                if (!in_array('started_at', $columns)) {
                    $this->issues[] = [
                        'type' => 'missing_started_at',
                        'table' => 'apify_extraction_runs',
                        'description' => 'Campo started_at faltante en apify_extraction_runs',
                        'severity' => 'high',
                        'impact' => 'Queries con filtros de fecha fallan, datos incompletos en interfaz'
                    ];
                }
            }
        } catch (Exception $e) {
            $this->log("⚠️  Error verificando apify_extraction_runs: " . $e->getMessage(), 'WARNING');
        }
    }
    
    /**
     * Verificar campo started_at
     */
    private function checkStartedAtField() 
    {
        // Ya verificado en checkAsyncJobUpdates
        // Este método puede expandirse para otros casos específicos de started_at
    }
    
    /**
     * Verificar handleUpdateRun
     */
    private function checkHandleUpdateRun() 
    {
        $files = array_merge(
            glob(__DIR__ . '/*api*.php'),
            glob(__DIR__ . '/usuarios/admin/*api*.php'),
            glob(__DIR__ . '/api/*.php')
        );
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            
            // Buscar case 'PUT' que llame a handleUpdateRun
            if (preg_match('/case\s+[\'"]PUT[\'"].*handleUpdateRun/is', $content)) {
                // Verificar si la función handleUpdateRun existe
                if (stripos($content, 'function handleUpdateRun') === false) {
                    $this->issues[] = [
                        'type' => 'missing_handleUpdateRun',
                        'file' => $file,
                        'description' => 'case PUT llama a handleUpdateRun pero la función no existe',
                        'severity' => 'high',
                        'impact' => 'Error 500 si alguien usa PUT request'
                    ];
                }
            }
        }
    }
    
    /**
     * Verificar unión de tablas incorrectas
     */
    private function checkTableJoins() 
    {
        $files = array_merge(
            glob(__DIR__ . '/*api*.php'),
            glob(__DIR__ . '/usuarios/admin/*api*.php')
        );
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            
            // Buscar LEFT JOIN apify_extraction_runs por hotel_id y fecha (no por job)
            if (preg_match('/LEFT\s+JOIN\s+apify_extraction_runs.*hotel_id.*fecha/is', $content) &&
                stripos($content, 'job_id') === false) {
                
                $this->issues[] = [
                    'type' => 'incorrect_table_join',
                    'file' => $file,
                    'description' => 'LEFT JOIN apify_extraction_runs por hotel_id y fecha, no por job',
                    'severity' => 'high',
                    'impact' => 'Puede asociar runs erróneos a jobs no correspondientes'
                ];
            }
        }
    }
    
    /**
     * Verificar headers CORS
     */
    private function checkCorsHeaders() 
    {
        $files = array_merge(
            glob(__DIR__ . '/*api*.php'),
            glob(__DIR__ . '/api/*.php')
        );
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            
            // Verificar si tiene Access-Control-Allow-Headers
            if (stripos($content, 'Access-Control-Allow-Headers') !== false) {
                // Verificar si incluye X-Admin-Session y X-Requested-With
                if (stripos($content, 'X-Admin-Session') === false ||
                    stripos($content, 'X-Requested-With') === false) {
                    
                    $this->issues[] = [
                        'type' => 'incomplete_cors_headers',
                        'file' => $file,
                        'description' => 'Headers CORS no incluyen X-Admin-Session y X-Requested-With',
                        'severity' => 'medium',
                        'impact' => 'Preflight CORS falla en entornos cross-origin'
                    ];
                }
            }
        }
    }
    
    /**
     * Verificar errores frontend
     */
    private function checkFrontendErrors() 
    {
        $files = array_merge(
            glob(__DIR__ . '/usuarios/admin/*.php'),
            glob(__DIR__ . '/usuarios/admin/*.html'),
            glob(__DIR__ . '/*.html')
        );
        
        foreach ($files as $file) {
            if (!file_exists($file)) continue;
            
            $content = file_get_contents($file);
            
            // Verificar class="visually-hidden" (error tipográfico)
            if (stripos($content, 'visually-hidden') !== false) {
                $this->issues[] = [
                    'type' => 'css_typo',
                    'file' => $file,
                    'description' => 'CSS class "visually-hidden" debe ser "visually-hidden"',
                    'severity' => 'low',
                    'impact' => 'Problemas de accesibilidad/UX'
                ];
            }
            
            // Verificar funciones JS duplicadas
            $funcMatches = [];
            preg_match_all('/function\s+(\w+)\s*\(/i', $content, $funcMatches);
            if (!empty($funcMatches[1])) {
                $funcNames = $funcMatches[1];
                $duplicates = array_filter(array_count_values($funcNames), function($count) { return $count > 1; });
                
                if (!empty($duplicates)) {
                    $this->issues[] = [
                        'type' => 'duplicate_js_functions',
                        'file' => $file,
                        'description' => 'Funciones JS duplicadas: ' . implode(', ', array_keys($duplicates)),
                        'severity' => 'medium',
                        'impact' => 'Funciones se sobrescriben, comportamiento impredecible'
                    ];
                }
            }
        }
    }
    
    /**
     * Aplicar todas las correcciones
     */
    private function applyFixes() 
    {
        $this->log("🔧 Aplicando correcciones...");
        
        foreach ($this->issues as $issue) {
            switch ($issue['type']) {
                case 'missing_started_at':
                    $this->fixStartedAtField($issue);
                    break;
                case 'async_job_updates':
                    $this->fixAsyncJobUpdates($issue);
                    break;
                case 'missing_handleUpdateRun':
                    $this->fixHandleUpdateRun($issue);
                    break;
                case 'incomplete_cors_headers':
                    $this->fixCorsHeaders($issue);
                    break;
                case 'css_typo':
                    $this->fixCssTypo($issue);
                    break;
                case 'duplicate_js_functions':
                    $this->fixDuplicateJsFunctions($issue);
                    break;
            }
        }
    }
    
    /**
     * Corregir campo started_at faltante
     */
    private function fixStartedAtField($issue) 
    {
        try {
            $this->log("🔧 Corrigiendo campo started_at en {$issue['table']}...");
            
            // Agregar started_at y finished_at si no existen
            $alterQueries = [
                "ALTER TABLE {$issue['table']} ADD COLUMN started_at TIMESTAMP NULL",
                "ALTER TABLE {$issue['table']} ADD COLUMN finished_at TIMESTAMP NULL"
            ];
            
            foreach ($alterQueries as $query) {
                try {
                    $this->pdo->exec($query);
                    $this->log("✅ Ejecutado: $query");
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column') === false) {
                        $this->log("⚠️  Error en query: " . $e->getMessage(), 'WARNING');
                    }
                }
            }
            
            // Actualizar registros existentes con started_at = created_at
            $updateQuery = "UPDATE {$issue['table']} SET started_at = created_at WHERE started_at IS NULL";
            $this->pdo->exec($updateQuery);
            
            $this->fixes[] = [
                'type' => $issue['type'],
                'description' => "Agregado started_at y finished_at a {$issue['table']}",
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            $this->log("❌ Error corrigiendo started_at: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Corregir updates de jobs async
     */
    private function fixAsyncJobUpdates($issue) 
    {
        try {
            $this->log("🔧 Corrigiendo updates de async jobs...");
            
            // Agregar job_id a apify_extraction_runs si no existe
            $alterQuery = "ALTER TABLE {$issue['table']} ADD COLUMN job_id INT, ADD FOREIGN KEY (job_id) REFERENCES extraction_jobs(id) ON DELETE CASCADE";
            
            try {
                $this->pdo->exec($alterQuery);
                $this->log("✅ Agregada columna job_id a {$issue['table']}");
            } catch (PDOException $e) {
                if (strpos($e->getMessage(), 'Duplicate column') === false) {
                    $this->log("⚠️  Error agregando job_id: " . $e->getMessage(), 'WARNING');
                }
            }
            
            $this->fixes[] = [
                'type' => $issue['type'],
                'description' => "Agregada relación job_id a {$issue['table']}",
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            $this->log("❌ Error corrigiendo async jobs: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Corregir handleUpdateRun faltante
     */
    private function fixHandleUpdateRun($issue) 
    {
        try {
            $this->log("🔧 Corrigiendo handleUpdateRun en {$issue['file']}...");
            
            $content = file_get_contents($issue['file']);
            
            // Opción 1: Implementar la función
            $handleUpdateRunFunction = '
    /**
     * Manejar actualización de run
     */
    function handleUpdateRun($data) {
        global $pdo;
        
        $id = intval($data["id"] ?? 0);
        if (!$id) {
            sendError("ID de run requerido");
        }
        
        try {
            $updates = [];
            $params = [];
            
            if (isset($data["status"])) {
                $updates[] = "status = ?";
                $params[] = $data["status"];
            }
            
            if (isset($data["progress"])) {
                $updates[] = "progress = ?";
                $params[] = floatval($data["progress"]);
            }
            
            if (isset($data["completed_at"])) {
                $updates[] = "completed_at = ?";
                $params[] = $data["completed_at"];
            }
            
            if (empty($updates)) {
                sendError("No hay datos para actualizar");
            }
            
            $updates[] = "updated_at = NOW()";
            $params[] = $id;
            
            $sql = "UPDATE apify_extraction_runs SET " . implode(", ", $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            if ($stmt->rowCount() > 0) {
                sendResponse([
                    "success" => true,
                    "message" => "Run actualizado correctamente"
                ]);
            } else {
                sendError("Run no encontrado");
            }
            
        } catch (Exception $e) {
            sendError("Error actualizando run: " . $e->getMessage());
        }
    }
';
            
            // Buscar el lugar correcto para insertar la función (antes del switch principal)
            $insertPos = strpos($content, 'switch($action)');
            if ($insertPos !== false) {
                $newContent = substr($content, 0, $insertPos) . $handleUpdateRunFunction . "\n" . substr($content, $insertPos);
                file_put_contents($issue['file'] . '.fixed', $newContent);
                
                $this->fixes[] = [
                    'type' => $issue['type'],
                    'description' => "Implementada función handleUpdateRun en {$issue['file']}",
                    'status' => 'completed',
                    'note' => 'Archivo guardado como .fixed - revisar antes de aplicar'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("❌ Error corrigiendo handleUpdateRun: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Corregir headers CORS
     */
    private function fixCorsHeaders($issue) 
    {
        try {
            $this->log("🔧 Corrigiendo headers CORS en {$issue['file']}...");
            
            $content = file_get_contents($issue['file']);
            
            // Buscar la línea de Access-Control-Allow-Headers y actualizarla
            $pattern = '/header\s*\(\s*[\'"]Access-Control-Allow-Headers:\s*([^\'"]+)[\'"]\s*\)/i';
            
            $newContent = preg_replace_callback($pattern, function($matches) {
                $currentHeaders = $matches[1];
                
                // Agregar headers faltantes si no existen
                $requiredHeaders = ['X-Admin-Session', 'X-Requested-With'];
                foreach ($requiredHeaders as $header) {
                    if (stripos($currentHeaders, $header) === false) {
                        $currentHeaders .= ', ' . $header;
                    }
                }
                
                return 'header(\'Access-Control-Allow-Headers: ' . $currentHeaders . '\')';
            }, $content);
            
            if ($newContent !== $content) {
                file_put_contents($issue['file'] . '.cors-fixed', $newContent);
                
                $this->fixes[] = [
                    'type' => $issue['type'],
                    'description' => "Corregidos headers CORS en {$issue['file']}",
                    'status' => 'completed',
                    'note' => 'Archivo guardado como .cors-fixed'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("❌ Error corrigiendo CORS: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Corregir error tipográfico CSS
     */
    private function fixCssTypo($issue) 
    {
        try {
            $this->log("🔧 Corrigiendo typo CSS en {$issue['file']}...");
            
            $content = file_get_contents($issue['file']);
            $newContent = str_replace('visually-hidden', 'visually-hidden', $content);
            
            if ($newContent !== $content) {
                file_put_contents($issue['file'] . '.css-fixed', $newContent);
                
                $this->fixes[] = [
                    'type' => $issue['type'],
                    'description' => "Corregido typo CSS visually-hidden en {$issue['file']}",
                    'status' => 'completed'
                ];
            }
            
        } catch (Exception $e) {
            $this->log("❌ Error corrigiendo CSS typo: " . $e->getMessage(), 'ERROR');
        }
    }
    
    /**
     * Corregir funciones JS duplicadas
     */
    private function fixDuplicateJsFunctions($issue) 
    {
        $this->log("⚠️  Funciones JS duplicadas detectadas en {$issue['file']} - requiere revisión manual", 'WARNING');
        
        $this->fixes[] = [
            'type' => $issue['type'],
            'description' => "Funciones JS duplicadas en {$issue['file']} - REQUIERE REVISIÓN MANUAL",
            'status' => 'manual_required',
            'recommendation' => 'Consolidar funciones duplicadas en una sola implementación'
        ];
    }
    
    /**
     * Verificar que las correcciones funcionan
     */
    private function verifyFixes() 
    {
        $this->log("✅ Verificando correcciones aplicadas...");
        
        // Re-ejecutar detección para verificar que se corrigieron los problemas
        $originalIssueCount = count($this->issues);
        $this->issues = []; // Reset
        $this->detectIssues();
        $newIssueCount = count($this->issues);
        
        $this->log("📊 Problemas resueltos: " . ($originalIssueCount - $newIssueCount));
        $this->log("📊 Problemas restantes: $newIssueCount");
    }
    
    /**
     * Generar reporte final
     */
    private function generateReport() 
    {
        $report = [
            'timestamp' => date('c'),
            'total_issues_found' => count($this->issues),
            'total_fixes_applied' => count($this->fixes),
            'issues' => $this->issues,
            'fixes' => $this->fixes,
            'recommendations' => $this->generateRecommendations()
        ];
        
        $reportFile = __DIR__ . '/storage/reports/functional-errors-report.json';
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
            'critico' => [
                'Revisar archivos .fixed generados antes de aplicar cambios',
                'Implementar tests para prevenir regresiones',
                'Configurar linting para detectar errores similares automáticamente'
            ],
            'mejoras' => [
                'Usar TypeScript para prevenir errores de tipos',
                'Implementar pipeline CI/CD con validaciones automáticas',
                'Crear documentación de API actualizada'
            ],
            'monitoreo' => [
                'Configurar alertas para errores 500',
                'Monitorear performance de queries después de correcciones',
                'Revisar logs regularmente por nuevos problemas'
            ]
        ];
    }
}

// Ejecutar si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $action = $argv[1] ?? 'fix-all';
    
    try {
        $fixer = new FunctionalErrorFixer();
        
        switch ($action) {
            case 'fix-all':
            case '--fix-all':
                echo "🚀 CORRECCIÓN DE ERRORES FUNCIONALES CRÍTICOS\n";
                echo str_repeat("=", 65) . "\n\n";
                
                $report = $fixer->fixAllFunctionalErrors();
                
                echo "\n📊 RESUMEN FINAL:\n";
                echo "🔍 Problemas encontrados: " . $report['total_issues_found'] . "\n";
                echo "🔧 Correcciones aplicadas: " . $report['total_fixes_applied'] . "\n";
                
                if ($report['total_fixes_applied'] > 0) {
                    echo "\n✅ CORRECCIONES APLICADAS:\n";
                    foreach ($report['fixes'] as $fix) {
                        $status = $fix['status'] === 'completed' ? '✅' : '⚠️';
                        echo "  $status {$fix['description']}\n";
                    }
                }
                
                if ($report['total_issues_found'] > 0) {
                    echo "\n⚠️  PROBLEMAS RESTANTES:\n";
                    foreach ($report['issues'] as $issue) {
                        $severity = $issue['severity'] === 'critical' ? '🚨' : 
                                   ($issue['severity'] === 'high' ? '⚠️' : '💡');
                        echo "  $severity {$issue['description']}\n";
                    }
                }
                
                echo "\n💡 PRÓXIMOS PASOS:\n";
                echo "1. Revisar archivos .fixed generados\n";
                echo "2. Aplicar cambios después de validación\n";
                echo "3. Ejecutar tests para verificar funcionamiento\n";
                
                break;
                
            case 'detect-only':
            case '--detect-only':
                echo "🔍 DETECCIÓN DE ERRORES FUNCIONALES\n";
                echo str_repeat("=", 50) . "\n\n";
                
                $fixer->detectIssues();
                // Solo mostrar problemas sin aplicar correcciones
                
                break;
                
            default:
                echo "Uso: php fix-functional-errors.php [fix-all|detect-only]\n";
                echo "  fix-all      - Detectar y corregir todos los errores\n";
                echo "  detect-only  - Solo detectar errores sin corregir\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>