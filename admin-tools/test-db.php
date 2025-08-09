<?php
echo "<h1>🔍 Test de Conexión a Base de Datos</h1>";

// Incluir configuración
include 'admin-config.php';

echo "<h2>1. Configuración:</h2>";
echo "Host: " . $db_config['host'] . "<br>";
echo "Database: " . $db_config['database'] . "<br>";
echo "Username: " . $db_config['username'] . "<br>";
echo "Password: " . (strlen($db_config['password']) > 0 ? '****' : 'VACÍO') . "<br>";

echo "<h2>2. Intentando conexión:</h2>";
$pdo = getDBConnection();

if ($pdo) {
    echo "✅ Conexión exitosa<br>";
    
    echo "<h2>3. Verificando tablas:</h2>";
    $tables = ['hoteles', 'ai_providers', 'extraction_jobs', 'system_logs'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $result = $stmt->fetch();
            echo "✅ Tabla '$table': " . $result['count'] . " registros<br>";
        } catch (PDOException $e) {
            echo "❌ Error en tabla '$table': " . $e->getMessage() . "<br>";
        }
    }
    
    echo "<h2>4. Probando funciones específicas:</h2>";
    
    // Test hotel stats
    $hotel_stats = getHotelStats();
    echo "Hoteles - Total: " . ($hotel_stats['total'] ?? 'ERROR') . ", Activos: " . ($hotel_stats['active'] ?? 'ERROR') . "<br>";
    
    // Test AI stats  
    $ai_stats = getAIStats();
    echo "AI Providers - Total: " . ($ai_stats['total'] ?? 'ERROR') . ", Activos: " . ($ai_stats['active'] ?? 'ERROR') . "<br>";
    
    // Test extractions
    $extractions = getTodayExtractions();
    echo "Extracciones hoy: " . $extractions . "<br>";
    
    // Test activity
    $activity = getRecentActivity();
    echo "Registros de actividad: " . count($activity) . "<br>";
    
} else {
    echo "❌ Error de conexión<br>";
    
    echo "<h2>3. Intentando conexión manual:</h2>";
    try {
        $pdo_manual = new PDO(
            "mysql:host=soporteclientes.net;dbname=soporteia_bookingkavia;charset=utf8mb4",
            "soporteia_admin",
            "QCF8RhS*}.Oj0u(v"
        );
        echo "✅ Conexión manual exitosa<br>";
        
        $stmt = $pdo_manual->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "Tablas disponibles: " . implode(', ', $tables) . "<br>";
        
    } catch (PDOException $e) {
        echo "❌ Error conexión manual: " . $e->getMessage() . "<br>";
    }
}

echo "<hr>";
echo "<p>Test completado. <a href='admin-dashboard.php'>Volver al Dashboard</a></p>";
?>