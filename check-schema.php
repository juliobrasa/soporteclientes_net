<?php
require_once 'env-loader.php';

$pdo = EnvironmentLoader::createDatabaseConnection();
$stmt = $pdo->query("DESCRIBE reviews");
$columns = $stmt->fetchAll();

echo "Columnas en tabla reviews:\n";
foreach ($columns as $col) {
    echo "- {$col['Field']} ({$col['Type']})\n";
}
?>