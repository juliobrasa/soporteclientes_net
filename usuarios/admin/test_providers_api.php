<?php
/**
 * Test simple de la API de proveedores
 */

// Simular request
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'getAiProviders';
$_REQUEST['action'] = 'getAiProviders';

// Incluir la API
ob_start();
include 'admin_api.php';
$output = ob_get_clean();

echo "Test API Proveedores:\n";
echo "====================\n";
echo $output;
?>