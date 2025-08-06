<?php
// Test simple para obtener hoteles
$_REQUEST['action'] = 'getHotels';
$_SERVER['REQUEST_METHOD'] = 'GET';

include 'admin_api.php';
?>