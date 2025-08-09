<?php
/**
 * Script para crear nuevo usuario de BD usando credenciales actuales
 */

echo "🔐 CREACIÓN DE NUEVO USUARIO DE BD\n";
echo str_repeat("=", 40) . "\n\n";

// Usar credenciales antiguas temporalmente para crear el nuevo usuario
$oldCredentials = [
    'host' => 'soporteclientes.net',
    'dbname' => 'soporteia_bookingkavia', 
    'user' => 'soporteia_admin',
    'pass' => 'QCF8RhS*}.Oj0u(v',
    'port' => 3306
];

// Nuevas credenciales del script SQL
$newCredentials = [
    'user' => 'soporteia_sec20250809',
    'pass' => '4S#9i9ijdUGjBUYWqf4*5FJC',
    'dbname' => 'soporteia_bookingkavia'
];

try {
    echo "🔍 Conectando con credenciales actuales...\n";
    
    $dsn = "mysql:host={$oldCredentials['host']};port={$oldCredentials['port']};dbname={$oldCredentials['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $oldCredentials['user'], $oldCredentials['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Conectado con credenciales actuales\n\n";
    
    echo "🆕 Creando nuevo usuario...\n";
    
    // 1. Crear nuevo usuario
    $sql = "CREATE USER '{$newCredentials['user']}'@'%' IDENTIFIED BY '{$newCredentials['pass']}'";
    $pdo->exec($sql);
    echo "✅ Usuario creado: {$newCredentials['user']}\n";
    
    // 2. Otorgar permisos específicos
    $sql = "GRANT SELECT, INSERT, UPDATE, DELETE ON {$newCredentials['dbname']}.* TO '{$newCredentials['user']}'@'%'";
    $pdo->exec($sql);
    echo "✅ Permisos básicos otorgados\n";
    
    $sql = "GRANT CREATE, DROP, INDEX, ALTER ON {$newCredentials['dbname']}.* TO '{$newCredentials['user']}'@'%'";
    $pdo->exec($sql);
    echo "✅ Permisos de estructura otorgados\n";
    
    // 3. Aplicar cambios
    $pdo->exec("FLUSH PRIVILEGES");
    echo "✅ Privilegios aplicados\n";
    
    // 4. Verificar nuevo usuario
    $stmt = $pdo->query("SELECT User, Host FROM mysql.user WHERE User = '{$newCredentials['user']}'");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "✅ Verificación exitosa - Usuario encontrado en sistema:\n";
        foreach ($users as $user) {
            echo "   - {$user['User']}@{$user['Host']}\n";
        }
    }
    
    echo "\n🧪 Probando conexión con nuevas credenciales...\n";
    
    // Probar conexión con nuevas credenciales
    $newDsn = "mysql:host={$oldCredentials['host']};port={$oldCredentials['port']};dbname={$newCredentials['dbname']};charset=utf8mb4";
    $newPdo = new PDO($newDsn, $newCredentials['user'], $newCredentials['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "✅ Nueva conexión funciona correctamente\n\n";
    
    // Probar una query simple
    $stmt = $newPdo->query("SELECT COUNT(*) as total FROM hoteles");
    $count = $stmt->fetch()['total'];
    echo "🏨 Hoteles encontrados con nuevas credenciales: $count\n\n";
    
    echo "🎉 ROTACIÓN COMPLETADA EXITOSAMENTE\n";
    echo "Las nuevas credenciales están funcionando correctamente.\n";
    echo "Ahora puedes eliminar el usuario anterior si quieres:\n";
    echo "DROP USER 'soporteia_admin'@'%';\n";
    
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "already exists") !== false) {
        echo "ℹ️  El usuario ya existe, probando conexión...\n";
        
        try {
            $newDsn = "mysql:host={$oldCredentials['host']};port={$oldCredentials['port']};dbname={$newCredentials['dbname']};charset=utf8mb4";
            $newPdo = new PDO($newDsn, $newCredentials['user'], $newCredentials['pass'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);
            echo "✅ Usuario ya existe y funciona correctamente\n";
        } catch (PDOException $e2) {
            echo "❌ Usuario existe pero credenciales incorrectas: " . $e2->getMessage() . "\n";
        }
    } else {
        echo "❌ Error creando usuario: " . $e->getMessage() . "\n";
        echo "\n💡 Posibles soluciones:\n";
        echo "1. Verificar que las credenciales actuales son correctas\n";
        echo "2. Verificar permisos de administrador del usuario actual\n";  
        echo "3. Verificar conectividad a la base de datos\n";
    }
} catch (Exception $e) {
    echo "❌ Error general: " . $e->getMessage() . "\n";
}
?>