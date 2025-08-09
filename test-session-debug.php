<?php
/**
 * Debug de sesiones para verificar si se mantienen entre páginas
 */

session_start();

echo "🔍 DEBUG DE SESIÓN\n";
echo str_repeat("=", 40) . "\n\n";

echo "📋 INFORMACIÓN DE SESIÓN:\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . session_status() . " (1=disabled, 2=none, 3=active)\n";
echo "Session Name: " . session_name() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Cookie Params: " . print_r(session_get_cookie_params(), true) . "\n";

echo "📋 DATOS DE SESIÓN:\n";
if (empty($_SESSION)) {
    echo "❌ Sesión vacía\n";
} else {
    foreach ($_SESSION as $key => $value) {
        if ($key === 'admin_logged') {
            echo "✅ $key = " . ($value ? 'true' : 'false') . "\n";
        } else {
            echo "   $key = " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
}

echo "\n📋 COOKIES:\n";
if (empty($_COOKIE)) {
    echo "❌ No hay cookies\n";
} else {
    foreach ($_COOKIE as $name => $value) {
        if (strpos($name, 'PHPSESSID') !== false) {
            echo "🍪 $name = $value\n";
        } else {
            echo "   $name = " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
        }
    }
}

echo "\n📋 HEADERS:\n";
$headers = getallheaders();
if ($headers) {
    foreach ($headers as $name => $value) {
        if (stripos($name, 'cookie') !== false || stripos($name, 'session') !== false) {
            echo "📡 $name: $value\n";
        }
    }
}

echo "\n🔧 ACCIONES DISPONIBLES:\n";
echo "1. Para iniciar sesión admin: php -r \"session_start(); \$_SESSION['admin_logged'] = true; session_write_close(); echo 'Admin logged in\\n';\"\n";
echo "2. Para limpiar sesión: php -r \"session_start(); session_destroy(); echo 'Session cleared\\n';\"\n";

// Test de autenticación igual que api-extraction.php
echo "\n🧪 TEST DE AUTENTICACIÓN:\n";
$isAuthenticated = isset($_SESSION['admin_logged']) && $_SESSION['admin_logged'] === true;
if ($isAuthenticated) {
    echo "✅ AUTENTICADO - api-extraction.php debería permitir acceso\n";
} else {
    echo "❌ NO AUTENTICADO - api-extraction.php devolverá error 401/403\n";
}

if (isset($_GET['login'])) {
    echo "\n🔑 INICIANDO SESIÓN ADMIN...\n";
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_email'] = 'test@admin.com';
    echo "✅ Sesión admin iniciada\n";
    echo "Recarga la página para ver el cambio\n";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug Session</title>
</head>
<body>
    <h1>Debug de Sesión</h1>
    <pre><?php echo ob_get_contents(); ?></pre>
    
    <hr>
    <h2>Acciones</h2>
    <a href="?login=1">🔑 Iniciar Sesión Admin</a> | 
    <a href="test-session-debug.php">🔄 Recargar</a> |
    <a href="/admin-login.php">🏠 Login Real</a>
    
    <hr>
    <h2>Test AJAX</h2>
    <button onclick="testAPI()">Probar api-extraction.php</button>
    <div id="result"></div>
    
    <script>
    async function testAPI() {
        const result = document.getElementById('result');
        result.innerHTML = '🔄 Probando...';
        
        try {
            const response = await fetch('/api-extraction.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    hotel_id: 6,  // Corregido: singular no plural
                    max_reviews: 10,
                    platforms: ['booking'],
                    languages: ['en', 'es']
                })
            });
            
            const text = await response.text();
            result.innerHTML = `<h3>Status: ${response.status}</h3><pre>${text}</pre>`;
            
        } catch (error) {
            result.innerHTML = `❌ Error: ${error.message}`;
        }
    }
    </script>
</body>
</html>