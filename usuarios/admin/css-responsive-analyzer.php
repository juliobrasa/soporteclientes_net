<?php
/**
 * ==========================================================================
 * ANALIZADOR DE CSS RESPONSIVE - FASE 8
 * Kavia Hoteles Panel de Administración
 * Script para analizar archivos CSS y detectar problemas de responsive
 * ==========================================================================
 */

// Headers para JSON
header('Content-Type: application/json; charset=utf-8');

/**
 * Clase para analizar CSS Responsive
 */
class CSSResponsiveAnalyzer {
    private $cssFiles = [];
    private $breakpoints = [];
    private $issues = [];
    private $recommendations = [];
    
    public function __construct() {
        $this->cssFiles = [
            'assets/css/admin-base.css',
            'assets/css/admin-components.css', 
            'assets/css/admin-tables.css',
            'assets/css/admin-modals.css'
        ];
        
        $this->breakpoints = [
            320 => 'Mobile Small',
            375 => 'Mobile',
            480 => 'Mobile Large', 
            768 => 'Tablet',
            1024 => 'Desktop',
            1200 => 'Large Desktop',
            1920 => 'Extra Large Desktop'
        ];
    }
    
    /**
     * Ejecutar análisis completo
     */
    public function runCompleteAnalysis() {
        $analysis = [
            'timestamp' => date('Y-m-d H:i:s'),
            'files_analyzed' => 0,
            'total_lines' => 0,
            'media_queries_found' => 0,
            'breakpoints_detected' => [],
            'issues' => [],
            'recommendations' => [],
            'responsive_score' => 0,
            'file_details' => []
        ];
        
        foreach ($this->cssFiles as $file) {
            if (file_exists($file)) {
                $fileAnalysis = $this->analyzeFile($file);
                $analysis['file_details'][$file] = $fileAnalysis;
                $analysis['files_analyzed']++;
                $analysis['total_lines'] += $fileAnalysis['lines'];
                $analysis['media_queries_found'] += $fileAnalysis['media_queries'];
            } else {
                $analysis['issues'][] = [
                    'type' => 'missing_file',
                    'severity' => 'warning',
                    'file' => $file,
                    'message' => "Archivo CSS no encontrado: $file"
                ];
            }
        }
        
        // Analizar breakpoints globales
        $analysis['breakpoints_detected'] = $this->analyzeBreakpoints();
        
        // Generar recomendaciones
        $analysis['recommendations'] = $this->generateRecommendations($analysis);
        
        // Calcular score
        $analysis['responsive_score'] = $this->calculateResponsiveScore($analysis);
        
        return $analysis;
    }
    
    /**
     * Analizar archivo CSS individual
     */
    private function analyzeFile($filePath) {
        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);
        
        $analysis = [
            'file' => $filePath,
            'lines' => count($lines),
            'size_kb' => round(filesize($filePath) / 1024, 2),
            'media_queries' => 0,
            'breakpoints' => [],
            'responsive_properties' => [],
            'issues' => [],
            'last_modified' => date('Y-m-d H:i:s', filemtime($filePath))
        ];
        
        // Buscar media queries
        preg_match_all('/@media\s+[^{]+\{/', $content, $mediaQueries);
        $analysis['media_queries'] = count($mediaQueries[0]);
        
        // Extraer breakpoints
        foreach ($mediaQueries[0] as $mediaQuery) {
            preg_match_all('/(\d+)px/', $mediaQuery, $pixels);
            foreach ($pixels[1] as $pixel) {
                $analysis['breakpoints'][] = intval($pixel);
            }
        }
        
        // Buscar propiedades responsive comunes
        $responsiveProps = [
            'flex-wrap', 'flex-direction', 'grid-template-columns', 
            'display: flex', 'display: grid', 'width: 100%', 
            'max-width', 'min-width', 'transform', 'overflow'
        ];
        
        foreach ($responsiveProps as $prop) {
            if (strpos($content, $prop) !== false) {
                $analysis['responsive_properties'][] = $prop;
            }
        }
        
        // Detectar posibles problemas
        $this->detectCSSIssues($content, $filePath, $analysis);
        
        return $analysis;
    }
    
    /**
     * Detectar problemas en CSS
     */
    private function detectCSSIssues($content, $filePath, &$analysis) {
        // 1. Fixed widths sin media queries
        if (preg_match_all('/width:\s*\d+px(?![^}]*@media)/', $content)) {
            $analysis['issues'][] = [
                'type' => 'fixed_width',
                'severity' => 'warning',
                'message' => 'Anchos fijos encontrados que podrían no ser responsive'
            ];
        }
        
        // 2. Overflow hidden que puede ocultar contenido
        if (strpos($content, 'overflow: hidden') !== false) {
            $analysis['issues'][] = [
                'type' => 'overflow_hidden', 
                'severity' => 'info',
                'message' => 'overflow: hidden encontrado - verificar que no oculte contenido importante'
            ];
        }
        
        // 3. Elementos muy pequeños para móvil
        if (preg_match('/font-size:\s*[1-9]px/', $content)) {
            $analysis['issues'][] = [
                'type' => 'small_font',
                'severity' => 'warning',
                'message' => 'Fuentes muy pequeñas (menos de 10px) pueden ser ilegibles en móvil'
            ];
        }
        
        // 4. Z-index muy altos
        if (preg_match('/z-index:\s*[0-9]{4,}/', $content)) {
            $analysis['issues'][] = [
                'type' => 'high_z_index',
                'severity' => 'info', 
                'message' => 'Z-index muy altos encontrados - considerar reorganizar capas'
            ];
        }
        
        // 5. !important excesivo
        $importantCount = substr_count($content, '!important');
        if ($importantCount > 10) {
            $analysis['issues'][] = [
                'type' => 'excessive_important',
                'severity' => 'warning',
                'message' => "Demasiados !important ($importantCount) - puede dificultar mantenimiento"
            ];
        }
        
        // 6. Verificar si tiene breakpoints móviles básicos
        $hasMobileBreakpoint = false;
        foreach ([320, 375, 480, 768] as $mobile) {
            if (strpos($content, $mobile . 'px') !== false) {
                $hasMobileBreakpoint = true;
                break;
            }
        }
        
        if (!$hasMobileBreakpoint && $analysis['media_queries'] == 0) {
            $analysis['issues'][] = [
                'type' => 'no_mobile_support',
                'severity' => 'error',
                'message' => 'No se encontraron media queries para móvil'
            ];
        }
    }
    
    /**
     * Analizar breakpoints globales
     */
    private function analyzeBreakpoints() {
        $detectedBreakpoints = [];
        
        foreach ($this->cssFiles as $file) {
            if (file_exists($file)) {
                $content = file_get_contents($file);
                preg_match_all('/@media[^{]*\(.*?(\d+)px/', $content, $matches);
                
                foreach ($matches[1] as $breakpoint) {
                    $bp = intval($breakpoint);
                    if (!isset($detectedBreakpoints[$bp])) {
                        $detectedBreakpoints[$bp] = [
                            'breakpoint' => $bp,
                            'name' => $this->getBreakpointName($bp),
                            'files' => [],
                            'usage_count' => 0
                        ];
                    }
                    
                    if (!in_array($file, $detectedBreakpoints[$bp]['files'])) {
                        $detectedBreakpoints[$bp]['files'][] = $file;
                    }
                    $detectedBreakpoints[$bp]['usage_count']++;
                }
            }
        }
        
        ksort($detectedBreakpoints);
        return array_values($detectedBreakpoints);
    }
    
    /**
     * Obtener nombre del breakpoint
     */
    private function getBreakpointName($breakpoint) {
        foreach ($this->breakpoints as $bp => $name) {
            if ($breakpoint <= $bp) {
                return $name;
            }
        }
        return 'Custom Breakpoint';
    }
    
    /**
     * Generar recomendaciones
     */
    private function generateRecommendations($analysis) {
        $recommendations = [];
        
        // Basado en breakpoints detectados
        $breakpointValues = array_column($analysis['breakpoints_detected'], 'breakpoint');
        
        if (!in_array(320, $breakpointValues)) {
            $recommendations[] = [
                'type' => 'missing_breakpoint',
                'priority' => 'high',
                'title' => 'Agregar soporte para móvil pequeño',
                'description' => 'Considerar agregar media query para 320px (móviles pequeños)',
                'implementation' => '@media (max-width: 320px) { /* estilos móvil pequeño */ }'
            ];
        }
        
        if (!in_array(768, $breakpointValues)) {
            $recommendations[] = [
                'type' => 'missing_breakpoint',
                'priority' => 'high',
                'title' => 'Agregar soporte para tablet',
                'description' => 'Falta breakpoint crítico para tablets (768px)',
                'implementation' => '@media (max-width: 768px) { /* estilos tablet */ }'
            ];
        }
        
        // Basado en cantidad de media queries
        if ($analysis['media_queries_found'] < 5) {
            $recommendations[] = [
                'type' => 'insufficient_media_queries',
                'priority' => 'medium',
                'title' => 'Incrementar media queries responsive',
                'description' => 'Pocos media queries encontrados. Considerar más adaptaciones responsive.',
                'implementation' => 'Agregar más breakpoints para mejor experiencia multi-dispositivo'
            ];
        }
        
        // Recomendaciones generales
        $recommendations[] = [
            'type' => 'best_practice',
            'priority' => 'low',
            'title' => 'Implementar Mobile-First',
            'description' => 'Considerar estrategia Mobile-First para mejor performance',
            'implementation' => 'Escribir estilos base para móvil y usar min-width para desktop'
        ];
        
        $recommendations[] = [
            'type' => 'best_practice', 
            'priority' => 'low',
            'title' => 'Optimizar para touch',
            'description' => 'Asegurar elementos táctiles mínimo 44px de altura',
            'implementation' => 'button, input, .clickable { min-height: 44px; min-width: 44px; }'
        ];
        
        $recommendations[] = [
            'type' => 'performance',
            'priority' => 'medium',
            'title' => 'Considerar CSS Container Queries',
            'description' => 'Para componentes más modulares y responsive',
            'implementation' => '@container (max-width: 300px) { .component { /* estilos */ } }'
        ];
        
        return $recommendations;
    }
    
    /**
     * Calcular score de responsive
     */
    private function calculateResponsiveScore($analysis) {
        $score = 100;
        
        // Penalizar por archivos faltantes
        $missingFiles = 4 - $analysis['files_analyzed'];
        $score -= ($missingFiles * 15);
        
        // Penalizar por falta de media queries
        if ($analysis['media_queries_found'] < 3) {
            $score -= 20;
        }
        
        // Penalizar por falta de breakpoints críticos
        $criticalBreakpoints = [320, 768, 1024];
        $detectedBreakpoints = array_column($analysis['breakpoints_detected'], 'breakpoint');
        
        foreach ($criticalBreakpoints as $critical) {
            if (!in_array($critical, $detectedBreakpoints)) {
                $score -= 15;
            }
        }
        
        // Bonificar por buenas prácticas
        if ($analysis['media_queries_found'] > 10) {
            $score += 5;
        }
        
        if (count($analysis['breakpoints_detected']) >= 5) {
            $score += 10;
        }
        
        // Penalizar por issues críticos
        foreach ($analysis['file_details'] as $fileDetail) {
            foreach ($fileDetail['issues'] as $issue) {
                if ($issue['severity'] === 'error') {
                    $score -= 10;
                } elseif ($issue['severity'] === 'warning') {
                    $score -= 5;
                }
            }
        }
        
        return max(0, min(100, $score));
    }
    
    /**
     * Generar reporte de responsive específico
     */
    public function generateResponsiveReport() {
        $analysis = $this->runCompleteAnalysis();
        
        $report = [
            'summary' => [
                'responsive_score' => $analysis['responsive_score'],
                'grade' => $this->getResponsiveGrade($analysis['responsive_score']),
                'total_breakpoints' => count($analysis['breakpoints_detected']),
                'media_queries' => $analysis['media_queries_found'],
                'files_analyzed' => $analysis['files_analyzed']
            ],
            'details' => $analysis,
            'action_items' => $this->generateActionItems($analysis)
        ];
        
        return $report;
    }
    
    /**
     * Obtener calificación de responsive
     */
    private function getResponsiveGrade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 80) return 'A';
        if ($score >= 70) return 'B';
        if ($score >= 60) return 'C';
        if ($score >= 50) return 'D';
        return 'F';
    }
    
    /**
     * Generar items de acción
     */
    private function generateActionItems($analysis) {
        $actions = [];
        
        // Acciones basadas en score
        if ($analysis['responsive_score'] < 70) {
            $actions[] = [
                'priority' => 'urgent',
                'action' => 'Mejorar soporte responsive básico',
                'description' => 'Score bajo indica problemas fundamentales de responsive design'
            ];
        }
        
        // Acciones basadas en breakpoints faltantes
        $detectedBreakpoints = array_column($analysis['breakpoints_detected'], 'breakpoint');
        if (!in_array(768, $detectedBreakpoints)) {
            $actions[] = [
                'priority' => 'high',
                'action' => 'Implementar breakpoint para tablet (768px)',
                'description' => 'Crítico para experiencia en tablets'
            ];
        }
        
        if (!in_array(320, $detectedBreakpoints)) {
            $actions[] = [
                'priority' => 'high', 
                'action' => 'Agregar soporte para móvil pequeño (320px)',
                'description' => 'Mejorar compatibilidad con móviles pequeños'
            ];
        }
        
        // Acciones basadas en issues
        foreach ($analysis['file_details'] as $file => $details) {
            foreach ($details['issues'] as $issue) {
                if ($issue['severity'] === 'error') {
                    $actions[] = [
                        'priority' => 'urgent',
                        'action' => "Corregir error en $file",
                        'description' => $issue['message']
                    ];
                }
            }
        }
        
        return $actions;
    }
}

// Procesar solicitud
$action = $_GET['action'] ?? 'analyze';

try {
    $analyzer = new CSSResponsiveAnalyzer();
    
    switch($action) {
        case 'analyze':
            $result = $analyzer->runCompleteAnalysis();
            break;
            
        case 'report':
            $result = $analyzer->generateResponsiveReport();
            break;
            
        default:
            $result = $analyzer->runCompleteAnalysis();
            break;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result,
        'message' => 'Análisis de CSS responsive completado'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch(Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>