<?php
/**
 * Análisis de esquemas de reviews existentes para unificación
 */

require_once 'env-loader.php';

echo "📊 ANÁLISIS DE ESQUEMAS DE REVIEWS\n";
echo str_repeat("=", 50) . "\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    echo "✅ Conexión establecida\n\n";
    
    // 1. Listar todas las tablas de reviews
    echo "🔍 1. TABLAS DE REVIEWS EXISTENTES:\n";
    $stmt = $pdo->query("SHOW TABLES LIKE '%review%'");
    $reviewTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($reviewTables)) {
        echo "❌ No se encontraron tablas de reviews\n";
        echo "Buscando tablas alternativas...\n";
        $stmt = $pdo->query("SHOW TABLES");
        $allTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tablas disponibles: " . implode(', ', $allTables) . "\n\n";
    } else {
        foreach ($reviewTables as $table) {
            echo "  - $table\n";
        }
    }
    
    echo "\n📋 2. ESQUEMAS DE CADA TABLA:\n";
    foreach ($reviewTables as $table) {
        echo "\n🔸 TABLA: $table\n";
        echo str_repeat("-", 40) . "\n";
        
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            
            echo "Columnas encontradas:\n";
            foreach ($columns as $col) {
                echo sprintf("  %-25s %-20s %s %s\n", 
                    $col['Field'], 
                    $col['Type'], 
                    $col['Null'], 
                    $col['Key'] ? "[$col[Key]]" : ""
                );
            }
            
            // Contar registros
            $stmt = $pdo->query("SELECT COUNT(*) FROM `$table`");
            $count = $stmt->fetchColumn();
            echo "Total registros: $count\n";
            
            // Mostrar sample si hay datos
            if ($count > 0) {
                echo "\nSample record:\n";
                $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 1");
                $sample = $stmt->fetch(PDO::FETCH_ASSOC);
                foreach ($sample as $key => $value) {
                    $displayValue = strlen($value) > 100 ? substr($value, 0, 100) . '...' : $value;
                    echo "  $key = " . json_encode($displayValue) . "\n";
                }
            }
            
        } catch (Exception $e) {
            echo "❌ Error analizando tabla $table: " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n🔧 3. ANÁLISIS DE INCONSISTENCIAS:\n";
    echo str_repeat("-", 40) . "\n";
    
    $inconsistencies = [];
    $allColumns = [];
    
    // Recopilar todas las columnas
    foreach ($reviewTables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            $allColumns[$table] = array_column($columns, 'Field');
        } catch (Exception $e) {
            continue;
        }
    }
    
    // Buscar variaciones de nombres similares
    $commonPatterns = [
        'platform' => ['platform', 'source_platform', 'platform_name'],
        'user_name' => ['user_name', 'reviewer_name', 'author_name', 'username'],
        'review_text' => ['review_text', 'text', 'comment', 'content'],
        'liked_text' => ['liked_text', 'positive_text', 'pros'],
        'disliked_text' => ['disliked_text', 'negative_text', 'cons'],
        'rating' => ['rating', 'score', 'normalized_rating'],
        'property_response' => ['property_response', 'response_from_owner', 'hotel_response']
    ];
    
    foreach ($commonPatterns as $concept => $variants) {
        echo "\n🔸 $concept:\n";
        foreach ($reviewTables as $table) {
            $found = array_intersect($variants, $allColumns[$table]);
            if (!empty($found)) {
                echo "  $table: " . implode(', ', $found) . "\n";
            }
        }
    }
    
    echo "\n💡 4. RECOMENDACIONES:\n";
    echo str_repeat("-", 40) . "\n";
    echo "- Crear tabla 'reviews_unified' como esquema final\n";
    echo "- Mantener columnas principales y alias para compatibilidad\n";
    echo "- Crear vistas para tablas legacy\n";
    echo "- Implementar adapters para migración gradual\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>