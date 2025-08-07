<?php
// Configuración de base de datos
$db_config = [
    'host' => 'soporteclientes.net',
    'database' => 'soporteia_bookingkavia',
    'username' => 'soporteia_admin',
    'password' => 'QCF8RhS*}.Oj0u(v'
];

// Función para conectar a la base de datos
function getDBConnection() {
    global $db_config;
    
    try {
        $pdo = new PDO(
            "mysql:host={$db_config['host']};dbname={$db_config['database']};charset=utf8mb4",
            $db_config['username'],
            $db_config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        error_log("Error de conexión BD: " . $e->getMessage());
        return null;
    }
}

// Función para obtener estadísticas de hoteles
function getHotelStats() {
    $pdo = getDBConnection();
    if (!$pdo) return ['total' => 0, 'active' => 0];
    
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN activo = 1 THEN 1 END) as active
            FROM hoteles");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error obteniendo stats hoteles: " . $e->getMessage());
        return ['total' => 0, 'active' => 0];
    }
}

// Función para obtener estadísticas de AI providers
function getAIStats() {
    $pdo = getDBConnection();
    if (!$pdo) return ['total' => 0, 'active' => 0];
    
    try {
        $stmt = $pdo->query("SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN is_active = 1 THEN 1 END) as active
            FROM ai_providers");
        return $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error obteniendo stats AI: " . $e->getMessage());
        return ['total' => 0, 'active' => 0];
    }
}

// Función para obtener actividad reciente
function getRecentActivity() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    try {
        $stmt = $pdo->query("SELECT 
            sl.created_at,
            h.nombre_hotel,
            sl.action,
            sl.level
            FROM system_logs sl
            LEFT JOIN hoteles h ON sl.hotel_id = h.id
            ORDER BY sl.created_at DESC 
            LIMIT 5");
        return $stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error obteniendo actividad: " . $e->getMessage());
        return [];
    }
}

// Función para obtener extracciones de hoy
function getTodayExtractions() {
    $pdo = getDBConnection();
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count 
            FROM extraction_jobs 
            WHERE DATE(created_at) = CURDATE()");
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error obteniendo extracciones hoy: " . $e->getMessage());
        return 0;
    }
}
?>