<?php
// Desactivar salida de errores HTML de PHP
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Manejar OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Configuración de base de datos
$host = "localhost";
$db_name = "soporteia_bookingkavia";
$username = "soporteia_admin";
$password = "QCF8RhS*}.Oj0u(v";

// Función para enviar respuesta de error
function sendError($message, $fallback = null) {
    $response = [
        'success' => false,
        'error' => $message
    ];
    if ($fallback) {
        $response['response'] = $fallback;
        $response['provider'] = 'Fallback';
    }
    echo json_encode($response);
    exit;
}

// Función para generar respuesta de fallback
function generateFallbackResponse($review, $hotel) {
    $guest = is_array($review) ? ($review['guest'] ?? 'Estimado huésped') : 'Estimado huésped';
    $rating = is_array($review) ? (floatval($review['rating'] ?? 3)) : 3;
    $hotel = $hotel ?: 'nuestro hotel';
    
    if ($rating >= 4) {
        return "¡Hola $guest! Nos alegra enormemente saber que disfrutaste de tu estancia en $hotel. Tu satisfacción es nuestra mayor recompensa y esperamos verte de nuevo muy pronto para brindarte otra experiencia excepcional. ¡Gracias por elegirnos!";
    } else if ($rating <= 2) {
        return "Estimado/a $guest, lamentamos profundamente que tu experiencia en $hotel no haya cumplido con tus expectativas. Hemos tomado nota de tus comentarios para implementar mejoras inmediatas. Te invitamos a contactarnos directamente para resolver cualquier inconveniente.";
    } else {
        return "Hola $guest, muchas gracias por tu reseña sobre $hotel. Apreciamos tanto tus elogios como tus sugerencias constructivas, ya que nos ayudan a seguir mejorando. Esperamos verte pronto.";
    }
}

// Conectar a la base de datos
try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    $review = isset($_POST['review']) ? json_decode($_POST['review'], true) : [];
    $hotel = $_POST['hotel'] ?? 'Hotel';
    sendError('Database connection failed', generateFallbackResponse($review, $hotel));
}

// Función para obtener el proveedor activo
function getActiveProvider($conn) {
    try {
        $stmt = $conn->query("SELECT * FROM ai_providers WHERE is_active = 1 LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        return null;
    }
}

// Función para obtener el prompt activo
function getActivePrompt($conn, $type = 'response', $language = 'es') {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM ai_prompts 
            WHERE is_active = 1 
            AND prompt_type = :type 
            AND (language = :language OR language IS NULL OR language = '')
            ORDER BY language DESC
            LIMIT 1
        ");
        $stmt->execute(['type' => $type, 'language' => $language]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        return null;
    }
}

// Función para reemplazar variables en el prompt
function replacePromptVariables($promptText, $data) {
    $replacements = [
        '{hotel_name}' => $data['hotel'] ?? 'nuestro hotel',
        '{guest_name}' => $data['guest'] ?? 'Estimado huésped',
        '{guest}' => $data['guest'] ?? 'Estimado huésped',
        '{rating}' => $data['rating'] ?? '',
        '{positive}' => $data['positive'] ?? '',
        '{negative}' => $data['negative'] ?? '',
        '{date}' => $data['date'] ?? date('Y-m-d'),
        '{title}' => $data['title'] ?? '',
        '{trip_type}' => $data['tripType'] ?? $data['trip_type'] ?? '',
        '{country}' => $data['country'] ?? ''
    ];
    
    return str_replace(array_keys($replacements), array_values($replacements), $promptText);
}

// Función para registrar en logs
function logResponse($conn, $providerId, $hotelName, $reviewData, $responseText, $tokensUsed = 0) {
    try {
        // Verificar si la tabla existe
        $stmt = $conn->query("SHOW TABLES LIKE 'ai_response_logs'");
        if ($stmt->rowCount() == 0) {
            // Crear la tabla si no existe
            $conn->exec("
                CREATE TABLE IF NOT EXISTS `ai_response_logs` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `provider_id` int(11),
                    `hotel_name` varchar(255),
                    `review_data` text,
                    `response_text` text,
                    `tokens_used` int(11) DEFAULT 0,
                    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        }
        
        $stmt = $conn->prepare("
            INSERT INTO ai_response_logs (provider_id, hotel_name, review_data, response_text, tokens_used, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$providerId, $hotelName, $reviewData, $responseText, $tokensUsed]);
    } catch(Exception $e) {
        error_log("Error logging response: " . $e->getMessage());
    }
}

// Función para llamar a OpenAI
function callOpenAI($apiKey, $prompt, $model = 'gpt-3.5-turbo', $apiUrl = null) {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }
    
    $url = $apiUrl ?: 'https://api.openai.com/v1/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Eres un asistente profesional de atención al cliente hotelero. Responde siempre en español de manera cordial y profesional.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 400
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'CURL error: ' . $curlError];
    }
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'response' => trim($result['choices'][0]['message']['content']),
                'tokens' => $result['usage']['total_tokens'] ?? 0
            ];
        }
        return ['success' => false, 'error' => 'Invalid response format from OpenAI'];
    }
    
    // Parsear error de OpenAI
    $errorData = json_decode($response, true);
    $errorMessage = $errorData['error']['message'] ?? 'Unknown OpenAI error';
    
    return ['success' => false, 'error' => 'OpenAI API error: ' . $errorMessage];
}

// Función para llamar a DeepSeek
function callDeepSeek($apiKey, $prompt, $model = 'deepseek-chat', $apiUrl = null) {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }
    
    $url = $apiUrl ?: 'https://api.deepseek.com/v1/chat/completions';
    
    $data = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'Eres un asistente profesional de atención al cliente hotelero. Responde siempre en español de manera cordial y profesional.'],
            ['role' => 'user', 'content' => $prompt]
        ],
        'temperature' => 0.7,
        'max_tokens' => 400
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'CURL error: ' . $curlError];
    }
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['choices'][0]['message']['content'])) {
            return [
                'success' => true,
                'response' => trim($result['choices'][0]['message']['content']),
                'tokens' => $result['usage']['total_tokens'] ?? 0
            ];
        }
        return ['success' => false, 'error' => 'Invalid response format from DeepSeek'];
    }
    
    return ['success' => false, 'error' => 'DeepSeek API error (HTTP ' . $httpCode . ')'];
}

// Función para llamar a Claude (Anthropic)
function callClaude($apiKey, $prompt, $model = 'claude-3-sonnet-20240229', $apiUrl = null) {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }
    
    $url = $apiUrl ?: 'https://api.anthropic.com/v1/messages';
    
    $data = [
        'model' => $model,
        'max_tokens' => 400,
        'messages' => [
            ['role' => 'user', 'content' => $prompt]
        ],
        'system' => 'Eres un asistente profesional de atención al cliente hotelero. Responde siempre en español de manera cordial y profesional.'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'CURL error: ' . $curlError];
    }
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['content'][0]['text'])) {
            return [
                'success' => true,
                'response' => trim($result['content'][0]['text']),
                'tokens' => $result['usage']['input_tokens'] + $result['usage']['output_tokens'] ?? 0
            ];
        }
        return ['success' => false, 'error' => 'Invalid response format from Claude'];
    }
    
    return ['success' => false, 'error' => 'Claude API error (HTTP ' . $httpCode . ')'];
}

// Función para llamar a Google Gemini
function callGemini($apiKey, $prompt, $model = 'gemini-pro', $apiUrl = null) {
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'API key not configured'];
    }
    
    $url = $apiUrl ?: "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
    
    $data = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.7,
            'maxOutputTokens' => 400
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        return ['success' => false, 'error' => 'CURL error: ' . $curlError];
    }
    
    if ($httpCode == 200) {
        $result = json_decode($response, true);
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'response' => trim($result['candidates'][0]['content']['parts'][0]['text']),
                'tokens' => 0 // Gemini doesn't return token count in the same way
            ];
        }
        return ['success' => false, 'error' => 'Invalid response format from Gemini'];
    }
    
    return ['success' => false, 'error' => 'Gemini API error (HTTP ' . $httpCode . ')'];
}

// MAIN LOGIC
try {
    // Obtener acción
    $action = $_POST['action'] ?? $_REQUEST['action'] ?? 'generate';
    
    // Manejo de traducción
    if ($action === 'translate') {
        $text = $_POST['text'] ?? '';
        
        if (empty($text)) {
            sendError('No text to translate');
        }
        
        $provider = getActiveProvider($conn);
        if (!$provider) {
            sendError('No active AI provider configured');
        }
        
        // Obtener prompt de traducción si existe
        $translationPrompt = getActivePrompt($conn, 'translation', 'es');
        if ($translationPrompt) {
            $prompt = replacePromptVariables($translationPrompt['prompt_text'], ['text' => $text]);
        } else {
            $prompt = "Traduce el siguiente texto al español. Solo devuelve la traducción, sin explicaciones adicionales:\n\n$text";
        }
        
        // Parsear parámetros del proveedor si existen
        $parameters = [];
        if (!empty($provider['parameters'])) {
            $parameters = json_decode($provider['parameters'], true) ?: [];
        }
        
        $apiUrl = !empty($provider['api_url']) ? $provider['api_url'] : null;
        $modelName = !empty($provider['model_name']) ? $provider['model_name'] : null;
        
        switch($provider['provider_type']) {
            case 'openai':
                $result = callOpenAI($provider['api_key'], $prompt, $modelName ?: 'gpt-3.5-turbo', $apiUrl);
                break;
            case 'deepseek':
                $result = callDeepSeek($provider['api_key'], $prompt, $modelName ?: 'deepseek-chat', $apiUrl);
                break;
            case 'claude':
                $result = callClaude($provider['api_key'], $prompt, $modelName ?: 'claude-3-sonnet-20240229', $apiUrl);
                break;
            case 'gemini':
                $result = callGemini($provider['api_key'], $prompt, $modelName ?: 'gemini-pro', $apiUrl);
                break;
            default:
                $result = ['success' => false, 'error' => 'Provider not supported for translation'];
        }
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'translation' => $result['response'],
                'provider' => $provider['name']
            ]);
        } else {
            sendError($result['error']);
        }
        exit;
    }
    
    // Generación de respuesta
    $reviewData = $_POST['review'] ?? '';
    $hotelName = $_POST['hotel'] ?? 'Hotel';
    
    if (empty($reviewData)) {
        $fallback = generateFallbackResponse([], $hotelName);
        sendError('No review data provided', $fallback);
    }
    
    // Decodificar datos de la reseña
    $review = json_decode($reviewData, true);
    if (!$review) {
        $fallback = generateFallbackResponse([], $hotelName);
        sendError('Invalid review data format', $fallback);
    }
    
    // Agregar el nombre del hotel a los datos de la reseña
    $review['hotel'] = $hotelName;
    
    // Obtener proveedor activo
    $provider = getActiveProvider($conn);
    if (!$provider) {
        $fallback = generateFallbackResponse($review, $hotelName);
        echo json_encode([
            'success' => true,
            'response' => $fallback,
            'provider' => 'Local/Fallback',
            'message' => 'No active AI provider, using fallback'
        ]);
        exit;
    }
    
    // Obtener prompt activo
    $promptTemplate = getActivePrompt($conn, 'response', 'es');
    
    // Preparar el prompt
    if ($promptTemplate && !empty($promptTemplate['prompt_text'])) {
        $prompt = replacePromptVariables($promptTemplate['prompt_text'], $review);
    } else {
        // Prompt por defecto mejorado
        $prompt = "Como representante del hotel $hotelName, genera una respuesta profesional y empática para esta reseña:\n\n";
        $prompt .= "Huésped: " . ($review['guest'] ?? 'Anónimo') . "\n";
        $prompt .= "País: " . ($review['country'] ?? 'No especificado') . "\n";
        $prompt .= "Calificación: " . ($review['rating'] ?? 'N/A') . "/5\n";
        $prompt .= "Fecha: " . ($review['date'] ?? date('Y-m-d')) . "\n";
        
        if (!empty($review['title'])) {
            $prompt .= "Título: " . $review['title'] . "\n";
        }
        if (!empty($review['positive'])) {
            $prompt .= "\nComentarios positivos: " . $review['positive'] . "\n";
        }
        if (!empty($review['negative'])) {
            $prompt .= "\nComentarios negativos: " . $review['negative'] . "\n";
        }
        
        $prompt .= "\nLa respuesta debe:\n";
        $prompt .= "- Agradecer al huésped por su tiempo y comentarios\n";
        $prompt .= "- Reconocer los aspectos positivos mencionados\n";
        $prompt .= "- Si hay críticas, mostrar empatía y mencionar acciones de mejora\n";
        $prompt .= "- Invitar al huésped a volver\n";
        $prompt .= "- Ser cordial, personalizada y profesional\n";
        $prompt .= "- Tener entre 80-150 palabras\n";
        $prompt .= "- NO incluir firma ni información de contacto";
    }
    
    // Parsear parámetros del proveedor si existen
    $parameters = [];
    if (!empty($provider['parameters'])) {
        $parameters = json_decode($provider['parameters'], true) ?: [];
    }
    
    $apiUrl = !empty($provider['api_url']) ? $provider['api_url'] : null;
    $modelName = !empty($provider['model_name']) ? $provider['model_name'] : null;
    
    // Llamar al proveedor de IA
    $startTime = microtime(true);
    $aiResult = ['success' => false];
    $providerName = $provider['name'] ?? $provider['provider_type'];
    
    switch($provider['provider_type']) {
        case 'openai':
            $aiResult = callOpenAI($provider['api_key'], $prompt, $modelName ?: 'gpt-3.5-turbo', $apiUrl);
            break;
            
        case 'deepseek':
            $aiResult = callDeepSeek($provider['api_key'], $prompt, $modelName ?: 'deepseek-chat', $apiUrl);
            break;
            
        case 'claude':
            $aiResult = callClaude($provider['api_key'], $prompt, $modelName ?: 'claude-3-sonnet-20240229', $apiUrl);
            break;
            
        case 'gemini':
            $aiResult = callGemini($provider['api_key'], $prompt, $modelName ?: 'gemini-pro', $apiUrl);
            break;
            
        case 'local':
        default:
            $fallbackResponse = generateFallbackResponse($review, $hotelName);
            $aiResult = [
                'success' => true,
                'response' => $fallbackResponse,
                'tokens' => 0
            ];
            $providerName = 'Local/Fallback';
            break;
    }
    
    if ($aiResult['success']) {
        // Log response
        logResponse($conn, $provider['id'], $hotelName, $reviewData, $aiResult['response'], $aiResult['tokens'] ?? 0);
        
        echo json_encode([
            'success' => true,
            'response' => $aiResult['response'],
            'provider' => $providerName,
            'tokens' => $aiResult['tokens'] ?? 0,
            'processing_time_ms' => round((microtime(true) - $startTime) * 1000)
        ]);
    } else {
        // Usar fallback si falla
        $fallback = generateFallbackResponse($review, $hotelName);
        
        // Log el intento fallido también
        logResponse($conn, $provider['id'], $hotelName, $reviewData, $fallback . ' [FALLBACK: ' . $aiResult['error'] . ']', 0);
        
        echo json_encode([
            'success' => true,
            'response' => $fallback,
            'provider' => 'Fallback',
            'original_error' => $aiResult['error'],
            'message' => 'AI provider failed, using fallback'
        ]);
    }
    
} catch(Exception $e) {
    // Error general - usar fallback
    $review = isset($_POST['review']) ? json_decode($_POST['review'], true) : [];
    $hotel = $_POST['hotel'] ?? 'Hotel';
    $fallback = generateFallbackResponse($review, $hotel);
    
    echo json_encode([
        'success' => true,
        'response' => $fallback,
        'provider' => 'Fallback',
        'error_message' => $e->getMessage()
    ]);
}
?>