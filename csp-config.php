<?php
/**
 * Configuración de Content Security Policy (CSP) específica por página
 */

// Función para generar CSP header apropiado según el contexto
function setCSPHeader($pageType = 'admin') {
    $policies = [
        'admin' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://code.jquery.com https://cdn.datatables.net https://cdnjs.cloudflare.com https://stackpath.bootstrapcdn.com https://unpkg.com https://maxcdn.bootstrapcdn.com",
            'style-src' => "'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.datatables.net https://cdnjs.cloudflare.com https://fonts.googleapis.com https://stackpath.bootstrapcdn.com https://unpkg.com https://maxcdn.bootstrapcdn.com",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' https: https://fonts.gstatic.com https://maxcdn.bootstrapcdn.com data:",
            'connect-src' => "'self' https:",
            'object-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
            'frame-ancestors' => "'none'"
        ],
        
        'client' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline'",
            'style-src' => "'self' 'unsafe-inline'", 
            'img-src' => "'self' data: https:",
            'font-src' => "'self'",
            'connect-src' => "'self'",
            'object-src' => "'none'",
            'base-uri' => "'self'"
        ],
        
        'public' => [
            'default-src' => "'self'",
            'script-src' => "'self'",
            'style-src' => "'self'",
            'img-src' => "'self' data:",
            'object-src' => "'none'"
        ]
    ];
    
    $policy = $policies[$pageType] ?? $policies['public'];
    
    $cspString = '';
    foreach ($policy as $directive => $sources) {
        $cspString .= $directive . ' ' . $sources . '; ';
    }
    
    header('Content-Security-Policy: ' . trim($cspString));
}

// Función para páginas administrativas (más permisiva para CDNs)
function setAdminCSP() {
    setCSPHeader('admin');
}

// Función para páginas de clientes (restrictiva)  
function setClientCSP() {
    setCSPHeader('client');
}

// Función para páginas públicas (muy restrictiva)
function setPublicCSP() {
    setCSPHeader('public');
}

// Auto-aplicar según el contexto del archivo
function autoApplyCSP() {
    $scriptName = basename($_SERVER['SCRIPT_NAME'], '.php');
    
    if (strpos($scriptName, 'admin-') === 0) {
        setAdminCSP();
    } elseif (strpos($scriptName, 'client-') === 0) {
        setClientCSP(); 
    } else {
        setPublicCSP();
    }
}

// Función para deshabilitar CSP temporalmente (solo para debugging)
function disableCSP() {
    if (defined('APP_ENV') && APP_ENV === 'development') {
        // Solo en desarrollo, permitir deshabilitar CSP
        header('Content-Security-Policy: default-src * \'unsafe-inline\' \'unsafe-eval\' data: blob:;');
    }
}

?>