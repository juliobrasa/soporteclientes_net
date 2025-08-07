<?php
/**
 * Cargador simple de variables de entorno desde archivo .env
 */
function loadEnvVariables($envFile = '.env') {
    if (!file_exists($envFile)) {
        return;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Saltar comentarios
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Dividir en nombre=valor
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            
            // Remover comillas si existen
            if (preg_match('/^"(.*)"$/', $value, $matches)) {
                $value = $matches[1];
            } elseif (preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            
            // Solo establecer si no existe ya
            if (!isset($_ENV[$name]) && !getenv($name)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

// Cargar automáticamente al incluir este archivo
loadEnvVariables(__DIR__ . '/.env');
?>