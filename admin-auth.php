<?php
session_start();

// Credenciales admin
$admin_email = 'admin@soporteclientes.net';
$admin_password = 'admin123';

if ($_POST['email'] === $admin_email && $_POST['password'] === $admin_password) {
    $_SESSION['admin_logged'] = true;
    $_SESSION['admin_email'] = $admin_email;
    header('Location: admin-dashboard.php');
    exit;
} else {
    header('Location: admin-login.php?error=1');
    exit;
}
?>