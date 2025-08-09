<?php
/**
 * Script de diagnóstico para verificar por qué no aparecen hoteles en extractor
 */

session_start();
if (!isset($_SESSION['admin_logged'])) {
    die('Requiere autenticación admin');
}

require_once 'admin-config.php';

echo "🔍 DIAGNÓSTICO DE HOTELES EN EXTRACTOR\n";
echo str_repeat("=", 50) . "\n\n";

$pdo = getDBConnection();
if (!$pdo) {
    die("❌ Error: No se pudo conectar a la base de datos\n");
}

echo "✅ Conexión a BD establecida\n\n";

try {
    // 1. Verificar estructura de tabla hoteles
    echo "📋 1. ESTRUCTURA DE TABLA HOTELES:\n";
    $stmt = $pdo->query("DESCRIBE hoteles");
    $columns = $stmt->fetchAll();
    foreach ($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Key']}\n";
    }
    echo "\n";

    // 2. Contar total de hoteles
    echo "📊 2. ESTADÍSTICAS DE HOTELES:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hoteles");
    $total = $stmt->fetch()['total'];
    echo "  Total hoteles: $total\n";

    // 3. Verificar campo 'activo'
    if (in_array('activo', array_column($columns, 'Field'))) {
        $stmt = $pdo->query("SELECT COUNT(*) as activos FROM hoteles WHERE activo = 1");
        $activos = $stmt->fetch()['activos'];
        echo "  Hoteles activos (activo=1): $activos\n";
        
        $stmt = $pdo->query("SELECT COUNT(*) as inactivos FROM hoteles WHERE activo = 0");
        $inactivos = $stmt->fetch()['inactivos'];
        echo "  Hoteles inactivos (activo=0): $inactivos\n";
    } else {
        echo "  ⚠️ Campo 'activo' no existe en tabla hoteles\n";
    }
    echo "\n";

    // 4. Mostrar algunos hoteles de ejemplo
    echo "📋 3. HOTELES DE EJEMPLO:\n";
    $stmt = $pdo->query("SELECT id, nombre_hotel, activo, created_at FROM hoteles LIMIT 5");
    $examples = $stmt->fetchAll();
    
    if (empty($examples)) {
        echo "  ❌ No se encontraron hoteles en la base de datos\n";
    } else {
        foreach ($examples as $hotel) {
            $activo = isset($hotel['activo']) ? ($hotel['activo'] ? 'SÍ' : 'NO') : 'N/A';
            echo "  - ID: {$hotel['id']} | {$hotel['nombre_hotel']} | Activo: $activo | Creado: {$hotel['created_at']}\n";
        }
    }
    echo "\n";

    // 5. Probar query del extractor
    echo "📋 4. TESTING QUERY DEL EXTRACTOR:\n";
    try {
        $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles WHERE activo = 1 ORDER BY nombre_hotel ASC LIMIT 10");
        $hoteles = $stmt->fetchAll();
        
        if (empty($hoteles)) {
            echo "  ❌ Query del extractor no devuelve resultados\n";
            
            // Intentar sin filtro activo
            echo "  🔍 Probando sin filtro 'activo':\n";
            $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles ORDER BY nombre_hotel ASC LIMIT 10");
            $hotelesAll = $stmt->fetchAll();
            
            if (empty($hotelesAll)) {
                echo "    ❌ No hay hoteles en la tabla\n";
            } else {
                echo "    ✅ Encontrados " . count($hotelesAll) . " hoteles sin filtro:\n";
                foreach ($hotelesAll as $h) {
                    echo "      - {$h['id']}: {$h['nombre_hotel']}\n";
                }
            }
        } else {
            echo "  ✅ Query del extractor funciona correctamente:\n";
            foreach ($hoteles as $h) {
                echo "    - {$h['id']}: {$h['nombre_hotel']}\n";
            }
        }
    } catch (PDOException $e) {
        echo "  ❌ Error ejecutando query: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 6. Verificar si hay trabajos de extracción
    echo "📋 5. TRABAJOS DE EXTRACCIÓN:\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM extraction_jobs");
    $totalJobs = $stmt->fetch()['total'];
    echo "  Total trabajos de extracción: $totalJobs\n";
    
    if ($totalJobs > 0) {
        $stmt = $pdo->query("SELECT ej.id, ej.status, h.nombre_hotel, ej.created_at FROM extraction_jobs ej LEFT JOIN hoteles h ON ej.hotel_id = h.id ORDER BY ej.created_at DESC LIMIT 3");
        $jobs = $stmt->fetchAll();
        echo "  Últimos trabajos:\n";
        foreach ($jobs as $job) {
            echo "    - Job {$job['id']}: {$job['nombre_hotel']} | Status: {$job['status']} | {$job['created_at']}\n";
        }
    }
    echo "\n";

    // 7. Verificar función getActiveHotels
    echo "📋 6. FUNCIÓN getActiveHotels():\n";
    if (function_exists('getActiveHotels')) {
        $hoteles = getActiveHotels();
        echo "  Función devuelve: " . count($hoteles) . " hoteles\n";
        if (!empty($hoteles)) {
            echo "  Primeros hoteles:\n";
            foreach (array_slice($hoteles, 0, 3) as $h) {
                echo "    - {$h['id']}: {$h['nombre_hotel']}\n";
            }
        }
    } else {
        echo "  ❌ Función getActiveHotels() no existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error durante diagnóstico: " . $e->getMessage() . "\n";
}

echo "\n💡 SOLUCIONES SUGERIDAS:\n";
echo "1. Si no hay hoteles: Ejecutar script para crear datos de prueba\n";
echo "2. Si campo 'activo' no existe: Agregar columna o modificar query\n";
echo "3. Si hay hoteles inactivos: Activarlos manualmente\n";
echo "4. Verificar permisos de BD y conexión\n\n";

echo "🔧 COMANDOS ÚTILES:\n";
echo "-- Ver todos los hoteles:\n";
echo "SELECT id, nombre_hotel, activo FROM hoteles LIMIT 10;\n\n";
echo "-- Activar todos los hoteles:\n";
echo "UPDATE hoteles SET activo = 1;\n\n";
echo "-- Crear hotel de prueba:\n";
echo "INSERT INTO hoteles (nombre_hotel, activo) VALUES ('Hotel Test', 1);\n\n";

?>