<?php
// Test para verificar la API de IA
header('Content-Type: text/html; charset=utf-8');

// Configuración de base de datos
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "<h2>✅ Conexión a base de datos: OK</h2>";
} catch(PDOException $e) {
    echo "<h2>❌ Error de conexión a base de datos:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    exit;
}

// Verificar proveedores activos
echo "<h3>🤖 Proveedores de IA:</h3>";
$stmt = $conn->query("SELECT * FROM ai_providers WHERE is_active = 1");
$providers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($providers)) {
    echo "<p style='color: red;'>⚠️ No hay proveedores de IA activos</p>";
} else {
    echo "<ul>";
    foreach ($providers as $provider) {
        echo "<li><strong>{$provider['name']}</strong> - Tipo: {$provider['provider_type']} - API Key: " . 
             (empty($provider['api_key']) ? "❌ NO CONFIGURADA" : "✅ Configurada") . "</li>";
    }
    echo "</ul>";
}

// Verificar prompts activos
echo "<h3>📝 Prompts activos:</h3>";
$stmt = $conn->query("SELECT * FROM ai_prompts WHERE is_active = 1 AND prompt_type = 'response'");
$prompts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($prompts)) {
    echo "<p style='color: red;'>⚠️ No hay prompts activos</p>";
} else {
    echo "<ul>";
    foreach ($prompts as $prompt) {
        echo "<li><strong>{$prompt['name']}</strong> - Idioma: {$prompt['language']}</li>";
        echo "<pre style='background: #f0f0f0; padding: 10px; font-size: 12px;'>" . 
             htmlspecialchars(substr($prompt['prompt_text'], 0, 200)) . "...</pre>";
    }
    echo "</ul>";
}

// Test de generación
if (isset($_GET['test'])) {
    echo "<h3>🧪 Test de generación de respuesta:</h3>";
    
    $testReview = [
        'guest' => 'Juan Pérez',
        'rating' => 5,
        'positive' => 'El hotel es excelente, las habitaciones muy limpias y el personal muy amable.',
        'negative' => '',
        'date' => '2024-08-03',
        'country' => 'España'
    ];
    
    echo "<p><strong>Datos de prueba:</strong></p>";
    echo "<pre>" . json_encode($testReview, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
    // Hacer llamada a la API
    $ch = curl_init('http://soporteclientes.net/usuarios/api/ai_response.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'action' => 'generate',
        'review' => json_encode($testReview),
        'hotel' => 'Hotel Ambiance'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>Respuesta de la API (HTTP $httpCode):</strong></p>";
    echo "<pre style='background: #f0f0f0; padding: 10px;'>" . 
         htmlspecialchars($response) . "</pre>";
    
    $data = json_decode($response, true);
    if ($data && isset($data['response'])) {
        echo "<p><strong>Respuesta generada:</strong></p>";
        echo "<div style='background: #e8f5e9; padding: 15px; border-radius: 5px;'>" . 
             nl2br(htmlspecialchars($data['response'])) . "</div>";
        echo "<p><small>Proveedor: " . ($data['provider'] ?? 'Desconocido') . "</small></p>";
    }
}

// Verificar últimos logs
echo "<h3>📊 Últimos logs de respuestas:</h3>";
$stmt = $conn->query("SHOW TABLES LIKE 'ai_response_logs'");
if ($stmt->rowCount() > 0) {
    $stmt = $conn->query("SELECT * FROM ai_response_logs ORDER BY created_at DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($logs)) {
        echo "<p>No hay logs registrados</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Hotel</th><th>Proveedor ID</th><th>Fecha</th><th>Tokens</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>{$log['id']}</td>";
            echo "<td>{$log['hotel_name']}</td>";
            echo "<td>{$log['provider_id']}</td>";
            echo "<td>{$log['created_at']}</td>";
            echo "<td>{$log['tokens_used']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} else {
    echo "<p>La tabla ai_response_logs no existe</p>";
}
?>

<hr>
<h3>🔧 Acciones:</h3>
<a href="?test=1" style="background: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">
    Ejecutar Test de Generación
</a>

<h3>💡 Solución rápida si no hay prompts:</h3>
<pre style="background: #f0f0f0; padding: 10px;">
INSERT INTO ai_prompts (name, prompt_text, prompt_type, language, is_active) VALUES (
    'Respuesta Estándar',
    'Como representante del hotel {hotel_name}, genera una respuesta profesional y empática para esta reseña:

Huésped: {guest_name}
Calificación: {rating}/5

Comentarios positivos: {positive}
Comentarios negativos: {negative}

La respuesta debe agradecer al huésped, ser cordial y profesional, y tener entre 80-120 palabras.',
    'response',
    'es',
    1
);
</pre>