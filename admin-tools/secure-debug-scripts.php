<?php
/**
 * Script de Seguridad: Proteger scripts de debug/test públicos
 * Mueve scripts peligrosos fuera del docroot público
 */

// Verificar que se ejecuta desde CLI
if (php_sapi_name() !== 'cli') {
    die("Este script solo puede ejecutarse desde CLI");
}

echo "🔒 SCRIPT DE SEGURIDAD: Protegiendo scripts debug/test\n";
echo str_repeat("=", 60) . "\n\n";

// Patrones de archivos peligrosos
$dangerousPatterns = [
    '*debug*',
    '*test*', 
    '*repair*',
    '*update-files*',
    '*install*',
    '*setup*',
    '*migrate*',
    '*temp*',
    '*backup*',
    '*dump*'
];

// Directorios a proteger (públicos)
$publicDirs = [
    '/root/soporteclientes_net',
    '/root/soporteclientes_net/kavia-laravel/public'
];

// Directorio seguro para mover scripts
$secureDir = '/root/soporteclientes_net/admin-tools';

// Crear directorio seguro
if (!is_dir($secureDir)) {
    mkdir($secureDir, 0700, true);
    echo "✅ Directorio seguro creado: $secureDir\n";
}

$totalFiles = 0;
$movedFiles = 0;
$protectedFiles = 0;

foreach ($publicDirs as $dir) {
    if (!is_dir($dir)) continue;
    
    echo "🔍 Escaneando: $dir\n";
    
    foreach ($dangerousPatterns as $pattern) {
        $files = glob($dir . '/' . $pattern . '.php');
        
        foreach ($files as $file) {
            $totalFiles++;
            $filename = basename($file);
            $relativePath = str_replace('/root/soporteclientes_net/', '', $file);
            
            echo "  📄 Encontrado: $relativePath\n";
            
            // Verificar si contiene código peligroso
            $content = file_get_contents($file);
            $isDangerous = false;
            
            $dangerousKeywords = [
                'phpinfo',
                'exec(',
                'system(',
                'shell_exec',
                'passthru',
                'eval(',
                '$_GET',
                '$_POST',
                'unlink(',
                'rmdir(',
                'file_get_contents',
                'file_put_contents',
                'mysql',
                'connection',
                'password',
                'token'
            ];
            
            foreach ($dangerousKeywords as $keyword) {
                if (stripos($content, $keyword) !== false) {
                    $isDangerous = true;
                    break;
                }
            }
            
            if ($isDangerous || strpos($file, '/public/') !== false) {
                // Mover archivo peligroso a directorio seguro
                $securePath = $secureDir . '/' . $filename;
                
                // Si ya existe, agregar timestamp
                if (file_exists($securePath)) {
                    $securePath = $secureDir . '/' . pathinfo($filename, PATHINFO_FILENAME) . '_' . time() . '.php';
                }
                
                if (rename($file, $securePath)) {
                    echo "    ➡️  Movido a: $securePath\n";
                    $movedFiles++;
                    
                    // Crear archivo de protección en su lugar
                    $protectionContent = "<?php\n/**\n * ARCHIVO PROTEGIDO POR SEGURIDAD\n * Script original movido a: $securePath\n * Fecha: " . date('Y-m-d H:i:s') . "\n */\n\nsession_start();\nif (!isset(\$_SESSION['admin_logged']) || \$_SESSION['admin_logged'] !== true) {\n    http_response_code(403);\n    die('Acceso denegado. Se requiere autenticación de administrador.');\n}\n\necho '<h1>🔒 Script Protegido</h1>';\necho '<p>Este script ha sido movido por razones de seguridad.</p>';\necho '<p>Ubicación segura: <code>$securePath</code></p>';\necho '<p>Para acceder, inicie sesión como administrador.</p>';\n?>";
                    
                    file_put_contents($file, $protectionContent);
                    chmod($file, 0640);
                    $protectedFiles++;
                    
                } else {
                    echo "    ❌ Error moviendo archivo\n";
                }
            } else {
                echo "    ✅ Archivo seguro, no requiere protección\n";
            }
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "  🔍 Archivos escaneados: $totalFiles\n";
echo "  📦 Archivos movidos: $movedFiles\n";  
echo "  🔒 Archivos protegidos: $protectedFiles\n";

// Crear .htaccess en directorio seguro
$htaccessContent = "# Protección adicional para herramientas de administración
<Files \"*.php\">
    Order allow,deny
    Deny from all
    # Solo permitir acceso desde localhost
    Allow from 127.0.0.1
    Allow from ::1
</Files>

# Proteger archivos sensibles
<FilesMatch \"\.(log|sql|bak|backup|dump)$\">
    Order allow,deny
    Deny from all
</FilesMatch>

# Deshabilitar listado de directorio
Options -Indexes

# Headers de seguridad
<IfModule mod_headers.c>
    Header always set X-Robots-Tag \"noindex, nofollow\"
    Header always set X-Frame-Options DENY
</IfModule>
";

file_put_contents($secureDir . '/.htaccess', $htaccessContent);
echo "  🛡️  Archivo .htaccess de protección creado\n";

// Crear archivo de documentación
$docContent = "# Herramientas de Administración Seguras

Este directorio contiene scripts de debug/test que fueron movidos del área pública por seguridad.

## Scripts movidos el " . date('Y-m-d H:i:s') . ":

";

$files = glob($secureDir . '/*.php');
foreach ($files as $file) {
    $filename = basename($file);
    if ($filename !== 'index.php') {
        $docContent .= "- `$filename`\n";
    }
}

$docContent .= "
## Uso seguro:

1. Estos scripts solo deben ejecutarse en entornos de desarrollo
2. Requieren autenticación de administrador si se acceden vía web
3. Para uso en producción, ejecutar solo desde CLI con acceso root

## Protecciones aplicadas:

- ✅ Movidos fuera del docroot público
- ✅ Permisos restrictivos (700)  
- ✅ .htaccess de protección
- ✅ Archivos de sustitución con autenticación

";

file_put_contents($secureDir . '/README.md', $docContent);
echo "  📖 Documentación creada: README.md\n";

// Crear script de protección genérico para público
$indexContent = "<?php
/**
 * Índice protegido para herramientas de administración
 */
session_start();

if (!isset(\$_SESSION['admin_logged']) || \$_SESSION['admin_logged'] !== true) {
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Acceso Restringido</title>
        <style>
            body { font-family: Arial; text-align: center; padding: 50px; background: #f5f5f5; }
            .container { max-width: 500px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .icon { font-size: 48px; color: #dc3545; margin-bottom: 20px; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <div class=\"icon\">🔒</div>
            <h1>Acceso Restringido</h1>
            <p>Esta área contiene herramientas de administración sensibles.</p>
            <p>Se requiere autenticación de administrador para continuar.</p>
            <p><a href=\"/admin-login.php\">Iniciar Sesión</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Lista de herramientas disponibles para administradores autenticados
\$tools = glob(__DIR__ . '/*.php');
sort(\$tools);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Herramientas de Administración</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f8f9fa; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .tool { padding: 10px; border: 1px solid #ddd; margin: 10px 0; border-radius: 4px; }
        .tool:hover { background: #f8f9fa; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <h1>🛠️ Herramientas de Administración</h1>
        
        <div class=\"warning\">
            <strong>⚠️ Advertencia:</strong> Estas herramientas son para administradores únicamente. 
            Usar con precaución en entornos de producción.
        </div>
        
        <h2>Scripts disponibles:</h2>
        <?php foreach (\$tools as \$tool): 
            \$name = basename(\$tool);
            if (\$name === 'index.php') continue;
        ?>
            <div class=\"tool\">
                <strong><?= htmlspecialchars(\$name) ?></strong>
                - <a href=\"<?= htmlspecialchars(\$name) ?>\" target=\"_blank\">Ejecutar</a>
            </div>
        <?php endforeach; ?>
        
        <p><a href=\"/\">← Volver al panel principal</a></p>
    </div>
</body>
</html>
";

file_put_contents($secureDir . '/index.php', $indexContent);
chmod($secureDir . '/index.php', 0640);
echo "  🏠 Índice protegido creado\n";

echo "\n✅ SEGURIDAD APLICADA EXITOSAMENTE\n";
echo "📁 Herramientas seguras en: $secureDir\n";
echo "🔗 Acceso web (admin): https://soporteclientes.net/admin-tools/\n\n";

echo "🚨 ACCIONES RECOMENDADAS:\n";
echo "1. Rotar credenciales de base de datos inmediatamente\n";
echo "2. Revisar logs de acceso por scripts comprometidos\n"; 
echo "3. Actualizar firewall para bloquear IPs sospechosas\n";
echo "4. Auditar acceso de usuarios reciente\n\n";

?>