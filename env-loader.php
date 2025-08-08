<?php
/**
 * Cargador de variables de entorno desde archivo .env
 */

function loadEnvFile($filepath = null) {
    if (!$filepath) {
        $filepath = __DIR__ . '/.env';
    }
    
    if (!file_exists($filepath)) {
        return false;
    }
    
    $lines = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue; // Skip comments
        }
        
        if (strpos($line, '=') === false) {
            continue; // Skip invalid lines
        }
        
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        
        // Remove quotes if present
        if ((strpos($value, '"') === 0 && strrpos($value, '"') === strlen($value) - 1) ||
            (strpos($value, "'") === 0 && strrpos($value, "'") === strlen($value) - 1)) {
            $value = substr($value, 1, -1);
        }
        
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
    
    return true;
}

// Auto-load .env file
loadEnvFile();
?>