<?php
/**
 * ==========================================================================
 * TEST DATABASE CONNECTION - Kavia Hoteles
 * Script para probar conexión y crear datos de prueba
 * ==========================================================================
 */

// Configuración de base de datos (misma que admin_api.php)
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

echo "<h2>🔍 Test de Conexión y Base de Datos</h2>";

try {
    // Conectar a la base de datos
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "<p>✅ Conexión a la base de datos exitosa</p>";
    
    // Verificar estructura de tabla hoteles
    echo "<h3>📋 Estructura de tabla 'hoteles'</h3>";
    $stmt = $pdo->query("DESCRIBE hoteles");
    $structure = $stmt->fetchAll();
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Clave</th><th>Default</th><th>Extra</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>{$field['Field']}</td>";
        echo "<td>{$field['Type']}</td>";
        echo "<td>{$field['Null']}</td>";
        echo "<td>{$field['Key']}</td>";
        echo "<td>{$field['Default']}</td>";
        echo "<td>{$field['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Contar hoteles existentes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM hoteles");
    $count = $stmt->fetch()['total'];
    echo "<p>📊 Hoteles existentes en la BD: <strong>$count</strong></p>";
    
    // Mostrar hoteles existentes
    if ($count > 0) {
        echo "<h3>🏨 Hoteles Existentes</h3>";
        $stmt = $pdo->query("SELECT id, nombre_hotel, hoja_destino, activo, created_at FROM hoteles ORDER BY id DESC LIMIT 10");
        $hotels = $stmt->fetchAll();
        
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Nombre</th><th>Destino</th><th>Activo</th><th>Creado</th></tr>";
        foreach ($hotels as $hotel) {
            echo "<tr>";
            echo "<td>{$hotel['id']}</td>";
            echo "<td>{$hotel['nombre_hotel']}</td>";
            echo "<td>{$hotel['hoja_destino']}</td>";
            echo "<td>" . ($hotel['activo'] ? 'Sí' : 'No') . "</td>";
            echo "<td>{$hotel['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<h3>➕ Crear Datos de Prueba</h3>";
        echo "<p>No hay hoteles en la base de datos. Creando datos de prueba...</p>";
        
        // Crear datos de prueba
        $hoteles_prueba = [
            [
                'nombre' => 'Hotel Paradise Madrid',
                'destino' => 'Un elegante hotel en el corazón de Madrid con vistas panorámicas',
                'url' => 'https://hotelparadisemadrid.com',
                'max_reviews' => 500,
                'activo' => 1
            ],
            [
                'nombre' => 'Resort Costa del Sol',
                'destino' => 'Resort de lujo frente al mar en la Costa del Sol',
                'url' => 'https://resortcostadelsol.com',
                'max_reviews' => 750,
                'activo' => 1
            ],
            [
                'nombre' => 'Hotel Boutique Barcelona',
                'destino' => 'Hotel boutique en el centro histórico de Barcelona',
                'url' => 'https://boutiquebarcelona.com',
                'max_reviews' => 300,
                'activo' => 1
            ],
            [
                'nombre' => 'Parador de Sevilla',
                'destino' => 'Hotel histórico en el centro de Sevilla',
                'url' => 'https://paradorsevilla.com',
                'max_reviews' => 400,
                'activo' => 0
            ]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO hoteles (nombre_hotel, hoja_destino, url_booking, max_reviews, activo, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $created = 0;
        foreach ($hoteles_prueba as $hotel) {
            try {
                $stmt->execute([
                    $hotel['nombre'],
                    $hotel['destino'],
                    $hotel['url'],
                    $hotel['max_reviews'],
                    $hotel['activo']
                ]);
                $created++;
                echo "<p>✅ Creado: {$hotel['nombre']}</p>";
            } catch (Exception $e) {
                echo "<p>❌ Error creando {$hotel['nombre']}: {$e->getMessage()}</p>";
            }
        }
        
        echo "<p><strong>✨ Se crearon $created hoteles de prueba</strong></p>";
    }
    
    // Probar endpoint API
    echo "<h3>🔗 Test de API Endpoint</h3>";
    $api_url = "http://{$_SERVER['HTTP_HOST']}" . dirname($_SERVER['REQUEST_URI'], 2) . "/admin_api.php?action=getHotels";
    echo "<p>Probando: <code>$api_url</code></p>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => "Content-Type: application/json\r\n",
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($api_url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['success']) {
            echo "<p>✅ API Response OK - Hoteles encontrados: " . count($data['data']) . "</p>";
            echo "<details><summary>Ver respuesta JSON</summary><pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre></details>";
        } else {
            echo "<p>❌ API Error: " . ($data['error'] ?? 'Respuesta inválida') . "</p>";
        }
    } else {
        echo "<p>❌ No se pudo conectar con el API</p>";
    }
    
    echo "<hr>";
    echo "<h3>🎯 Resumen</h3>";
    echo "<ul>";
    echo "<li>✅ Conexión DB: OK</li>";
    echo "<li>✅ Estructura tabla: OK</li>";
    echo "<li>📊 Datos disponibles: " . ($count + ($created ?? 0)) . " hoteles</li>";
    echo "<li>🔗 API funcionando: " . ($data && $data['success'] ? 'OK' : 'ERROR') . "</li>";
    echo "</ul>";
    
    echo "<p><strong>🚀 El módulo de hoteles debería funcionar correctamente</strong></p>";
    
} catch (PDOException $e) {
    echo "<p>❌ Error de conexión a la base de datos: " . $e->getMessage() . "</p>";
    echo "<p>Verifica la configuración de la base de datos en admin_api.php</p>";
} catch (Exception $e) {
    echo "<p>❌ Error general: " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    background: #f5f5f5;
}

table {
    background: white;
    margin: 10px 0;
    border-collapse: collapse;
    width: 100%;
}

th, td {
    text-align: left;
    border: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: bold;
}

code {
    background: #f1f1f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-family: monospace;
}

pre {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    overflow-x: auto;
    font-size: 12px;
}

details {
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
    padding: 10px;
    background: white;
}

summary {
    cursor: pointer;
    font-weight: bold;
    padding: 5px;
}

ul {
    background: white;
    padding: 15px;
    border-radius: 5px;
    border-left: 4px solid #28a745;
}
</style>