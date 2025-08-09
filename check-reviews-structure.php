<?php
/**
 * Verificar estructura de tabla reviews
 */

try {
    $host = "soporteclientes.net";
    $dbname = "soporteia_bookingkavia";
    $username = "soporteia_admin";
    $password = "QCF8RhS*}.Oj0u(v";
    
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    echo "📊 ESTRUCTURA DE TABLA REVIEWS:\n\n";
    
    $stmt = $pdo->query("DESCRIBE reviews");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']}: {$column['Type']}\n";
    }
    
    echo "\n📝 MUESTRA DE DATOS:\n";
    
    $sampleStmt = $pdo->query("SELECT * FROM reviews LIMIT 2");
    $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($samples as $i => $sample) {
        echo "\nReseña " . ($i + 1) . ":\n";
        foreach ($sample as $field => $value) {
            $displayValue = is_string($value) ? substr($value, 0, 50) . "..." : $value;
            echo "   {$field}: {$displayValue}\n";
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
?>