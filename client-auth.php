<?php
session_start();

// Configuración de base de datos
require_once 'env-loader.php';

$host = $_ENV['DB_HOST'] ?? 'localhost';
$dbname = $_ENV['DB_NAME'] ?? 'soporteia_bookingkavia';
$username = $_ENV['DB_USER'] ?? 'root';
$password = $_ENV['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    die("Error de conexión a la base de datos");
}

// Procesar login
if ($_POST['action'] === 'login') {
    $email = trim($_POST['email']);
    $password_input = $_POST['password'];
    
    if (empty($email) || empty($password_input)) {
        header('Location: client-login.php?error=credentials&email=' . urlencode($email));
        exit();
    }
    
    try {
        // Buscar usuario con su nivel de cliente
        $stmt = $pdo->prepare("
            SELECT cu.*, cl.name as level_name, cl.display_name, cl.features, cl.modules
            FROM client_users cu 
            JOIN client_levels cl ON cu.client_level_id = cl.id 
            WHERE cu.email = ? AND cu.active = 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            header('Location: client-login.php?error=credentials&email=' . urlencode($email));
            exit();
        }
        
        // Verificar contraseña
        if (!password_verify($password_input, $user['password'])) {
            header('Location: client-login.php?error=credentials&email=' . urlencode($email));
            exit();
        }
        
        // Verificar suscripción activa
        $subscription_active = false;
        
        if ($user['subscription_status'] === 'active' || $user['subscription_status'] === 'trial') {
            if (!$user['subscription_end'] || strtotime($user['subscription_end']) > time()) {
                $subscription_active = true;
            }
        }
        
        if (!$subscription_active) {
            header('Location: client-subscription-expired.php?email=' . urlencode($email));
            exit();
        }
        
        // Obtener hoteles del usuario
        $stmt = $pdo->prepare("
            SELECT h.id, h.nombre_hotel, cha.permissions
            FROM client_hotel_access cha
            JOIN hoteles h ON cha.hotel_id = h.id
            WHERE cha.client_user_id = ? AND cha.active = 1
        ");
        $stmt->execute([$user['id']]);
        $user_hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Actualizar último login
        $stmt = $pdo->prepare("UPDATE client_users SET last_login_at = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Crear sesión
        $_SESSION['client_user'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'company_name' => $user['company_name'],
            'level_name' => $user['level_name'],
            'level_display' => $user['display_name'],
            'features' => json_decode($user['features'], true),
            'modules' => json_decode($user['modules'], true),
            'subscription_status' => $user['subscription_status'],
            'hotels' => $user_hotels,
            'login_time' => time()
        ];
        
        header('Location: client-login.php?success=1');
        exit();
        
    } catch(PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        header('Location: client-login.php?error=database&email=' . urlencode($email));
        exit();
    }
}

// Logout
elseif ($_POST['action'] === 'logout') {
    session_destroy();
    header('Location: client-login.php');
    exit();
}

// Acción no válida
else {
    header('Location: client-login.php');
    exit();
}
?>