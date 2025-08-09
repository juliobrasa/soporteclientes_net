<?php
/**
 * Script para corregir CSP en archivos administrativos
 */

$adminFiles = [
    'admin-place-ids.php',
    'admin-ai.php', 
    'admin-apis.php',
    'admin-logs.php',
    'admin-analytics.php',
    'admin-prompts.php',
    'admin-dashboard.php',
    'admin-hotels.php',
    'admin-tools.php',
    'admin-extraction-unified.php',
    'admin-extraction-portals.php'
];

$insertAfterPattern = '/include\s+[\'"]admin-config\.php[\'"];?/';
$insertCode = "\n// Aplicar CSP específico para páginas administrativas\nrequire_once 'csp-config.php';\nsetAdminCSP();\n";

$fixed = 0;
$skipped = 0;

echo "🔧 Corrigiendo CSP en archivos administrativos...\n\n";

foreach ($adminFiles as $file) {
    if (!file_exists($file)) {
        echo "⚠️  Archivo no encontrado: $file\n";
        $skipped++;
        continue;
    }
    
    $content = file_get_contents($file);
    
    // Verificar si ya tiene la configuración CSP
    if (strpos($content, 'setAdminCSP()') !== false) {
        echo "✅ Ya configurado: $file\n";
        $skipped++;
        continue;
    }
    
    // Buscar dónde insertar el código CSP
    if (preg_match($insertAfterPattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
        $insertPos = $matches[0][1] + strlen($matches[0][0]);
        
        // Insertar código CSP después de include admin-config.php
        $newContent = substr($content, 0, $insertPos) . $insertCode . substr($content, $insertPos);
        
        // Crear backup
        copy($file, $file . '.csp-backup');
        
        // Escribir archivo modificado
        file_put_contents($file, $newContent);
        
        echo "✅ Corregido: $file\n";
        $fixed++;
        
    } else {
        // Si no encuentra el patrón, agregar al inicio después del session_start
        if (preg_match('/session_start\(\);?\s*/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
            $insertPos = $matches[0][1] + strlen($matches[0][0]);
            
            $cspCode = "\n// Aplicar CSP específico para páginas administrativas\nrequire_once 'csp-config.php';\nsetAdminCSP();\n";
            $newContent = substr($content, 0, $insertPos) . $cspCode . substr($content, $insertPos);
            
            copy($file, $file . '.csp-backup');
            file_put_contents($file, $newContent);
            
            echo "✅ Corregido (después de session_start): $file\n";
            $fixed++;
        } else {
            echo "⚠️  No se pudo procesar: $file (patrón no encontrado)\n";
            $skipped++;
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "✅ Archivos corregidos: $fixed\n";
echo "⚠️  Archivos omitidos: $skipped\n";

// Verificar que csp-config.php existe
if (!file_exists('csp-config.php')) {
    echo "\n❌ ADVERTENCIA: csp-config.php no existe. Los archivos modificados pueden fallar.\n";
} else {
    echo "\n✅ csp-config.php encontrado correctamente\n";
}

echo "\n🔧 SIGUIENTE PASO: Recargar las páginas administrativas en el navegador\n";
echo "Los errores de CSP deberían estar resueltos.\n";

?>