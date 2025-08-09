-- ESQUEMA FINAL UNIFICADO DE REVIEWS
-- Basado en análisis de 13 tablas existentes y sus inconsistencias
-- Fecha: 2025-08-09

-- ==================================================
-- TABLA PRINCIPAL: reviews_final
-- ==================================================
CREATE TABLE IF NOT EXISTS reviews_final (
    -- Primary Key
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID único global para evitar duplicados',
    
    -- Referencias
    hotel_id INT UNSIGNED NOT NULL COMMENT 'ID del hotel (FK a hoteles.id)',
    extraction_run_id VARCHAR(100) NULL COMMENT 'ID del run de extracción',
    platform_review_id VARCHAR(255) NULL COMMENT 'ID de la review en la plataforma original',
    platform_hotel_id VARCHAR(255) NULL COMMENT 'ID del hotel en la plataforma',
    
    -- USUARIO (nombres normalizados)
    user_name VARCHAR(255) NULL COMMENT 'Nombre principal del usuario/revisor',
    reviewer_name VARCHAR(255) NULL COMMENT 'Alias para compatibilidad (deprecado)',
    user_location VARCHAR(255) NULL COMMENT 'Ubicación del usuario',
    traveler_type VARCHAR(100) NULL COMMENT 'Tipo de viajero',
    
    -- CONTENIDO DE LA REVIEW (unificado)
    review_title VARCHAR(500) NULL COMMENT 'Título de la review',
    review_text TEXT NULL COMMENT 'Texto completo de la review (campo principal)',
    liked_text TEXT NULL COMMENT 'Aspectos positivos/que gustaron',
    disliked_text TEXT NULL COMMENT 'Aspectos negativos/que no gustaron',
    
    -- RATING (normalizado)
    rating DECIMAL(3,1) NULL COMMENT 'Rating original de la plataforma',
    normalized_rating DECIMAL(4,2) NULL COMMENT 'Rating normalizado 0-10 para consistencia',
    
    -- PLATAFORMA (unificado)
    source_platform VARCHAR(50) NOT NULL COMMENT 'Plataforma principal (booking, tripadvisor, etc)',
    platform VARCHAR(50) NULL COMMENT 'Alias para compatibilidad (deprecado)',
    
    -- RESPUESTA DEL HOTEL (unificado)
    property_response TEXT NULL COMMENT 'Respuesta del hotel (campo principal)',
    response_from_owner TEXT NULL COMMENT 'Alias para compatibilidad (deprecado)',
    hotel_response TEXT NULL COMMENT 'Alias para compatibilidad con reviews_unified',
    
    -- FECHAS Y TIEMPOS
    review_date DATE NULL COMMENT 'Fecha cuando se escribió la review',
    check_in_date DATE NULL COMMENT 'Fecha de check-in',
    check_out_date DATE NULL COMMENT 'Fecha de check-out',
    scraped_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Cuando se extrajo la data',
    
    -- METADATOS
    helpful_votes INT DEFAULT 0 COMMENT 'Votos de utilidad',
    review_language VARCHAR(10) DEFAULT 'auto' COMMENT 'Idioma detectado',
    sentiment_score DECIMAL(3,2) NULL COMMENT 'Score de sentimiento -1 a 1',
    is_verified BOOLEAN DEFAULT FALSE COMMENT 'Si la review está verificada',
    was_translated BOOLEAN DEFAULT FALSE COMMENT 'Si fue traducida',
    
    -- INFORMACIÓN ADICIONAL
    room_info TEXT NULL COMMENT 'Información de la habitación',
    number_of_nights INT NULL COMMENT 'Número de noches de estancia',
    images LONGTEXT NULL COMMENT 'JSON con imágenes de la review',
    tags LONGTEXT NULL COMMENT 'JSON con tags/categorías',
    reviewer_info LONGTEXT NULL COMMENT 'JSON con info adicional del revisor',
    
    -- ESTADO Y PROCESAMIENTO
    extraction_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',
    extraction_source ENUM('apify', 'manual', 'api', 'bulk') DEFAULT 'apify' COMMENT 'Fuente de extracción',
    processed_at TIMESTAMP NULL COMMENT 'Cuando se procesó/analizó',
    
    -- COMPATIBILIDAD CON LEGACY (campos deprecados pero mantenidos)
    hotel_name VARCHAR(255) NULL COMMENT 'LEGACY: Usar hotel_id en su lugar',
    hotel_destination VARCHAR(100) NULL COMMENT 'LEGACY: Obtener de tabla hoteles',
    source_url TEXT NULL COMMENT 'URL original donde se encontró',
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- ÍNDICES PARA PERFORMANCE
    INDEX idx_hotel_platform (hotel_id, source_platform),
    INDEX idx_review_date (review_date),
    INDEX idx_rating (rating, normalized_rating),
    INDEX idx_scraped_at (scraped_at),
    INDEX idx_extraction_run (extraction_run_id),
    INDEX idx_sentiment (sentiment_score),
    INDEX idx_status (extraction_status),
    INDEX idx_verified (is_verified),
    
    -- Foreign Keys
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tabla final unificada de reviews con compatibilidad legacy';

-- ==================================================
-- TRIGGERS PARA MANTENER COMPATIBILIDAD
-- ==================================================
DELIMITER //

-- Trigger BEFORE INSERT para sincronizar campos alias
CREATE TRIGGER reviews_final_before_insert 
BEFORE INSERT ON reviews_final
FOR EACH ROW 
BEGIN
    -- Sincronizar user_name con reviewer_name
    IF NEW.user_name IS NULL AND NEW.reviewer_name IS NOT NULL THEN
        SET NEW.user_name = NEW.reviewer_name;
    ELSEIF NEW.reviewer_name IS NULL AND NEW.user_name IS NOT NULL THEN
        SET NEW.reviewer_name = NEW.user_name;
    END IF;
    
    -- Sincronizar source_platform con platform
    IF NEW.source_platform IS NULL AND NEW.platform IS NOT NULL THEN
        SET NEW.source_platform = NEW.platform;
    ELSEIF NEW.platform IS NULL AND NEW.source_platform IS NOT NULL THEN
        SET NEW.platform = NEW.source_platform;
    END IF;
    
    -- Sincronizar property_response con aliases
    IF NEW.property_response IS NULL AND NEW.response_from_owner IS NOT NULL THEN
        SET NEW.property_response = NEW.response_from_owner;
    ELSEIF NEW.property_response IS NULL AND NEW.hotel_response IS NOT NULL THEN
        SET NEW.property_response = NEW.hotel_response;
    END IF;
    
    -- Si no hay response_from_owner pero sí property_response, sincronizar
    IF NEW.response_from_owner IS NULL AND NEW.property_response IS NOT NULL THEN
        SET NEW.response_from_owner = NEW.property_response;
    END IF;
    
    -- Si no hay hotel_response pero sí property_response, sincronizar  
    IF NEW.hotel_response IS NULL AND NEW.property_response IS NOT NULL THEN
        SET NEW.hotel_response = NEW.property_response;
    END IF;
    
    -- Auto-generar normalized_rating si no existe
    IF NEW.normalized_rating IS NULL AND NEW.rating IS NOT NULL THEN
        SET NEW.normalized_rating = NEW.rating;
    END IF;
END//

-- Trigger BEFORE UPDATE para sincronizar cambios
CREATE TRIGGER reviews_final_before_update
BEFORE UPDATE ON reviews_final  
FOR EACH ROW
BEGIN
    -- Misma lógica que en INSERT
    IF NEW.user_name != OLD.user_name THEN
        SET NEW.reviewer_name = NEW.user_name;
    ELSEIF NEW.reviewer_name != OLD.reviewer_name THEN
        SET NEW.user_name = NEW.reviewer_name;
    END IF;
    
    IF NEW.source_platform != OLD.source_platform THEN
        SET NEW.platform = NEW.source_platform;
    ELSEIF NEW.platform != OLD.platform THEN
        SET NEW.source_platform = NEW.platform;
    END IF;
    
    IF NEW.property_response != OLD.property_response THEN
        SET NEW.response_from_owner = NEW.property_response;
        SET NEW.hotel_response = NEW.property_response;
    END IF;
END//

DELIMITER ;

-- ==================================================
-- VISTAS DE COMPATIBILIDAD PARA TABLAS LEGACY
-- ==================================================

-- Vista para compatibilidad con 'reviews' legacy
CREATE OR REPLACE VIEW reviews_legacy_view AS
SELECT 
    id,
    unique_id,
    hotel_id,
    user_name,
    reviewer_name,
    user_location,
    review_title,
    review_text,
    liked_text,
    disliked_text,
    rating,
    normalized_rating,
    source_platform,
    platform,
    property_response,
    response_from_owner,
    review_date,
    scraped_at,
    hotel_name,
    hotel_destination,
    extraction_run_id,
    extraction_status,
    is_verified,
    helpful_votes,
    review_language,
    sentiment_score,
    created_at,
    updated_at
FROM reviews_final;

-- Vista para compatibilidad con 'reviews_unified'
CREATE OR REPLACE VIEW reviews_unified_compat AS
SELECT 
    id,
    unique_id,
    hotel_id,
    user_name as guest_name,
    source_platform as platform_name,
    normalized_rating as unified_rating,
    CONCAT_WS(' ', review_text, liked_text, disliked_text) as full_review_text,
    property_response as hotel_response,
    extraction_source as data_source,
    scraped_at,
    review_date,
    sentiment_score,
    review_language as language,
    is_verified,
    helpful_votes,
    tags
FROM reviews_final;

-- Vista para compatibilidad con 'recent_reviews'
CREATE OR REPLACE VIEW recent_reviews_compat AS
SELECT 
    hotel_name,
    hotel_destination,
    user_name,
    rating,
    review_title,
    liked_text,
    disliked_text,
    review_date,
    traveler_type as traveler_type_spanish
FROM reviews_final 
WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 30 DAY);

-- ==================================================
-- FUNCIÓN AUXILIAR PARA INSERCIÓN SEGURA
-- ==================================================
DELIMITER //

CREATE FUNCTION insert_review_safe(
    p_unique_id VARCHAR(255),
    p_hotel_id INT,
    p_user_name VARCHAR(255),
    p_platform VARCHAR(50),
    p_rating DECIMAL(3,1),
    p_review_text TEXT,
    p_review_date DATE
) RETURNS BIGINT
READS SQL DATA
DETERMINISTIC
BEGIN
    DECLARE review_id BIGINT DEFAULT 0;
    DECLARE duplicate_count INT DEFAULT 0;
    
    -- Verificar duplicados
    SELECT COUNT(*) INTO duplicate_count 
    FROM reviews_final 
    WHERE unique_id = p_unique_id;
    
    -- Insertar solo si no existe
    IF duplicate_count = 0 THEN
        INSERT INTO reviews_final (
            unique_id,
            hotel_id,
            user_name,
            source_platform,
            rating,
            review_text,
            review_date
        ) VALUES (
            p_unique_id,
            p_hotel_id,
            p_user_name,
            p_platform,
            p_rating,
            p_review_text,
            p_review_date
        );
        
        SET review_id = LAST_INSERT_ID();
    ELSE
        -- Retornar ID existente
        SELECT id INTO review_id 
        FROM reviews_final 
        WHERE unique_id = p_unique_id;
    END IF;
    
    RETURN review_id;
END//

DELIMITER ;

-- ==================================================
-- COMENTARIOS FINALES
-- ==================================================

/*
ESQUEMA FINAL UNIFICADO - CARACTERÍSTICAS:

1. COLUMNAS PRINCIPALES (usar estas):
   - user_name (en lugar de reviewer_name)
   - source_platform (en lugar de platform)
   - property_response (en lugar de response_from_owner)
   - review_text (campo principal de texto)
   - normalized_rating (rating normalizado 0-10)

2. COMPATIBILIDAD LEGACY:
   - Mantiene campos deprecados para no romper código existente
   - Triggers automáticos sincronizan cambios
   - Vistas proporcionan acceso compatible

3. PERFORMANCE:
   - Índices optimizados para consultas frecuentes
   - Foreign keys para integridad referencial
   - Charset UTF-8 para soporte internacional

4. FLEXIBILIDAD:
   - Campos JSON para metadatos extensibles
   - ENUMs para valores controlados
   - Campos NULL opcionales

5. MIGRACIÓN:
   - Usar función insert_review_safe() para evitar duplicados
   - Vistas permiten migración gradual
   - Triggers mantienen consistencia automática

PRÓXIMOS PASOS:
1. Ejecutar este SQL en producción
2. Crear script de migración de datos
3. Actualizar código para usar columnas principales
4. Crear adapters para APIs existentes
*/