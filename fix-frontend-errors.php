<?php
/**
 * Corrector de Errores Frontend
 * 
 * Arregla errores de CSS y funciones JavaScript duplicadas
 */

echo "🎨 CORRIGIENDO ERRORES FRONTEND\n";
echo str_repeat("=", 50) . "\n\n";

$issues = [];
$fixes = [];

// 1. Buscar CSS visually-hidden en archivos HTML/PHP
echo "🔍 1. Buscando errores CSS visually-hidden...\n";

$searchDirs = [
    __DIR__ . '/usuarios/admin',
    __DIR__ . '/'
];

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        
        $filename = $file->getPathname();
        $extension = strtolower($file->getExtension());
        
        if (!in_array($extension, ['php', 'html', 'htm'])) continue;
        if (strpos($filename, '/kavia-laravel/') !== false) continue; // Skip Laravel vendor
        if (strpos($filename, '.fixed') !== false) continue; // Skip already fixed
        
        $content = file_get_contents($filename);
        if ($content === false) continue;
        
        if (stripos($content, 'visually-hidden') !== false) {
            $issues[] = [
                'type' => 'css_typo',
                'file' => $filename,
                'error' => 'visually-hidden debe ser visually-hidden'
            ];
            echo "  ❌ $filename: CSS typo encontrado\n";
        }
    }
}

// 2. Buscar funciones JavaScript duplicadas editHotel
echo "\n🔍 2. Analizando funciones JavaScript duplicadas...\n";

$jsFiles = [
    __DIR__ . '/usuarios/admin/admin_main.php',
    __DIR__ . '/usuarios/admin/modules/hotels/hotels-tab.php',
    __DIR__ . '/admin_enhanced.php'
];

$editHotelOccurrences = [];

foreach ($jsFiles as $file) {
    if (!file_exists($file)) continue;
    
    $content = file_get_contents($file);
    if (strpos($content, 'function editHotel') !== false) {
        $editHotelOccurrences[] = $file;
        echo "  📄 $file: function editHotel encontrada\n";
    }
}

if (count($editHotelOccurrences) > 1) {
    $issues[] = [
        'type' => 'duplicate_js_functions',
        'function' => 'editHotel',
        'files' => $editHotelOccurrences,
        'error' => 'Múltiples definiciones de editHotel pueden causar conflictos'
    ];
    echo "  ⚠️  Función editHotel duplicada en " . count($editHotelOccurrences) . " archivos\n";
}

// 3. Aplicar correcciones
echo "\n🔧 3. Aplicando correcciones...\n";

foreach ($issues as $issue) {
    switch ($issue['type']) {
        case 'css_typo':
            // Corregir visually-hidden -> visually-hidden
            $content = file_get_contents($issue['file']);
            $newContent = str_replace('visually-hidden', 'visually-hidden', $content);
            
            if ($newContent !== $content) {
                file_put_contents($issue['file'], $newContent);
                $fixes[] = "✅ Corregido CSS typo en " . basename($issue['file']);
                echo "  ✅ Corregido CSS en " . basename($issue['file']) . "\n";
            }
            break;
            
        case 'duplicate_js_functions':
            // Para funciones duplicadas, mantener solo la implementación principal
            // y comentar las demás con una nota
            
            $mainFile = null;
            $secondaryFiles = [];
            
            // Identificar archivo principal (admin_main.php tiene prioridad)
            foreach ($issue['files'] as $file) {
                if (strpos($file, 'admin_main.php') !== false) {
                    $mainFile = $file;
                } else {
                    $secondaryFiles[] = $file;
                }
            }
            
            // Si no hay admin_main.php, usar el primero como principal
            if (!$mainFile && !empty($issue['files'])) {
                $mainFile = $issue['files'][0];
                $secondaryFiles = array_slice($issue['files'], 1);
            }
            
            echo "  📋 Función '{$issue['function']}' principal en: " . basename($mainFile) . "\n";
            
            // Comentar funciones duplicadas en archivos secundarios
            foreach ($secondaryFiles as $file) {
                $content = file_get_contents($file);
                
                // Buscar y comentar la función
                $pattern = '/function\s+' . preg_quote($issue['function']) . '\s*\([^}]+\}/s';
                $replacement = "// FUNCIÓN DUPLICADA - COMENTADA PARA EVITAR CONFLICTOS\n    // " . 
                              "function {$issue['function']} - Ver implementación en " . basename($mainFile) . "\n    /*\n$0\n    */";
                
                $newContent = preg_replace($pattern, $replacement, $content, 1);
                
                if ($newContent !== $content) {
                    file_put_contents($file . '.dedup', $newContent);
                    $fixes[] = "✅ Comentada función duplicada {$issue['function']} en " . basename($file);
                    echo "  ✅ Función {$issue['function']} comentada en " . basename($file) . " (guardado como .dedup)\n";
                } else {
                    echo "  ⚠️  No se pudo comentar automáticamente en " . basename($file) . "\n";
                }
            }
            break;
    }
}

// 4. Generar reporte
echo "\n📊 REPORTE FINAL:\n";
echo "  🔍 Problemas encontrados: " . count($issues) . "\n";
echo "  🔧 Correcciones aplicadas: " . count($fixes) . "\n\n";

if (!empty($fixes)) {
    echo "✅ CORRECCIONES APLICADAS:\n";
    foreach ($fixes as $fix) {
        echo "  $fix\n";
    }
}

if (!empty($issues)) {
    echo "\n📋 PROBLEMAS DETECTADOS:\n";
    foreach ($issues as $issue) {
        echo "  🔸 {$issue['type']}: {$issue['error']}\n";
    }
}

// 5. Recomendaciones
echo "\n💡 RECOMENDACIONES:\n";
echo "1. Revisar archivos .dedup antes de aplicar cambios\n";
echo "2. Consolidar funciones duplicadas en un módulo central\n";
echo "3. Usar namespace o módulos para evitar colisiones\n";
echo "4. Implementar linting automático para detectar duplicados\n";
echo "5. Considerar usar un build system para concatenar JS\n";

echo "\n🎉 Corrección de errores frontend completada!\n";
?>