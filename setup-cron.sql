-- ==========================================================================
-- TABLA PARA LOG DE EJECUCIONES CRON
-- ==========================================================================

CREATE TABLE IF NOT EXISTS cron_extraction_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    execution_date DATETIME NOT NULL,
    total_reviews_extracted INT DEFAULT 0,
    hotels_processed INT DEFAULT 0,
    errors_log TEXT,
    execution_time_seconds INT,
    status ENUM('running', 'completed', 'failed') DEFAULT 'running',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Índices para optimizar consultas
CREATE INDEX idx_cron_log_date ON cron_extraction_log(execution_date);
CREATE INDEX idx_cron_log_status ON cron_extraction_log(status);

-- ==========================================================================
-- CONFIGURACIÓN DE CRON JOBS
-- ==========================================================================

-- Para configurar el cron job, ejecuta este comando en el servidor:
-- crontab -e

-- Luego agrega estas líneas:
-- # Extracción diaria de reseñas a las 6:00 AM
-- 0 6 * * * /usr/bin/php /root/soporteclientes_net/cron-extractor.php >> /root/soporteclientes_net/logs/cron.log 2>&1

-- # Verificación de estado cada 4 horas
-- 0 */4 * * * /usr/bin/php /root/soporteclientes_net/health-check.php >> /root/soporteclientes_net/logs/health.log 2>&1

-- ==========================================================================
-- CONFIGURACIÓN DE LÍMITES APIFY
-- ==========================================================================

-- Tabla para configurar límites dinámicos por hotel
CREATE TABLE IF NOT EXISTS extraction_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    daily_limit INT DEFAULT 100,
    platform_limits JSON,
    last_extraction DATETIME,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
);

-- Insertar límites por defecto para hoteles existentes
INSERT INTO extraction_limits (hotel_id, daily_limit, platform_limits)
SELECT id, 100, '{"booking": 40, "google": 30, "tripadvisor": 30}'
FROM hoteles 
WHERE activo = 1
ON DUPLICATE KEY UPDATE hotel_id = hotel_id;

-- ==========================================================================
-- VISTA PARA MONITOREO
-- ==========================================================================

CREATE OR REPLACE VIEW extraction_summary AS
SELECT 
    h.nombre_hotel,
    h.id as hotel_id,
    COUNT(r.id) as total_reviews,
    COUNT(CASE WHEN DATE(r.scraped_at) = CURDATE() THEN 1 END) as today_reviews,
    COUNT(CASE WHEN r.scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 END) as week_reviews,
    MAX(r.scraped_at) as last_extraction,
    el.daily_limit,
    CASE 
        WHEN MAX(r.scraped_at) >= CURDATE() THEN 'UPDATED'
        WHEN MAX(r.scraped_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 'RECENT'
        ELSE 'OUTDATED'
    END as status
FROM hoteles h
LEFT JOIN reviews r ON h.id = r.hotel_id
LEFT JOIN extraction_limits el ON h.id = el.hotel_id
WHERE h.activo = 1
GROUP BY h.id, h.nombre_hotel, el.daily_limit
ORDER BY h.nombre_hotel;

-- ==========================================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ==========================================================================

DELIMITER //

CREATE OR REPLACE PROCEDURE GetExtractionStats()
BEGIN
    SELECT 
        'Total Hotels' as metric,
        COUNT(*) as value
    FROM hoteles WHERE activo = 1
    
    UNION ALL
    
    SELECT 
        'Reviews Today' as metric,
        COUNT(*) as value
    FROM reviews WHERE DATE(scraped_at) = CURDATE()
    
    UNION ALL
    
    SELECT 
        'Reviews This Week' as metric,
        COUNT(*) as value
    FROM reviews WHERE scraped_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 
        'Last Extraction' as metric,
        TIMESTAMPDIFF(HOUR, MAX(scraped_at), NOW()) as value
    FROM reviews;
END//

DELIMITER ;

-- ==========================================================================
-- DATOS DE PRUEBA Y CONFIGURACIÓN
-- ==========================================================================

-- Insertar configuración de ejemplo
INSERT INTO extraction_limits (hotel_id, daily_limit, platform_limits) VALUES
(1, 150, '{"booking": 60, "google": 45, "tripadvisor": 45}'),
(2, 100, '{"booking": 40, "google": 30, "tripadvisor": 30}'),
(3, 200, '{"booking": 80, "google": 60, "tripadvisor": 60}')
ON DUPLICATE KEY UPDATE daily_limit = VALUES(daily_limit);

-- Log inicial
INSERT INTO cron_extraction_log (execution_date, total_reviews_extracted, hotels_processed, status)
VALUES (NOW(), 0, 0, 'completed');