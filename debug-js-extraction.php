<?php
session_start();
if (!isset($_SESSION['admin_logged'])) {
    die('Requiere autenticación admin');
}

echo "🔍 DEBUG JAVASCRIPT ADMIN EXTRACTION\n";
echo str_repeat("=", 50) . "\n\n";

echo "Session ID: '" . session_id() . "'\n";
echo "Session ID length: " . strlen(session_id()) . "\n";
echo "Session ID tiene caracteres especiales: " . (preg_match('/[^a-zA-Z0-9]/', session_id()) ? 'SÍ' : 'NO') . "\n\n";

// Simular la línea problemática
$sessionId = session_id();
echo "Línea JavaScript problemática:\n";
echo "'X-Admin-Session': '$sessionId',\n\n";

// Verificar si el session_id tiene comillas o caracteres que puedan romper JavaScript
if (strpos($sessionId, "'") !== false || strpos($sessionId, '"') !== false) {
    echo "❌ PROBLEMA: Session ID contiene comillas\n";
} else {
    echo "✅ Session ID no contiene comillas problemáticas\n";
}

// Mostrar el output literal que se generaría
echo "\nOutput literal para JavaScript:\n";
echo "X-Admin-Session': '" . addslashes($sessionId) . "'\n";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Debug JS</title>
</head>
<body>
    <h1>Prueba JavaScript</h1>
    <button onclick="testFunction()">Probar</button>
    
    <script>
    console.log('🧪 Iniciando prueba JavaScript');
    
    // Simular la línea problemática exacta
    const headers = {
        'Content-Type': 'application/json',
        'X-Admin-Session': '<?php echo session_id(); ?>',
        'X-Requested-With': 'XMLHttpRequest'
    };
    
    console.log('✅ Headers:', headers);
    
    function testFunction() {
        console.log('✅ Función de prueba funciona');
        alert('JavaScript funciona correctamente');
    }
    
    // Probar funciones que fallan
    function startExtraction() {
        console.log('✅ startExtraction definida');
    }
    
    function refreshJobs() {
        console.log('✅ refreshJobs definida');
    }
    
    console.log('🎯 Script cargado completamente');
    </script>
</body>
</html>