<?php
/**
 * Test simple de la API de prompts
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug POST data
echo "=== DEBUG POST DATA ===\n";
$_POST = [
    'action' => 'getPrompts',
    'page' => '1',
    'limit' => '10'
];

$_REQUEST = $_POST; // API uses $_REQUEST

echo "POST data set: " . json_encode($_POST) . "\n";
echo "REQUEST data: " . json_encode($_REQUEST) . "\n";

// Capturar output
ob_start();

// Ejecutar la API
$api_response = null;
try {
    // Simular llamada directa
    include_once 'admin_api.php';
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

$content = ob_get_clean();

echo "=== CONTENT CAPTURED ===\n";
echo $content;
echo "\n=== END CONTENT ===\n";

// Test directo de la función
try {
    echo "\n=== TEST DIRECTO ===\n";
    
    // Include database configuration
    include_once 'config/config.php';
    
    // Test conexión DB
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Conexión DB exitosa\n";
    
    // Test consulta prompts
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM prompts");
    $stmt->execute();
    $count = $stmt->fetch();
    
    echo "✅ Total prompts en BD: " . $count['total'] . "\n";
    
    // Test estructura tabla
    $stmt = $pdo->prepare("DESCRIBE prompts");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "✅ Columnas en tabla prompts:\n";
    foreach ($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Error DB: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
?>