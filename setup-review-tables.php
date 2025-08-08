<?php
/**
 * Script para crear las tablas necesarias para el sistema de reseñas
 */

require_once 'admin-config.php';

function executeSQL($pdo, $sql) {
    try {
        $result = $pdo->exec($sql);
        return ['success' => true, 'affected_rows' => $result];
    } catch (PDOException $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

$pdo = getDBConnection();
if (!$pdo) {
    die("❌ Error de conexión a la base de datos\n");
}

echo "🔧 CONFIGURANDO SISTEMA DE RESEÑAS MULTI-PLATAFORMA\n";
echo "==================================================\n\n";

// Lista de queries SQL a ejecutar
$queries = [
    // Tabla para análisis de sentimientos
    "CREATE TABLE IF NOT EXISTS review_analysis (
        id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        sentiment ENUM('positive', 'negative', 'neutral') NOT NULL,
        sentiment_score DECIMAL(3,2) DEFAULT 0.00,
        confidence DECIMAL(3,2) DEFAULT 0.00,
        topics JSON,
        language VARCHAR(5) DEFAULT 'en',
        analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_review (review_id),
        INDEX idx_sentiment (sentiment),
        INDEX idx_score (sentiment_score)
    )",
    
    // Tabla para plataformas
    "CREATE TABLE IF NOT EXISTS review_platforms (
        id INT AUTO_INCREMENT PRIMARY KEY,
        platform_name VARCHAR(50) UNIQUE NOT NULL,
        platform_url VARCHAR(255),
        rating_scale INT DEFAULT 5,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Insertar plataformas
    "INSERT IGNORE INTO review_platforms (platform_name, platform_url, rating_scale) VALUES
    ('tripadvisor', 'https://tripadvisor.com', 5),
    ('booking', 'https://booking.com', 10),
    ('expedia', 'https://expedia.com', 5),
    ('hotels', 'https://hotels.com', 5),
    ('airbnb', 'https://airbnb.com', 5),
    ('yelp', 'https://yelp.com', 5),
    ('google', 'https://maps.google.com', 5)",
    
    // Tabla para runs de Apify
    "CREATE TABLE IF NOT EXISTS apify_extraction_runs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hotel_id INT NOT NULL,
        apify_run_id VARCHAR(100) UNIQUE NOT NULL,
        status ENUM('pending', 'running', 'succeeded', 'failed', 'timeout') DEFAULT 'pending',
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        finished_at TIMESTAMP NULL,
        total_reviews_extracted INT DEFAULT 0,
        platforms_requested JSON,
        max_reviews_per_platform INT DEFAULT 100,
        cost_estimate DECIMAL(8,2) DEFAULT 0.00,
        actual_cost DECIMAL(8,2) NULL,
        error_message TEXT NULL,
        apify_response JSON,
        INDEX idx_hotel (hotel_id),
        INDEX idx_status (status),
        INDEX idx_started (started_at)
    )",
    
    // Tabla para métricas
    "CREATE TABLE IF NOT EXISTS hotel_review_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hotel_id INT NOT NULL,
        platform VARCHAR(50) NOT NULL,
        total_reviews INT DEFAULT 0,
        average_rating DECIMAL(3,2) DEFAULT 0.00,
        positive_reviews INT DEFAULT 0,
        negative_reviews INT DEFAULT 0,
        neutral_reviews INT DEFAULT 0,
        last_review_date DATE,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_hotel_platform (hotel_id, platform),
        INDEX idx_hotel (hotel_id),
        INDEX idx_platform (platform)
    )",
    
    // Tabla para alertas
    "CREATE TABLE IF NOT EXISTS review_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        hotel_id INT NOT NULL,
        alert_type ENUM('rating_drop', 'negative_spike', 'low_volume', 'competitor_alert') NOT NULL,
        severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        message TEXT NOT NULL,
        threshold_value DECIMAL(5,2),
        current_value DECIMAL(5,2),
        is_resolved TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        resolved_at TIMESTAMP NULL,
        INDEX idx_hotel (hotel_id),
        INDEX idx_type (alert_type),
        INDEX idx_resolved (is_resolved)
    )"
];

// Actualizar tabla reviews
$updateReviewsQueries = [
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS platform VARCHAR(50) DEFAULT 'unknown'",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS platform_review_id VARCHAR(100)",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS reviewer_name VARCHAR(100)",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS helpful_votes INT DEFAULT 0",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS response_from_owner TEXT",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS reviewer_info JSON",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS original_rating DECIMAL(3,2)",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS normalized_rating DECIMAL(3,2)",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS review_language VARCHAR(5) DEFAULT 'en'",
    "ALTER TABLE reviews ADD COLUMN IF NOT EXISTS extraction_run_id INT"
];

// Actualizar tabla hoteles
$updateHotelesQueries = [
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS google_place_id VARCHAR(100)",
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS coordinates_lat DECIMAL(10, 8)",
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS coordinates_lng DECIMAL(11, 8)",
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS total_reviews_count INT DEFAULT 0",
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS average_rating_overall DECIMAL(3,2) DEFAULT 0.00",
    "ALTER TABLE hoteles ADD COLUMN IF NOT EXISTS last_extraction_date TIMESTAMP NULL"
];

$totalQueries = count($queries) + count($updateReviewsQueries) + count($updateHotelesQueries);
$executed = 0;
$errors = 0;

// Ejecutar queries principales
foreach ($queries as $query) {
    $executed++;
    echo "[$executed/$totalQueries] Ejecutando query...\n";
    
    $result = executeSQL($pdo, $query);
    if ($result['success']) {
        echo "   ✅ Completado\n";
    } else {
        echo "   ❌ Error: " . $result['error'] . "\n";
        $errors++;
    }
}

// Actualizar tabla reviews
echo "\n📝 Actualizando tabla reviews...\n";
foreach ($updateReviewsQueries as $query) {
    $executed++;
    echo "[$executed/$totalQueries] Agregando columna...\n";
    
    $result = executeSQL($pdo, $query);
    if ($result['success']) {
        echo "   ✅ Completado\n";
    } else {
        echo "   ⚠️  " . $result['error'] . "\n";
        // No contar como error si la columna ya existe
        if (strpos($result['error'], 'Duplicate column name') === false) {
            $errors++;
        }
    }
}

// Actualizar tabla hoteles
echo "\n🏨 Actualizando tabla hoteles...\n";
foreach ($updateHotelesQueries as $query) {
    $executed++;
    echo "[$executed/$totalQueries] Agregando columna...\n";
    
    $result = executeSQL($pdo, $query);
    if ($result['success']) {
        echo "   ✅ Completado\n";
    } else {
        echo "   ⚠️  " . $result['error'] . "\n";
        if (strpos($result['error'], 'Duplicate column name') === false) {
            $errors++;
        }
    }
}

// Crear índices adicionales
echo "\n📊 Creando índices...\n";
$indexQueries = [
    "CREATE INDEX IF NOT EXISTS idx_reviews_platform ON reviews(platform)",
    "CREATE INDEX IF NOT EXISTS idx_reviews_rating ON reviews(normalized_rating)",
    "CREATE INDEX IF NOT EXISTS idx_reviews_language ON reviews(review_language)",
    "CREATE INDEX IF NOT EXISTS idx_hoteles_place_id ON hoteles(google_place_id)",
    "CREATE INDEX IF NOT EXISTS idx_hoteles_rating ON hoteles(average_rating_overall)"
];

foreach ($indexQueries as $query) {
    $result = executeSQL($pdo, $query);
    if ($result['success']) {
        echo "   ✅ Índice creado\n";
    } else {
        echo "   ⚠️  " . $result['error'] . "\n";
    }
}

// Verificar estructura final
echo "\n🔍 Verificando estructura de tablas...\n";
$verificationQueries = [
    "SHOW TABLES LIKE 'review_analysis'",
    "SHOW TABLES LIKE 'apify_extraction_runs'",
    "SHOW TABLES LIKE 'review_platforms'",
    "SHOW TABLES LIKE 'hotel_review_metrics'"
];

$tablesCreated = 0;
foreach ($verificationQueries as $query) {
    $stmt = $pdo->query($query);
    if ($stmt && $stmt->fetch()) {
        $tablesCreated++;
        echo "   ✅ Tabla verificada\n";
    } else {
        echo "   ❌ Tabla no encontrada\n";
        $errors++;
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "🎉 CONFIGURACIÓN COMPLETADA\n";
echo "Tablas creadas: $tablesCreated/4\n";
echo "Queries ejecutadas: $executed\n";
echo "Errores: $errors\n";

if ($errors == 0) {
    echo "\n✅ ¡Sistema de reseñas configurado exitosamente!\n";
    echo "📝 Funcionalidades habilitadas:\n";
    echo "   - Extracción multi-plataforma con Apify\n";
    echo "   - Análisis de sentimientos automático\n";
    echo "   - Métricas agregadas por hotel/plataforma\n";
    echo "   - Sistema de alertas automáticas\n";
} else {
    echo "\n⚠️  Configuración completada con algunos errores.\n";
    echo "   Revisa los errores anteriores y ejecuta el script nuevamente si es necesario.\n";
}

echo "\n💡 Próximos pasos:\n";
echo "   1. Configurar APIFY_API_TOKEN en variables de entorno\n";
echo "   2. Agregar Google Place IDs a los hoteles existentes\n";
echo "   3. Probar una extracción desde el panel admin\n";
echo "\n";
?>