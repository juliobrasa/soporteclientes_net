<?php
/**
 * Cargador Seguro de Variables de Entorno
 * Versión corregida sin credenciales hardcodeadas
 */

// Prevenir acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    http_response_code(403);
    exit('Acceso directo no permitido');
}

class EnvironmentLoader 
{
    private static $loaded = false;
    private static $config = [];
    private static $envPaths = [
        '.env.local',
        '.env', 
        '.env.production'
    ];
    
    /**
     * Cargar variables de entorno de forma segura
     */
    public static function load($forceReload = false) 
    {
        if (self::$loaded && !$forceReload) {
            return self::$config;
        }
        
        // Buscar archivo .env
        $envFile = self::findEnvFile();
        
        if ($envFile) {
            self::parseEnvFile($envFile);
        }
        
        // Cargar valores por defecto si no están definidos
        self::loadDefaults();
        
        self::$loaded = true;
        self::logEnvironmentLoad($envFile);
        
        return self::$config;
    }
    
    /**
     * Buscar archivo de entorno disponible
     */
    private static function findEnvFile() 
    {
        foreach (self::$envPaths as $path) {
            $fullPath = __DIR__ . '/' . $path;
            if (file_exists($fullPath) && is_readable($fullPath)) {
                return $fullPath;
            }
        }
        
        return null;
    }
    
    /**
     * Parsear archivo .env
     */
    private static function parseEnvFile($file) 
    {
        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Saltar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parsear línea KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas
                $value = trim($value, '"\'');
                
                self::$config[$key] = $value;
                
                // También establecer en $_ENV para compatibilidad
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    /**
     * Cargar valores por defecto seguros
     */
    private static function loadDefaults() 
    {
        $defaults = [
            // Base de datos (valores ejemplo - DEBEN ser configurados en .env)
            'DB_HOST' => 'localhost',
            'DB_PORT' => '3306', 
            'DB_DATABASE' => 'soporteclientes_db',
            'DB_USERNAME' => 'db_user',
            'DB_PASSWORD' => '', // DEBE ser configurado en .env
            
            // APIs (valores ejemplo - DEBEN ser configurados en .env)
            'APIFY_API_TOKEN' => '', // DEBE ser configurado en .env
            'OPENAI_API_KEY' => '', // DEBE ser configurado en .env
            
            // Configuración de aplicación
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://soporteclientes.net',
            'APP_NAME' => 'Soporte Clientes',
            
            // Configuración de cache y session
            'CACHE_DRIVER' => 'file',
            'SESSION_LIFETIME' => '120',
            'SESSION_DRIVER' => 'file',
            
            // Configuración de logging
            'LOG_CHANNEL' => 'stack',
            'LOG_LEVEL' => 'error',
            
            // Configuración de correo
            'MAIL_DRIVER' => 'smtp',
            'MAIL_HOST' => 'localhost',
            'MAIL_PORT' => '587',
            'MAIL_USERNAME' => '',
            'MAIL_PASSWORD' => '',
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_FROM_ADDRESS' => 'noreply@soporteclientes.net',
            'MAIL_FROM_NAME' => 'Soporte Clientes',
            
            // Configuraciones de seguridad
            'HASH_DRIVER' => 'bcrypt',
            'CIPHER' => 'AES-256-CBC',
            
            // Rate limiting
            'RATE_LIMIT_MAX' => '60',
            'RATE_LIMIT_WINDOW' => '60',
            
            // Timeouts
            'HTTP_TIMEOUT' => '30',
            'DB_TIMEOUT' => '30'
        ];
        
        foreach ($defaults as $key => $value) {
            if (!isset(self::$config[$key]) || self::$config[$key] === '') {
                self::$config[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
    
    /**
     * Obtener valor de configuración
     */
    public static function get($key, $default = null) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Verificar si una configuración existe
     */
    public static function has($key) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        return isset(self::$config[$key]);
    }
    
    /**
     * Obtener todas las configuraciones (sin passwords)
     */
    public static function all($hideSensitive = true) 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        if (!$hideSensitive) {
            return self::$config;
        }
        
        $safe = self::$config;
        $sensitiveKeys = [
            'DB_PASSWORD', 'APIFY_API_TOKEN', 'OPENAI_API_KEY', 
            'MAIL_PASSWORD', 'JWT_SECRET', 'APP_KEY'
        ];
        
        foreach ($sensitiveKeys as $key) {
            if (isset($safe[$key])) {
                $safe[$key] = '***HIDDEN***';
            }
        }
        
        return $safe;
    }
    
    /**
     * Validar configuración crítica
     */
    public static function validateCriticalConfig() 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        $errors = [];
        $required = [
            'DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'
        ];
        
        foreach ($required as $key) {
            if (empty(self::$config[$key])) {
                $errors[] = "Variable requerida no configurada: $key";
            }
        }
        
        // Validaciones específicas
        if (self::get('APP_DEBUG', 'false') === 'true' && self::get('APP_ENV') === 'production') {
            $errors[] = "DEBUG no debe estar activo en producción";
        }
        
        return $errors;
    }
    
    /**
     * Crear conexión PDO usando configuración cargada
     */
    public static function createDatabaseConnection() 
    {
        if (!self::$loaded) {
            self::load();
        }
        
        $host = self::get('DB_HOST');
        $port = self::get('DB_PORT');
        $database = self::get('DB_DATABASE');
        $username = self::get('DB_USERNAME');
        $password = self::get('DB_PASSWORD');
        
        $dsn = "mysql:host=$host;port=$port;dbname=$database;charset=utf8mb4";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => intval(self::get('DB_TIMEOUT', 30))
        ];
        
        try {
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("L Error de conexión DB: " . $e->getMessage());
            throw new Exception("Error de conexión a la base de datos");
        }
    }
    
    /**
     * Log del proceso de carga
     */
    private static function logEnvironmentLoad($envFile) 
    {
        $status = $envFile ? "desde $envFile" : "usando valores por defecto";
        $configCount = count(self::$config);
        
        error_log(" Entorno cargado correctamente: $configCount variables $status");
        
        // Verificar configuración crítica
        $errors = self::validateCriticalConfig();
        if (!empty($errors)) {
            error_log("  Errores de configuración: " . implode(', ', $errors));
        }
    }
}

/**
 * FUNCIONES DE COMPATIBILIDAD LEGACY
 */

function createDatabaseConnection() {
    return EnvironmentLoader::createDatabaseConnection();
}

function getenv_safe($key, $default = null) {
    return EnvironmentLoader::get($key, $default);
}

// Auto-cargar al incluir el archivo
EnvironmentLoader::load();

?>