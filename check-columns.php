<?php
echo "<h1>🔍 Verificar Estructura de Todas las Tablas</h1>";

include 'admin-config.php';

$pdo = getDBConnection();
if ($pdo) {
    $tables = ['ai_providers', 'hoteles', 'prompts', 'external_apis', 'system_logs'];
    
    foreach ($tables as $table) {
        echo "<h2>Tabla '$table':</h2>";
        try {
            // Mostrar estructura
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll();
            echo "<h4>Columnas:</h4>";
            foreach ($columns as $col) {
                echo "- " . $col['Field'] . " (" . $col['Type'] . ")<br>";
            }
            
            // Mostrar datos
            $stmt = $pdo->query("SELECT * FROM $table LIMIT 5");
            $data = $stmt->fetchAll();
            echo "<h4>Datos (primeros 5 registros):</h4>";
            if (empty($data)) {
                echo "<p><em>No hay datos en esta tabla</em></p>";
            } else {
                echo "<pre>" . print_r($data, true) . "</pre>";
            }
            
        } catch (PDOException $e) {
            echo "Error en tabla $table: " . $e->getMessage() . "<br>";
        }
        echo "<hr>";
    }
}
?>