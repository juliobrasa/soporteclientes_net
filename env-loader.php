<?php
/**
 * Environment Loader - Carga variables de entorno desde múltiples fuentes
 * 
 * Busca y carga configuración desde:
 * 1. Variables de entorno del sistema
 * 2. Archivo .env en raíz del proyecto
 * 3. Archivo .env.local (si existe)
 * 4. Valores por defecto seguros
 */

class EnvLoader 
{
    private static $loaded = false;
    
    public static function load($envPath = null) 
    {
        if (self::$loaded) {
            return;
        }
        
        // Ruta del archivo .env
        $envPath = $envPath ?: __DIR__ . '/.env';
        $envLocalPath = __DIR__ . '/.env.local';
        
        // Cargar .env principal
        if (file_exists($envPath)) {
            self::loadEnvFile($envPath);
        }
        
        // Cargar .env.local (sobrescribe valores)
        if (file_exists($envLocalPath)) {
            self::loadEnvFile($envLocalPath);
        }
        
        // Establecer valores por defecto si no existen
        self::setDefaults();
        
        self::$loaded = true;
    }
    
    private static function loadEnvFile($filePath) 
    {
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // Ignorar comentarios
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Procesar líneas KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                
                $key = trim($key);
                $value = trim($value);
                
                // Remover comillas si existen
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                // Solo establecer si no existe en $_ENV
                if (!isset($_ENV[$key])) {
                    $_ENV[$key] = $value;
                    putenv("$key=$value");
                }
            }
        }
    }
    
    private static function setDefaults() 
    {
        $defaults = [
            // Base de datos - usar valores de .env o .env.local
            'DB_HOST' => 'localhost',
            'DB_NAME' => 'reviews_database',
            'DB_USER' => 'reviews_user',
            'DB_PASS' => '',
            'DB_PORT' => '3306',
            'DB_CHARSET' => 'utf8mb4',
            
            // Aplicación
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => 'https://localhost',
            
            // Logging
            'LOG_LEVEL' => 'info',
            'LOG_FILE' => __DIR__ . '/storage/logs/app.log',
            
            // Paths
            'BACKUP_PATH' => __DIR__ . '/backups',
            'TEMP_PATH' => '/tmp',
            
            // API Externa - configurar en .env.local
            'APIFY_API_TOKEN' => '',
            'EXTERNAL_API_KEY' => '',
            
            // Cache
            'CACHE_DRIVER' => 'file',
            'CACHE_PATH' => __DIR__ . '/storage/cache',
            
            // Sessions  
            'SESSION_DRIVER' => 'file',
            'SESSION_PATH' => __DIR__ . '/storage/sessions',
            
            // Timezone
            'APP_TIMEZONE' => 'UTC'
        ];
        
        foreach ($defaults as $key => $defaultValue) {
            if (!isset($_ENV[$key])) {
                $_ENV[$key] = $defaultValue;
                putenv("$key=$defaultValue");
            }
        }
    }
    
    public static function get($key, $default = null) 
    {
        self::load();
        return $_ENV[$key] ?? $default;
    }
    
    public static function getDbConfig() 
    {
        self::load();
        
        return [
            'host' => $_ENV['DB_HOST'],
            'dbname' => $_ENV['DB_NAME'], 
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS'],
            'port' => $_ENV['DB_PORT'],
            'charset' => $_ENV['DB_CHARSET']
        ];
    }
    
    public static function isDebug() 
    {
        self::load();
        return strtolower($_ENV['APP_DEBUG']) === 'true';
    }
    
    public static function isProduction() 
    {
        self::load();
        return strtolower($_ENV['APP_ENV']) === 'production';
    }
    
    /**
     * Validar que las variables requeridas estén configuradas
     */
    public static function validateRequired($requiredKeys = []) 
    {
        self::load();
        
        $defaultRequired = ['DB_HOST', 'DB_NAME', 'DB_USER'];
        $keys = empty($requiredKeys) ? $defaultRequired : $requiredKeys;
        
        $missing = [];
        
        foreach ($keys as $key) {
            if (empty($_ENV[$key])) {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new Exception('Missing required environment variables: ' . implode(', ', $missing));
        }
        
        return true;
    }
    
    /**
     * Crear directorios necesarios
     */
    public static function ensureDirectories() 
    {
        $directories = [
            dirname(self::get('LOG_FILE')),
            self::get('BACKUP_PATH'),
            self::get('CACHE_PATH'),
            self::get('SESSION_PATH')
        ];
        
        foreach ($directories as $dir) {
            if ($dir && !is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
}

// Auto-cargar al incluir este archivo
EnvLoader::load();

// Para compatibilidad con scripts existentes
if (!function_exists('env')) {
    function env($key, $default = null) {
        return EnvLoader::get($key, $default);
    }
}

/**
 * Función helper para obtener configuración de base de datos
 */
function getDatabaseConfig() 
{
    return EnvLoader::getDbConfig();
}

/**
 * Función helper para crear conexión PDO
 */
function createDatabaseConnection() 
{
    // Validar configuración requerida
    EnvLoader::validateRequired(['DB_HOST', 'DB_NAME', 'DB_USER']);
    
    $config = EnvLoader::getDbConfig();
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};port={$config['port']};charset={$config['charset']}";
    
    try {
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
        ]);
    } catch (PDOException $e) {
        if (EnvLoader::isDebug()) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        } else {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check configuration.");
        }
    }
}

// Mostrar información de carga si se ejecuta directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🔧 ENV LOADER - Configuración Cargada\n";
    echo str_repeat("-", 40) . "\n";
    
    try {
        $config = EnvLoader::getDbConfig();
        echo "Base de datos:\n";
        echo "  Host: {$config['host']}\n";
        echo "  DB: {$config['dbname']}\n";
        echo "  Usuario: " . substr($config['username'], 0, 3) . "***\n";
        echo "  Puerto: {$config['port']}\n";
        
        echo "\nEntorno:\n";
        echo "  APP_ENV: " . EnvLoader::get('APP_ENV') . "\n";
        echo "  APP_DEBUG: " . (EnvLoader::isDebug() ? 'true' : 'false') . "\n";
        echo "  APP_URL: " . EnvLoader::get('APP_URL') . "\n";
        
        // Crear directorios si no existen
        EnvLoader::ensureDirectories();
        
        echo "\n✅ Configuración cargada correctamente\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        echo "💡 Crear archivo .env.local con las credenciales correctas\n";
    }
}
?>