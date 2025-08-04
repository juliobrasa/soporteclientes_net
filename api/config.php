<?php
// api/config.php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'soporteia_bookingkavia');
define('DB_USER', 'soporteia_admin');
define('DB_PASS', 'QCF8RhS*}.Oj0u(v');

class Database {
    private $connection;
    
    public function __construct() {
        try {
            $this->connection = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                ]
            );
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Connection failed: ' . $e->getMessage()]);
            exit;
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Query failed: ' . $e->getMessage()]);
            exit;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }
}

// Función para formatear respuestas
function jsonResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Función para validar parámetros
function validateParam($param, $default = null, $type = 'string') {
    $value = $_GET[$param] ?? $default;
    
    switch($type) {
        case 'int':
            return (int) $value;
        case 'float':
            return (float) $value;
        case 'bool':
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        default:
            return $value;
    }
}

// Función para calcular IRO (Índice de Reputación Online)
function calculateIRO($hotelData) {
    $rating = $hotelData['average_rating'] ?? 0;
    $totalReviews = $hotelData['total_reviews'] ?? 0;
    $recentReviews = $hotelData['recent_reviews'] ?? 0;
    $responseRate = $hotelData['response_rate'] ?? 0;
    
    // Componentes del IRO
    $ratingScore = ($rating / 5) * 100;
    $volumeScore = min(($totalReviews / 100) * 100, 100);
    $freshnessScore = min(($recentReviews / 10) * 100, 100);
    $responseScore = $responseRate * 100;
    
    // Pesos de cada componente
    $weights = [
        'rating' => 0.4,
        'volume' => 0.25,
        'freshness' => 0.2,
        'response' => 0.15
    ];
    
    $iro = ($ratingScore * $weights['rating']) + 
           ($volumeScore * $weights['volume']) + 
           ($freshnessScore * $weights['freshness']) + 
           ($responseScore * $weights['response']);
    
    return [
        'score' => round($iro, 1),
        'components' => [
            'rating' => round($ratingScore, 1),
            'volume' => round($volumeScore, 1),
            'freshness' => round($freshnessScore, 1),
            'response' => round($responseScore, 1)
        ]
    ];
}

// Función para análisis semántico básico
function calculateSentimentScore($reviews) {
    $positiveKeywords = ['excelente', 'bueno', 'genial', 'perfecto', 'recomiendo', 'limpio', 'amable'];
    $negativeKeywords = ['malo', 'terrible', 'sucio', 'ruidoso', 'caro', 'lento', 'problema'];
    
    $totalScore = 0;
    $reviewCount = 0;
    
    foreach($reviews as $review) {
        $text = strtolower(($review['liked_text'] ?? '') . ' ' . ($review['disliked_text'] ?? ''));
        
        $positiveCount = 0;
        $negativeCount = 0;
        
        foreach($positiveKeywords as $keyword) {
            $positiveCount += substr_count($text, $keyword);
        }
        
        foreach($negativeKeywords as $keyword) {
            $negativeCount += substr_count($text, $keyword);
        }
        
        $reviewScore = $positiveCount - $negativeCount;
        $totalScore += $reviewScore;
        $reviewCount++;
    }
    
    if ($reviewCount === 0) return 50;
    
    $avgScore = $totalScore / $reviewCount;
    $normalizedScore = max(0, min(100, 50 + ($avgScore * 10)));
    
    return round($normalizedScore, 1);
}
?>