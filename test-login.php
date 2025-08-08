<?php
// Archivo de prueba simple para debuggear el error 500
echo "Test 1: PHP funciona<br>";

try {
    // Test 2: Incluir env-loader
    require_once 'env-loader.php';
    echo "Test 2: env-loader.php incluido<br>";
    
    // Test 3: Mostrar variables de entorno
    echo "Test 3: Variables de entorno:<br>";
    echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_USER: " . ($_ENV['DB_USER'] ?? 'NO DEFINIDO') . "<br>";
    echo "DB_PASS: " . (isset($_ENV['DB_PASS']) ? '[DEFINIDO]' : 'NO DEFINIDO') . "<br>";
    
    // Test 4: Conexión a base de datos
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'soporteia_bookingkavia';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    echo "Test 4: Intentando conectar a la base de datos...<br>";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Test 5: Conexión exitosa!<br>";
    
    // Test 6: Verificar tablas
    $stmt = $pdo->query("SHOW TABLES LIKE 'client_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Test 6: Tablas encontradas: " . implode(', ', $tables) . "<br>";
    
} catch(Exception $e) {
    echo "ERROR: " . $e->getMessage() . "<br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
}

echo "<br><a href='client-login.php'>Ir a client-login.php</a>";
?>