<?php
/**
 * Diagnóstico simple de hoteles sin autenticación
 */

require_once 'env-loader.php';

echo "🔍 DIAGNÓSTICO SIMPLE DE HOTELES\n";
echo str_repeat("=", 40) . "\n\n";

try {
    $pdo = EnvironmentLoader::createDatabaseConnection();
    echo "✅ Conexión establecida\n\n";
    
    // Verificar tabla hoteles
    $stmt = $pdo->query("SHOW TABLES LIKE 'hoteles'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Tabla 'hoteles' no existe\n";
        exit(1);
    }
    echo "✅ Tabla 'hoteles' existe\n";
    
    // Verificar columnas
    $stmt = $pdo->query("DESCRIBE hoteles");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columnas: " . implode(', ', $columns) . "\n\n";
    
    // Contar total
    $stmt = $pdo->query("SELECT COUNT(*) FROM hoteles");
    $total = $stmt->fetchColumn();
    echo "Total hoteles: $total\n";
    
    if ($total == 0) {
        echo "\n❌ NO HAY HOTELES EN LA BASE DE DATOS\n";
        echo "🔧 Necesitas crear algunos hoteles para poder usar el extractor\n\n";
        
        echo "Ejecuta este SQL para crear hoteles de prueba:\n";
        echo "```sql\n";
        echo "INSERT INTO hoteles (nombre_hotel, hoja_destino, activo, created_at) VALUES\n";
        echo "('Hotel Xcaret México', 'Playa del Carmen, Riviera Maya', 1, NOW()),\n";
        echo "('Grand Velas Riviera Maya', 'Playa del Carmen, México', 1, NOW()),\n";
        echo "('Rosewood Mayakoba', 'Riviera Maya, México', 1, NOW());\n";
        echo "```\n";
        
        exit(1);
    }
    
    // Verificar campo activo
    if (in_array('activo', $columns)) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM hoteles WHERE activo = 1");
        $activos = $stmt->fetchColumn();
        echo "Hoteles activos: $activos\n";
        
        if ($activos == 0) {
            echo "\n❌ TODOS LOS HOTELES ESTÁN INACTIVOS\n";
            echo "🔧 Ejecuta: UPDATE hoteles SET activo = 1;\n";
        }
    } else {
        echo "⚠️ Campo 'activo' no existe - todos los hoteles serán visibles\n";
    }
    
    // Mostrar algunos hoteles
    echo "\n📋 HOTELES ENCONTRADOS:\n";
    $query = in_array('activo', $columns) ? 
        "SELECT id, nombre_hotel, activo FROM hoteles LIMIT 5" : 
        "SELECT id, nombre_hotel FROM hoteles LIMIT 5";
        
    $stmt = $pdo->query($query);
    $hoteles = $stmt->fetchAll();
    
    foreach ($hoteles as $hotel) {
        $activo_text = isset($hotel['activo']) ? ($hotel['activo'] ? ' ✅' : ' ❌') : '';
        echo "  - {$hotel['id']}: {$hotel['nombre_hotel']}$activo_text\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>