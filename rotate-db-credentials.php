<?php
/**
 * Script para rotación segura de credenciales de base de datos
 * 
 * IMPORTANTE: Ejecutar solo después de haber actualizado las credenciales en el panel del proveedor
 */

echo "🔐 ROTACIÓN DE CREDENCIALES DE BASE DE DATOS\n";
echo str_repeat("=", 55) . "\n\n";

class DatabaseCredentialRotator 
{
    private $envFile;
    private $backupDir;
    
    public function __construct() 
    {
        $this->envFile = __DIR__ . '/.env.local';
        $this->backupDir = __DIR__ . '/backup-credentials';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0700, true);
        }
    }
    
    public function rotateCredentials() 
    {
        echo "🔍 Analizando credenciales actuales...\n";
        
        // Leer configuración actual
        $currentConfig = $this->readCurrentConfig();
        $this->displayCurrentConfig($currentConfig);
        
        echo "\n⚠️  CREDENCIALES COMPROMETIDAS DETECTADAS:\n";
        echo "   - DB_PASS está en código fuente (api/config.php, admin-config.php)\n";
        echo "   - Credenciales visibles en archivos de respaldo\n";
        echo "   - Posible exposición en logs/historia de Git\n\n";
        
        // Generar nuevas credenciales
        $newCredentials = $this->generateNewCredentials();
        $this->displayNewCredentials($newCredentials);
        
        // Crear backup de configuración actual
        $this->backupCurrentConfig($currentConfig);
        
        // Generar script SQL para cambios en BD
        $this->generateSQLScript($currentConfig, $newCredentials);
        
        // Generar nuevo archivo .env.local
        $this->generateNewEnvFile($currentConfig, $newCredentials);
        
        // Proporcionar instrucciones
        $this->displayRotationInstructions();
    }
    
    private function readCurrentConfig() 
    {
        if (!file_exists($this->envFile)) {
            throw new Exception("Archivo .env.local no encontrado");
        }
        
        $config = [];
        $lines = file($this->envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                [$key, $value] = explode('=', $line, 2);
                $config[trim($key)] = trim($value);
            }
        }
        
        return $config;
    }
    
    private function displayCurrentConfig($config) 
    {
        $dbKeys = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_PORT'];
        
        echo "📋 Configuración actual:\n";
        foreach ($dbKeys as $key) {
            $value = $config[$key] ?? 'NO CONFIGURADO';
            if ($key === 'DB_PASS') {
                $value = str_repeat('*', 8) . substr($value, -4); // Ocultar mayoría
            }
            echo "   $key = $value\n";
        }
    }
    
    private function generateNewCredentials() 
    {
        // Generar password seguro
        $newPassword = $this->generateSecurePassword(24);
        
        // Generar nuevo usuario (opcional)
        $timestamp = date('Ymd');
        $newUser = "soporteia_sec$timestamp";
        
        return [
            'DB_USER' => $newUser,
            'DB_PASS' => $newPassword,
            'rotation_date' => date('Y-m-d H:i:s'),
            'reason' => 'Credential exposure in source code'
        ];
    }
    
    private function generateSecurePassword($length = 24) 
    {
        // Caracteres seguros (evitando confusos como 0, O, l, I)
        $chars = 'abcdefghijkmnopqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789@#$%&*+=';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $chars[random_int(0, strlen($chars) - 1)];
        }
        
        return $password;
    }
    
    private function displayNewCredentials($newCreds) 
    {
        echo "\n🆕 Nuevas credenciales generadas:\n";
        echo "   DB_USER = {$newCreds['DB_USER']}\n";
        echo "   DB_PASS = {$newCreds['DB_PASS']}\n";
        echo "   Fecha: {$newCreds['rotation_date']}\n";
    }
    
    private function backupCurrentConfig($config) 
    {
        $timestamp = date('Y-m-d_H-i-s');
        $backupFile = $this->backupDir . "/env-backup-$timestamp.json";
        
        $backupData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'reason' => 'Pre-rotation backup',
            'config' => $config
        ];
        
        file_put_contents($backupFile, json_encode($backupData, JSON_PRETTY_PRINT));
        
        // Proteger el archivo
        chmod($backupFile, 0600);
        
        echo "\n💾 Backup creado: $backupFile\n";
    }
    
    private function generateSQLScript($current, $new) 
    {
        $sqlFile = $this->backupDir . '/rotation-script-' . date('Y-m-d_H-i-s') . '.sql';
        
        $sql = "-- Script de rotación de credenciales BD\n";
        $sql .= "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- EJECUTAR COMO ROOT/ADMIN EN MYSQL\n\n";
        
        $sql .= "-- 1. Crear nuevo usuario con permisos limitados\n";
        $sql .= "CREATE USER '{$new['DB_USER']}'@'%' IDENTIFIED BY '{$new['DB_PASS']}';\n\n";
        
        $sql .= "-- 2. Otorgar permisos específicos (principio de menor privilegio)\n";
        $sql .= "GRANT SELECT, INSERT, UPDATE, DELETE ON {$current['DB_NAME']}.* TO '{$new['DB_USER']}'@'%';\n";
        $sql .= "GRANT CREATE, DROP, INDEX, ALTER ON {$current['DB_NAME']}.* TO '{$new['DB_USER']}'@'%';\n\n";
        
        $sql .= "-- 3. Aplicar cambios\n";
        $sql .= "FLUSH PRIVILEGES;\n\n";
        
        $sql .= "-- 4. Verificar nuevo usuario\n";
        $sql .= "SELECT User, Host FROM mysql.user WHERE User = '{$new['DB_USER']}';\n\n";
        
        $sql .= "-- 5. DESPUÉS de verificar que todo funciona, eliminar usuario anterior\n";
        $sql .= "-- DROP USER '{$current['DB_USER']}'@'%';\n";
        $sql .= "-- FLUSH PRIVILEGES;\n\n";
        
        file_put_contents($sqlFile, $sql);
        chmod($sqlFile, 0600);
        
        echo "📜 Script SQL generado: $sqlFile\n";
    }
    
    private function generateNewEnvFile($current, $new) 
    {
        $newEnvFile = $this->backupDir . '/.env.local.new';
        
        // Mantener configuración existente, solo actualizar credenciales DB
        $newConfig = array_merge($current, [
            'DB_USER' => $new['DB_USER'],
            'DB_PASS' => $new['DB_PASS']
        ]);
        
        // Agregar metadatos de rotación
        $content = "# Configuración Local - NO SUBIR A GITHUB\n";
        $content .= "# Credenciales rotadas: {$new['rotation_date']}\n";
        $content .= "# Motivo: {$new['reason']}\n\n";
        
        $content .= "# Base de datos\n";
        $content .= "DB_HOST={$newConfig['DB_HOST']}\n";
        $content .= "DB_NAME={$newConfig['DB_NAME']}\n";
        $content .= "DB_USER={$newConfig['DB_USER']}\n";
        $content .= "DB_PASS={$newConfig['DB_PASS']}\n";
        $content .= "DB_PORT={$newConfig['DB_PORT']}\n";
        $content .= "DB_CHARSET={$newConfig['DB_CHARSET']}\n\n";
        
        // Resto de configuración
        $content .= "# Aplicación\n";
        foreach ($newConfig as $key => $value) {
            if (!str_starts_with($key, 'DB_')) {
                $content .= "$key=$value\n";
            }
        }
        
        file_put_contents($newEnvFile, $content);
        chmod($newEnvFile, 0600);
        
        echo "🆕 Nuevo archivo .env generado: $newEnvFile\n";
    }
    
    private function displayRotationInstructions() 
    {
        echo "\n📋 INSTRUCCIONES DE ROTACIÓN:\n";
        echo str_repeat("-", 50) . "\n";
        echo "1. 🛠️  EJECUTAR SCRIPT SQL:\n";
        echo "   - Conectar a MySQL como root/admin\n";
        echo "   - Ejecutar el script SQL generado\n";
        echo "   - Verificar que el nuevo usuario funciona\n\n";
        
        echo "2. 🔧 ACTUALIZAR CONFIGURACIÓN:\n";
        echo "   - Mover .env.local.new a .env.local\n";
        echo "   - Verificar que la aplicación conecta correctamente\n";
        echo "   - Probar funcionalidades principales\n\n";
        
        echo "3. 🧹 LIMPIAR CÓDIGO FUENTE:\n";
        echo "   - Eliminar credenciales hardcoded de:\n";
        echo "     * api/config.php (si aún existe)\n";
        echo "     * admin-config.php\n";
        echo "     * Cualquier otro archivo con credenciales\n\n";
        
        echo "4. 🗑️  DESPUÉS DE VERIFICAR TODO:\n";
        echo "   - Ejecutar DROP USER para eliminar usuario anterior\n";
        echo "   - Limpiar archivos de backup antiguos\n";
        echo "   - Rotar logs que puedan contener credenciales\n\n";
        
        echo "5. 🔍 VERIFICAR SEGURIDAD:\n";
        echo "   - Buscar credenciales en historial Git\n";
        echo "   - Verificar permisos de archivos sensibles\n";
        echo "   - Auditar accesos recientes a BD\n\n";
        
        echo "⚠️  IMPORTANTE:\n";
        echo "- No ejecutar en horario de alta demanda\n";
        echo "- Tener plan de rollback preparado\n";
        echo "- Monitorear logs durante transición\n";
        echo "- Notificar al equipo sobre el cambio\n\n";
    }
}

// Ejecutar rotación si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    try {
        $rotator = new DatabaseCredentialRotator();
        $rotator->rotateCredentials();
        
        echo "✅ Rotación de credenciales preparada exitosamente\n";
        echo "🔧 Revisar archivos en backup-credentials/ y seguir instrucciones\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error durante rotación: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>