<?php
echo "<h1>🔍 Verificar Estructura de Tablas</h1>";

include 'admin-config.php';

$pdo = getDBConnection();
if ($pdo) {
    echo "<h2>Columnas de la tabla 'ai_providers':</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE ai_providers");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        
        echo "<h3>Datos reales en ai_providers:</h3>";
        $stmt = $pdo->query("SELECT * FROM ai_providers LIMIT 3");
        $providers = $stmt->fetchAll();
        echo "<pre>" . print_r($providers, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>Columnas de la tabla 'hoteles':</h2>";
    try {
        $stmt = $pdo->query("DESCRIBE hoteles");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
        }
        
        echo "<h3>Datos reales en hoteles:</h3>";
        $stmt = $pdo->query("SELECT * FROM hoteles LIMIT 3");
        $hotels = $stmt->fetchAll();
        echo "<pre>" . print_r($hotels, true) . "</pre>";
        
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}
?>