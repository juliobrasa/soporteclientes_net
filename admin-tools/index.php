<?php
/**
 * Índice protegido para herramientas de administración
 */
session_start();

if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
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
        <div class="container">
            <div class="icon">🔒</div>
            <h1>Acceso Restringido</h1>
            <p>Esta área contiene herramientas de administración sensibles.</p>
            <p>Se requiere autenticación de administrador para continuar.</p>
            <p><a href="/admin-login.php">Iniciar Sesión</a></p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Lista de herramientas disponibles para administradores autenticados
$tools = glob(__DIR__ . '/*.php');
sort($tools);

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
    <div class="container">
        <h1>🛠️ Herramientas de Administración</h1>
        
        <div class="warning">
            <strong>⚠️ Advertencia:</strong> Estas herramientas son para administradores únicamente. 
            Usar con precaución en entornos de producción.
        </div>
        
        <h2>Scripts disponibles:</h2>
        <?php foreach ($tools as $tool): 
            $name = basename($tool);
            if ($name === 'index.php') continue;
        ?>
            <div class="tool">
                <strong><?= htmlspecialchars($name) ?></strong>
                - <a href="<?= htmlspecialchars($name) ?>" target="_blank">Ejecutar</a>
            </div>
        <?php endforeach; ?>
        
        <p><a href="/">← Volver al panel principal</a></p>
    </div>
</body>
</html>
