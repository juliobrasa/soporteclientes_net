<?php
/**
 * Cargador seguro de variables de entorno
 * Evita hardcodear API tokens y claves sensibles en el código
 */

/**
 * Cargar variables de entorno desde archivo .env
 */
function loadEnvFile($filepath = null) {
    // Usar archivo .env en el directorio raíz por defecto
    if ($filepath === null) {
        $filepath = __DIR__ . '/.env';
    }
    
    // Primero cargar .env base
    loadEnvFromFile($filepath);
    
    // Luego cargar .env.local si existe (sobrescribe valores)
    $localPath = __DIR__ . '/.env.local';
    if (file_exists($localPath)) {
        loadEnvFromFile($localPath);
    }
}

function loadEnvFromFile($filepath) {
    
    // Si no existe el archivo .env, intentar crear uno desde .env.example
    if (!file_exists($filepath)) {
        $examplePath = __DIR__ . '/.env.example';
        if (file_exists($examplePath)) {
            copy($examplePath, $filepath);
            error_log("Archivo .env creado desde .env.example. Configura tus tokens reales.");
        }
    }
    
    if (!file_exists($filepath)) {
        error_log("Archivo .env no encontrado en: " . $filepath);
        return false;
    }
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Ignorar comentarios y líneas vacías
        if (strpos(trim($line), '#') === 0 || empty(trim($line))) {
            continue;
        }
        
        // Separar clave=valor
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Remover comillas si existen
        $value = trim($value, '"\'');
        
        // Solo establecer si no existe ya en el entorno
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    return true;
}

/**
 * Obtener variable de entorno de forma segura
 */
function getEnvVar($key, $default = null) {
    // Cargar archivo .env si no se ha hecho ya
    static $loaded = false;
    if (!$loaded) {
        loadEnvFile();
        $loaded = true;
    }
    
    return $_ENV[$key] ?? getenv($key) ?? $default;
}

/**
 * Validar que las variables de entorno críticas estén configuradas
 */
function validateRequiredEnvVars($requiredVars = []) {
    $missing = [];
    
    // Variables por defecto que siempre deben estar configuradas
    $defaultRequired = [
        'APIFY_API_TOKEN'
    ];
    
    $requiredVars = array_merge($defaultRequired, $requiredVars);
    
    foreach ($requiredVars as $var) {
        $value = getEnvVar($var);
        if (empty($value) || $value === 'your_token_here' || $value === 'your_apify_token_here') {
            $missing[] = $var;
        }
    }
    
    if (!empty($missing)) {
        $message = "Variables de entorno faltantes o no configuradas: " . implode(', ', $missing);
        $message .= "\nConfigura estas variables en el archivo .env";
        throw new Exception($message);
    }
    
    return true;
}

/**
 * Crear archivo .env.example con estructura básica
 */
function createEnvExample($force = false) {
    $examplePath = __DIR__ . '/.env.example';
    
    if (file_exists($examplePath) && !$force) {
        return false; // Ya existe
    }
    
    $content = <<<ENV
# Configuración de API tokens
# IMPORTANTE: Configura tus tokens reales aquí

# Token de Apify para extracción de reseñas
APIFY_API_TOKEN=your_apify_token_here

# Configuración de base de datos (si es diferente a admin-config.php)
# DB_HOST=localhost
# DB_NAME=database_name
# DB_USER=username  
# DB_PASS=password

# Configuración de entorno
# APP_ENV=production
# DEBUG_MODE=false

# Configuración de logging
# LOG_LEVEL=info
# LOG_FILE=logs/app.log
ENV;

    return file_put_contents($examplePath, $content) !== false;
}

/**
 * Inicialización automática
 * Carga las variables de entorno al incluir este archivo
 */
function initializeEnvironment() {
    // Crear .env.example si no existe
    createEnvExample();
    
    // Cargar variables de entorno
    loadEnvFile();
    
    // Configurar timezone por defecto
    if (!ini_get('date.timezone')) {
        date_default_timezone_set(getEnvVar('TIMEZONE', 'America/Mexico_City'));
    }
}

// Auto-inicializar al cargar el archivo
initializeEnvironment();

/**
 * Función de utilidad para debugging (solo en desarrollo)
 */
function debugEnvVars() {
    if (getEnvVar('APP_ENV') === 'production') {
        return "Debug no disponible en producción";
    }
    
    $safeVars = [];
    foreach ($_ENV as $key => $value) {
        // Ocultar tokens y passwords
        if (strpos(strtolower($key), 'token') !== false || 
            strpos(strtolower($key), 'password') !== false ||
            strpos(strtolower($key), 'secret') !== false) {
            $safeVars[$key] = '***HIDDEN***';
        } else {
            $safeVars[$key] = $value;
        }
    }
    
    return $safeVars;
}
?>