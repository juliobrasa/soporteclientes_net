<?php
require_once 'env-loader.php';

echo "🔍 VERIFICACIÓN DE ESTRUCTURA TABLA 'hoteles'\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    
    $stmt = $pdo->query("DESCRIBE hoteles");
    $columns = $stmt->fetchAll();
    
    echo "📋 COLUMNAS EN TABLA 'hoteles':\n";
    foreach ($columns as $col) {
        echo "  • {$col['Field']} ({$col['Type']}) - NULL: {$col['Null']} - Default: {$col['Default']}\n";
    }
    
    echo "\n🔍 BÚSQUEDA DE COLUMNA 'destino':\n";
    $destinoFound = false;
    $destinoAlternatives = [];
    
    foreach ($columns as $col) {
        if (strtolower($col['Field']) === 'destino') {
            $destinoFound = true;
            echo "✅ Encontrada: {$col['Field']}\n";
        }
        
        // Buscar alternativas similares
        if (stripos($col['Field'], 'dest') !== false || 
            stripos($col['Field'], 'ubicacion') !== false ||
            stripos($col['Field'], 'ciudad') !== false ||
            stripos($col['Field'], 'lugar') !== false) {
            $destinoAlternatives[] = $col['Field'];
        }
    }
    
    if (!$destinoFound) {
        echo "❌ Columna 'destino' NO ENCONTRADA\n";
        
        if (!empty($destinoAlternatives)) {
            echo "\n💡 POSIBLES ALTERNATIVAS:\n";
            foreach ($destinoAlternatives as $alt) {
                echo "  • {$alt}\n";
            }
        }
        
        echo "\n📊 MUESTRA DE DATOS (primeros 3 hoteles):\n";
        $stmt = $pdo->query("SELECT * FROM hoteles LIMIT 3");
        $samples = $stmt->fetchAll();
        
        foreach ($samples as $i => $hotel) {
            echo "\n🏨 Hotel " . ($i + 1) . ":\n";
            foreach ($hotel as $field => $value) {
                if (is_numeric($field)) continue; // Skip numeric indexes
                $displayValue = is_null($value) ? 'NULL' : (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value);
                echo "    {$field}: {$displayValue}\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>