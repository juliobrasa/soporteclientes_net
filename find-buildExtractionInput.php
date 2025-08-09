<?php
/**
 * Buscar función buildExtractionInput en todo el código
 */

echo "🔍 BUSCANDO FUNCIÓN buildExtractionInput\n";
echo str_repeat("=", 50) . "\n\n";

// Directorios a buscar
$searchDirs = [
    __DIR__,
    __DIR__ . '/api',
    __DIR__ . '/usuarios/admin',
    __DIR__ . '/usuarios/admin/modules',
    __DIR__ . '/usuarios/admin/assets/js'
];

$patterns = [
    'buildExtractionInput',
    'buildExtraction',
    'extractionInput',
    'enableGoogleMaps',
    'enableTripadvisor',
    'enableBooking',
    'platforms.*enable',
    'enable.*platform'
];

$foundFiles = [];

foreach ($searchDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    echo "📁 Buscando en: $dir\n";
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if (!$file->isFile()) continue;
        
        $filename = $file->getPathname();
        $extension = strtolower($file->getExtension());
        
        // Solo archivos PHP, JS, HTML
        if (!in_array($extension, ['php', 'js', 'html', 'htm'])) continue;
        
        $content = file_get_contents($filename);
        if ($content === false) continue;
        
        // Buscar cada patrón
        foreach ($patterns as $pattern) {
            if (preg_match("/$pattern/i", $content, $matches)) {
                if (!isset($foundFiles[$filename])) {
                    $foundFiles[$filename] = [];
                }
                $foundFiles[$filename][] = $pattern;
            }
        }
    }
}

echo "\n📊 RESULTADOS:\n";

if (empty($foundFiles)) {
    echo "❌ No se encontró buildExtractionInput en ningún archivo\n";
    echo "\n💡 Posible explicación:\n";
    echo "- La función puede estar en un archivo JavaScript externo\n";
    echo "- Podría estar minificada o compilada\n"; 
    echo "- Tal vez se llama de forma diferente\n";
    echo "- Puede que aún no esté implementada (bug reportado)\n\n";
    
    echo "🔍 Buscando patrones relacionados...\n";
    
    // Buscar otros patrones relacionados
    $relatedPatterns = [
        'function.*extract',
        'extract.*function',
        'apify.*build',
        'build.*apify',
        'scraper.*config',
        'config.*scraper',
        'platform.*select',
        'select.*platform'
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
            
            if (!in_array($extension, ['php', 'js'])) continue;
            
            $content = file_get_contents($filename);
            if ($content === false) continue;
            
            foreach ($relatedPatterns as $pattern) {
                if (preg_match("/$pattern/i", $content, $matches)) {
                    echo "  🔸 $filename: $pattern\n";
                    
                    // Mostrar el contexto
                    $lines = explode("\n", $content);
                    foreach ($lines as $lineNum => $line) {
                        if (preg_match("/$pattern/i", $line)) {
                            $start = max(0, $lineNum - 2);
                            $end = min(count($lines) - 1, $lineNum + 2);
                            
                            echo "    Líneas " . ($start + 1) . "-" . ($end + 1) . ":\n";
                            for ($i = $start; $i <= $end; $i++) {
                                $marker = ($i === $lineNum) ? '>>>' : '   ';
                                echo "    $marker " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
                            }
                            echo "\n";
                            break;
                        }
                    }
                }
            }
        }
    }
    
} else {
    echo "✅ Encontrados " . count($foundFiles) . " archivos con coincidencias:\n\n";
    
    foreach ($foundFiles as $filename => $patterns) {
        echo "📄 $filename\n";
        echo "   Patrones: " . implode(', ', $patterns) . "\n";
        
        // Mostrar contexto de cada coincidencia
        $content = file_get_contents($filename);
        $lines = explode("\n", $content);
        
        foreach ($patterns as $pattern) {
            foreach ($lines as $lineNum => $line) {
                if (preg_match("/$pattern/i", $line)) {
                    echo "   Línea " . ($lineNum + 1) . ": " . trim($line) . "\n";
                }
            }
        }
        echo "\n";
    }
}

echo "\n🎯 RECOMENDACIÓN:\n";
echo "Si buildExtractionInput no existe, debe crearse para:\n";
echo "1. Mapear platforms seleccionadas a flags enableX\n";
echo "2. Evitar scrapear más plataformas de las pedidas\n";
echo "3. Reducir costes innecesarios\n";
?>