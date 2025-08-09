<?php
/**
 * ARCHIVO PROTEGIDO POR SEGURIDAD
 * Script original movido a: /root/soporteclientes_net/admin-tools/secure-debug-scripts.php
 * Fecha: 2025-08-09 00:41:48
 */

session_start();
if (!isset($_SESSION['admin_logged']) || $_SESSION['admin_logged'] !== true) {
    http_response_code(403);
    die('Acceso denegado. Se requiere autenticación de administrador.');
}

echo '<h1>🔒 Script Protegido</h1>';
echo '<p>Este script ha sido movido por razones de seguridad.</p>';
echo '<p>Ubicación segura: <code>/root/soporteclientes_net/admin-tools/secure-debug-scripts.php</code></p>';
echo '<p>Para acceder, inicie sesión como administrador.</p>';
?>