<?php
// Script para crear usuario administrador
require_once 'env-loader.php';

// Configuración de base de datos (usando credenciales del admin)
$host = "soporteclientes.net";
$dbname = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar nivel Enterprise (nivel 1 - máximo acceso)
    $stmt = $pdo->query("SELECT * FROM client_levels WHERE name = 'enterprise' ORDER BY id ASC LIMIT 1");
    $enterpriseLevel = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$enterpriseLevel) {
        echo "Error: Nivel Enterprise no encontrado\n";
        exit;
    }
    
    echo "Nivel encontrado: " . $enterpriseLevel['display_name'] . " (ID: " . $enterpriseLevel['id'] . ")\n";
    
    // Verificar si el usuario ya existe
    $stmt = $pdo->prepare("SELECT id FROM client_users WHERE email = ?");
    $stmt->execute(['admin@soporteclientes.net']);
    $existingUser = $stmt->fetch();
    
    if ($existingUser) {
        echo "Usuario ya existe con ID: " . $existingUser['id'] . "\n";
        echo "Actualizando contraseña...\n";
        
        $stmt = $pdo->prepare("UPDATE client_users SET password = ? WHERE email = ?");
        $stmt->execute([password_hash('admin123', PASSWORD_DEFAULT), 'admin@soporteclientes.net']);
        
        $userId = $existingUser['id'];
    } else {
        // Crear el usuario
        $stmt = $pdo->prepare("
            INSERT INTO client_users (
                name, email, phone, company_name, email_verified_at, password,
                client_level_id, active, preferences, subscription_start,
                subscription_end, subscription_status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $result = $stmt->execute([
            'Administrador Soporte',
            'admin@soporteclientes.net',
            '+52 998 000 0000', 
            'SoporteClientes Admin',
            password_hash('admin123', PASSWORD_DEFAULT),
            $enterpriseLevel['id'],
            1, // active
            '{"language":"es","timezone":"America/Mexico_City"}',
            date('Y-m-d H:i:s', strtotime('-30 days')), // subscription_start
            date('Y-m-d H:i:s', strtotime('+365 days')), // subscription_end
            'active'
        ]);
        
        $userId = $pdo->lastInsertId();
        echo "Usuario creado con ID: $userId\n";
    }
    
    // Obtener todos los hoteles disponibles
    $stmt = $pdo->query("SELECT id, nombre_hotel FROM hoteles ORDER BY id");
    $hoteles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Hoteles disponibles: " . count($hoteles) . "\n";
    
    // Asignar todos los hoteles al usuario con permisos completos
    foreach ($hoteles as $hotel) {
        // Verificar si ya existe la relación
        $stmt = $pdo->prepare("SELECT id FROM client_hotel_access WHERE client_user_id = ? AND hotel_id = ?");
        $stmt->execute([$userId, $hotel['id']]);
        $existingAccess = $stmt->fetch();
        
        if (!$existingAccess) {
            $stmt = $pdo->prepare("
                INSERT INTO client_hotel_access (
                    client_user_id, hotel_id, permissions, active, created_at, updated_at
                ) VALUES (?, ?, ?, 1, NOW(), NOW())
            ");
            
            $permissions = json_encode([
                'view_reviews' => true,
                'export_reports' => true,
                'competitor_analysis' => true,
                'ai_responses' => true,
                'sentiment_analysis' => true,
                'full_access' => true
            ]);
            
            $stmt->execute([$userId, $hotel['id'], $permissions]);
            echo "- Acceso asignado al hotel: " . $hotel['nombre_hotel'] . " (ID: " . $hotel['id'] . ")\n";
        }
    }
    
    echo "\n✅ USUARIO CREADO EXITOSAMENTE\n";
    echo "Email: admin@soporteclientes.net\n";
    echo "Password: admin123\n";
    echo "Nivel: " . $enterpriseLevel['display_name'] . "\n";
    echo "Hoteles asignados: " . count($hoteles) . "\n";
    echo "Estado: Activo con suscripción válida por 1 año\n";
    
} catch(PDOException $e) {
    echo "Error de base de datos: " . $e->getMessage() . "\n";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>