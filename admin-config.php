<?php
// admin-config.php - VERSIÓN SEGURA CORREGIDA

// SEGURIDAD: Cargar configuración desde variables de entorno
require_once 'env-loader.php';

// Función para conectar a la base de datos de forma segura
function getDBConnection() {
    try {
        // Usar EnvironmentLoader para obtener credenciales seguras
        return EnvironmentLoader::createDatabaseConnection();
    } catch (Exception $e) {
        error_log("Error de conexión BD (admin-config): " . $e->getMessage());
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

// Función para verificar autenticación de admin
function verifyAdminAuth() {
    session_start();
    if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
        http_response_code(401);
        if (headers_sent()) {
            echo '<script>alert("Sesión expirada. Redirigiendo..."); window.location="/admin-login.php";</script>';
        } else {
            header('Location: /admin-login.php');
        }
        exit;
    }
}

// Función para log de seguridad
function logSecurityEvent($event, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'event' => $event,
        'details' => $details,
        'session_id' => session_id()
    ];
    
    error_log("SECURITY_EVENT: " . json_encode($logData));
}

?>