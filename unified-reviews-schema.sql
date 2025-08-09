-- Esquema unificado para tabla reviews
-- Generado: 2025-08-09 01:13:52

CREATE TABLE IF NOT EXISTS reviews_unified_final (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID único global',
    
    -- Referencias
    hotel_id INT UNSIGNED NOT NULL,
    extraction_run_id VARCHAR(100) NULL,
    platform_review_id VARCHAR(255) NULL,
    
    -- Datos del usuario (columnas normalizadas)
    user_name VARCHAR(255) NULL,
    reviewer_name VARCHAR(255) NULL COMMENT 'Alias de user_name para compatibilidad',
    user_location VARCHAR(255) NULL,
    
    -- Contenido de la review (columnas unificadas)
    review_title VARCHAR(500) NULL,
    review_text TEXT NULL COMMENT 'Texto completo de la review',
    liked_text TEXT NULL COMMENT 'Aspectos positivos',
    disliked_text TEXT NULL COMMENT 'Aspectos negativos',
    
    -- Rating (normalizado)
    rating DECIMAL(3,1) NULL COMMENT 'Rating original',
    normalized_rating DECIMAL(3,1) NULL COMMENT 'Rating normalizado 0-10',
    
    -- Plataforma (unificado)
    source_platform VARCHAR(50) NOT NULL COMMENT 'Plataforma principal',
    platform VARCHAR(50) NULL COMMENT 'Alias para compatibilidad',
    
    -- Respuesta del hotel (unificado)
    property_response TEXT NULL COMMENT 'Respuesta del hotel',
    response_from_owner TEXT NULL COMMENT 'Alias para compatibilidad',
    
    -- Metadatos
    review_date DATE NULL,
    scraped_at TIMESTAMP NULL,
    helpful_votes INT DEFAULT 0,
    review_language VARCHAR(10) DEFAULT 'auto',
    extraction_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_hotel_platform (hotel_id, source_platform),
    INDEX idx_review_date (review_date),
    INDEX idx_scraped_at (scraped_at),
    INDEX idx_rating (rating),
    INDEX idx_extraction_run (extraction_run_id),
    
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Triggers para mantener columnas alias sincronizadas
DELIMITER //
CREATE TRIGGER reviews_before_insert BEFORE INSERT ON reviews_unified_final
FOR EACH ROW BEGIN
    SET NEW.reviewer_name = COALESCE(NEW.user_name, NEW.reviewer_name);
    SET NEW.platform = COALESCE(NEW.source_platform, NEW.platform);
    SET NEW.response_from_owner = COALESCE(NEW.property_response, NEW.response_from_owner);
END//
DELIMITER ;

