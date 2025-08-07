<?php

// Redirigir todo el tráfico al directorio public/
// Este archivo debe estar en la raíz de kavia-laravel/

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);

// Si ya estamos en public/, continúa normalmente
if (substr($uri, 0, strlen('/kavia-laravel/public/')) === '/kavia-laravel/public/') {
    require_once __DIR__ . '/public/index.php';
    return;
}

// Redirigir a public/
if ($uri !== '/' && file_exists(__DIR__ . '/public' . $uri)) {
    return false;
}

require_once __DIR__ . '/public/index.php';