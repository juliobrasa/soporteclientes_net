-- Crear nuevas tablas para el sistema de reseñas multi-plataforma
-- Basado en la estructura existente pero optimizado para Apify Hotel Review Aggregator

-- Tabla para análisis de sentimientos de reseñas
CREATE TABLE IF NOT EXISTS review_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    review_id INT NOT NULL,
    sentiment ENUM('positive', 'negative', 'neutral') NOT NULL,
    sentiment_score DECIMAL(3,2) DEFAULT 0.00,
    confidence DECIMAL(3,2) DEFAULT 0.00,
    topics JSON,
    language VARCHAR(5) DEFAULT 'en',
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (review_id) REFERENCES reviews(id) ON DELETE CASCADE,
    INDEX idx_sentiment (sentiment),
    INDEX idx_score (sentiment_score),
    INDEX idx_analyzed (analyzed_at)
);

-- Tabla para información de plataformas de reseñas
CREATE TABLE IF NOT EXISTS review_platforms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    platform_name VARCHAR(50) UNIQUE NOT NULL,
    platform_url VARCHAR(255),
    rating_scale INT DEFAULT 5,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insertar plataformas soportadas por el actor
INSERT IGNORE INTO review_platforms (platform_name, platform_url, rating_scale) VALUES
('tripadvisor', 'https://tripadvisor.com', 5),
('booking', 'https://booking.com', 10),
('expedia', 'https://expedia.com', 5),
('hotels', 'https://hotels.com', 5),
('airbnb', 'https://airbnb.com', 5),
('yelp', 'https://yelp.com', 5),
('google', 'https://maps.google.com', 5);

-- Tabla para trabajos de extracción con Apify
CREATE TABLE IF NOT EXISTS apify_extraction_runs (
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
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_hotel (hotel_id),
    INDEX idx_started (started_at)
);

-- Actualizar tabla de reseñas existente para soportar múltiples plataformas
ALTER TABLE reviews 
ADD COLUMN IF NOT EXISTS platform VARCHAR(50) DEFAULT 'unknown',
ADD COLUMN IF NOT EXISTS platform_review_id VARCHAR(100),
ADD COLUMN IF NOT EXISTS reviewer_name VARCHAR(100),
ADD COLUMN IF NOT EXISTS helpful_votes INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS response_from_owner TEXT,
ADD COLUMN IF NOT EXISTS reviewer_info JSON,
ADD COLUMN IF NOT EXISTS original_rating DECIMAL(3,2),
ADD COLUMN IF NOT EXISTS normalized_rating DECIMAL(3,2),
ADD COLUMN IF NOT EXISTS review_language VARCHAR(5) DEFAULT 'en',
ADD COLUMN IF NOT EXISTS extraction_run_id INT,
ADD INDEX IF NOT EXISTS idx_platform (platform),
ADD INDEX IF NOT EXISTS idx_rating (normalized_rating),
ADD INDEX IF NOT EXISTS idx_language (review_language);

-- Tabla para métricas agregadas por hotel y plataforma
CREATE TABLE IF NOT EXISTS hotel_review_metrics (
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
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    UNIQUE KEY unique_hotel_platform (hotel_id, platform),
    INDEX idx_hotel (hotel_id),
    INDEX idx_platform (platform),
    INDEX idx_rating (average_rating)
);

-- Tabla para comparaciones competitivas
CREATE TABLE IF NOT EXISTS competitor_analysis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    competitor_hotel_id INT NOT NULL,
    comparison_date DATE NOT NULL,
    rating_difference DECIMAL(3,2),
    review_volume_difference INT,
    sentiment_difference DECIMAL(3,2),
    analysis_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    FOREIGN KEY (competitor_hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    INDEX idx_hotel (hotel_id),
    INDEX idx_competitor (competitor_hotel_id),
    INDEX idx_date (comparison_date)
);

-- Tabla para alertas y notificaciones
CREATE TABLE IF NOT EXISTS review_alerts (
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
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE,
    INDEX idx_hotel (hotel_id),
    INDEX idx_type (alert_type),
    INDEX idx_severity (severity),
    INDEX idx_resolved (is_resolved)
);

-- Agregar campos necesarios a la tabla hoteles
ALTER TABLE hoteles
ADD COLUMN IF NOT EXISTS google_place_id VARCHAR(100) UNIQUE,
ADD COLUMN IF NOT EXISTS coordinates_lat DECIMAL(10, 8),
ADD COLUMN IF NOT EXISTS coordinates_lng DECIMAL(11, 8),
ADD COLUMN IF NOT EXISTS total_reviews_count INT DEFAULT 0,
ADD COLUMN IF NOT EXISTS average_rating_overall DECIMAL(3,2) DEFAULT 0.00,
ADD COLUMN IF NOT EXISTS last_extraction_date TIMESTAMP NULL,
ADD INDEX IF NOT EXISTS idx_place_id (google_place_id),
ADD INDEX IF NOT EXISTS idx_coordinates (coordinates_lat, coordinates_lng),
ADD INDEX IF NOT EXISTS idx_rating (average_rating_overall);

-- Vista para estadísticas rápidas por hotel
CREATE OR REPLACE VIEW hotel_review_summary AS
SELECT 
    h.id,
    h.nombre_hotel,
    h.google_place_id,
    h.average_rating_overall,
    COUNT(r.id) as total_reviews,
    COUNT(DISTINCT r.platform) as platforms_count,
    AVG(CASE WHEN ra.sentiment = 'positive' THEN 1 
             WHEN ra.sentiment = 'negative' THEN -1 
             ELSE 0 END) as sentiment_avg,
    MAX(r.scraped_at) as last_review_date,
    COUNT(CASE WHEN ra.sentiment = 'positive' THEN 1 END) as positive_count,
    COUNT(CASE WHEN ra.sentiment = 'negative' THEN 1 END) as negative_count,
    COUNT(CASE WHEN ra.sentiment = 'neutral' THEN 1 END) as neutral_count
FROM hoteles h
LEFT JOIN reviews r ON h.id = r.hotel_id
LEFT JOIN review_analysis ra ON r.id = ra.review_id
WHERE h.activo = 1
GROUP BY h.id, h.nombre_hotel, h.google_place_id, h.average_rating_overall;

-- Procedimiento almacenado para actualizar métricas
DELIMITER //
CREATE OR REPLACE PROCEDURE UpdateHotelMetrics(IN hotel_id_param INT)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Actualizar métricas por plataforma
    INSERT INTO hotel_review_metrics (hotel_id, platform, total_reviews, average_rating, positive_reviews, negative_reviews, neutral_reviews, last_review_date)
    SELECT 
        r.hotel_id,
        r.platform,
        COUNT(*) as total_reviews,
        AVG(r.normalized_rating) as average_rating,
        COUNT(CASE WHEN ra.sentiment = 'positive' THEN 1 END) as positive_reviews,
        COUNT(CASE WHEN ra.sentiment = 'negative' THEN 1 END) as negative_reviews,
        COUNT(CASE WHEN ra.sentiment = 'neutral' THEN 1 END) as neutral_reviews,
        MAX(DATE(r.scraped_at)) as last_review_date
    FROM reviews r
    LEFT JOIN review_analysis ra ON r.id = ra.review_id
    WHERE r.hotel_id = hotel_id_param
    GROUP BY r.hotel_id, r.platform
    ON DUPLICATE KEY UPDATE
        total_reviews = VALUES(total_reviews),
        average_rating = VALUES(average_rating),
        positive_reviews = VALUES(positive_reviews),
        negative_reviews = VALUES(negative_reviews),
        neutral_reviews = VALUES(neutral_reviews),
        last_review_date = VALUES(last_review_date);
    
    -- Actualizar resumen general del hotel
    UPDATE hoteles h
    SET 
        total_reviews_count = (SELECT COUNT(*) FROM reviews WHERE hotel_id = hotel_id_param),
        average_rating_overall = (SELECT AVG(normalized_rating) FROM reviews WHERE hotel_id = hotel_id_param),
        last_extraction_date = NOW()
    WHERE h.id = hotel_id_param;
    
    COMMIT;
END//
DELIMITER ;