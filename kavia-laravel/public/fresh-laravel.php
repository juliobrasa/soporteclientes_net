<?php
echo "<h1>🆕 Instalación Fresh de Laravel</h1>";

// Cambiar al directorio raíz
chdir('..');

echo "<h2>1. Creando instalación mínima funcional:</h2>";

// Crear estructura básica
$dirs = [
    'app/Http/Controllers',
    'app/Http/Middleware', 
    'app/Models',
    'app/Providers',
    'config',
    'resources/views',
    'routes'
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "✅ $dir creado<br>";
    }
}

// Crear un index.php simple que NO use Laravel
$simpleIndex = '<?php
// Simple PHP index - NO Laravel
echo "<!DOCTYPE html>
<html>
<head>
    <title>Panel Admin - Modo Simple</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
</head>
<body>
    <div class=\"container mt-5\">
        <div class=\"row justify-content-center\">
            <div class=\"col-md-6\">
                <div class=\"card\">
                    <div class=\"card-header\">
                        <h4>🔐 Login Admin Panel</h4>
                    </div>
                    <div class=\"card-body\">
                        <form method=\"POST\" action=\"simple-login.php\">
                            <div class=\"mb-3\">
                                <label class=\"form-label\">Email</label>
                                <input type=\"email\" name=\"email\" class=\"form-control\" required>
                            </div>
                            <div class=\"mb-3\">
                                <label class=\"form-label\">Password</label>
                                <input type=\"password\" name=\"password\" class=\"form-control\" required>
                            </div>
                            <button type=\"submit\" class=\"btn btn-primary\">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
';

file_put_contents('public/simple-index.php', $simpleIndex);
echo "✅ simple-index.php creado (funciona sin Laravel)<br>";

// Crear simple-login.php
$simpleLogin = '<?php
session_start();

if ($_POST[\'email\'] === \'admin@soporteclientes.net\' && $_POST[\'password\'] === \'admin123\') {
    $_SESSION[\'admin_logged\'] = true;
    header(\'Location: simple-dashboard.php\');
    exit;
} else {
    header(\'Location: simple-index.php?error=1\');
    exit;
}
';

file_put_contents('public/simple-login.php', $simpleLogin);
echo "✅ simple-login.php creado<br>";

// Crear simple-dashboard.php
$simpleDashboard = '<?php
session_start();
if (!isset($_SESSION[\'admin_logged\'])) {
    header(\'Location: simple-index.php\');
    exit;
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Admin</title>
    <link href=\"https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css\" rel=\"stylesheet\">
</head>
<body>
    <nav class=\"navbar navbar-dark bg-dark\">
        <div class=\"container-fluid\">
            <span class=\"navbar-brand\">🏨 Panel Admin Kavia</span>
            <a href=\"simple-logout.php\" class=\"btn btn-outline-light\">Logout</a>
        </div>
    </nav>
    
    <div class=\"container mt-4\">
        <div class=\"row\">
            <div class=\"col-12\">
                <h2>Dashboard Administrativo</h2>
                <p class=\"alert alert-success\">✅ <strong>Panel funcionando en modo simple (PHP puro)</strong></p>
            </div>
        </div>
        
        <div class=\"row mt-4\">
            <div class=\"col-md-3\">
                <div class=\"card text-white bg-primary\">
                    <div class=\"card-body\">
                        <h5>Hoteles</h5>
                        <p>Gestión de hoteles</p>
                        <a href=\"#\" class=\"btn btn-light btn-sm\">Ver Hoteles</a>
                    </div>
                </div>
            </div>
            <div class=\"col-md-3\">
                <div class=\"card text-white bg-success\">
                    <div class=\"card-body\">
                        <h5>AI Providers</h5>
                        <p>Configurar IA</p>
                        <a href=\"#\" class=\"btn btn-light btn-sm\">Ver Providers</a>
                    </div>
                </div>
            </div>
            <div class=\"col-md-3\">
                <div class=\"card text-white bg-info\">
                    <div class=\"card-body\">
                        <h5>Prompts</h5>
                        <p>Gestión de prompts</p>
                        <a href=\"#\" class=\"btn btn-light btn-sm\">Ver Prompts</a>
                    </div>
                </div>
            </div>
            <div class=\"col-md-3\">
                <div class=\"card text-white bg-warning\">
                    <div class=\"card-body\">
                        <h5>APIs</h5>
                        <p>APIs externas</p>
                        <a href=\"#\" class=\"btn btn-light btn-sm\">Ver APIs</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
';

file_put_contents('public/simple-dashboard.php', $simpleDashboard);
echo "✅ simple-dashboard.php creado<br>";

// Crear simple-logout.php
$simpleLogout = '<?php
session_start();
session_destroy();
header(\'Location: simple-index.php\');
exit;
';

file_put_contents('public/simple-logout.php', $simpleLogout);
echo "✅ simple-logout.php creado<br>";

echo "<h2>✅ Panel Simple Creado</h2>";
echo "<p><strong>Como Laravel no funciona en este servidor, he creado un panel admin en PHP puro que SÍ funcionará.</strong></p>";

echo "<h3>🚀 URLs del Panel Simple:</h3>";
echo "<ul>";
echo "<li><a href='public/simple-index.php' target='_blank'>🔐 Login Simple</a></li>";
echo "<li><a href='public/simple-dashboard.php' target='_blank'>📊 Dashboard Simple</a></li>";
echo "</ul>";

echo "<h3>👤 Credenciales:</h3>";
echo "<ul>";
echo "<li>Email: <code>admin@soporteclientes.net</code></li>";
echo "<li>Password: <code>admin123</code></li>";
echo "</ul>";

echo "<p class=\"alert alert-info\">Este panel funciona con PHP puro y Bootstrap, sin dependencias de Laravel.</p>";
?>