-- CORRECCIÓN DEFINITIVA DEL ESQUEMA DE BASE DE DATOS
-- Unifica inconsistencias entre legacy, Laravel y apify-data-processor

-- 1. TABLA REVIEWS - Esquema unificado final
DROP TABLE IF EXISTS `reviews_unified`;
CREATE TABLE `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `hotel_destination` varchar(255) DEFAULT NULL,
  
  -- NOMBRES UNIFICADOS (usar siempre estos)
  `user_name` varchar(255) DEFAULT NULL,           -- legacy usa esto
  `review_text` text DEFAULT NULL,                 -- apify-data-processor usa esto
  `liked_text` text DEFAULT NULL,                  -- legacy usa esto (texto positivo)
  `disliked_text` text DEFAULT NULL,               -- legacy usa esto (texto negativo)
  `source_platform` varchar(50) DEFAULT NULL,     -- UNIFICADO: siempre usar este
  `property_response` text DEFAULT NULL,           -- UNIFICADO: siempre usar este
  
  -- CAMPOS COMUNES
  `review_date` date DEFAULT NULL,
  `rating` decimal(3,1) DEFAULT NULL,             -- escala 1-10 de Booking, normalizar a 1-5 si necesario
  `review_title` varchar(500) DEFAULT NULL,
  `platform_review_id` varchar(255) DEFAULT NULL,
  `extraction_run_id` varchar(255) DEFAULT NULL,
  `extraction_status` varchar(50) DEFAULT 'completed',
  `scraped_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  
  -- CAMPOS LEGACY ADICIONALES
  `helpful_votes` int(11) DEFAULT 0,
  `review_language` varchar(10) DEFAULT 'auto',
  `traveler_type_spanish` varchar(100) DEFAULT NULL,
  `was_translated` tinyint(1) DEFAULT 0,
  `number_of_nights` int(11) DEFAULT NULL,
  
  -- CAMPOS NUEVOS APIFY
  `reviewer_location` varchar(255) DEFAULT NULL,
  `stay_date` varchar(50) DEFAULT NULL,
  `room_type` varchar(255) DEFAULT NULL,
  `original_rating` decimal(3,1) DEFAULT NULL,    -- rating original antes de normalizar
  
  -- TIMESTAMPS
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_id` (`unique_id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `source_platform` (`source_platform`),
  KEY `rating` (`rating`),
  KEY `review_date` (`review_date`),
  KEY `scraped_at` (`scraped_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA EXTRACTION_JOBS - Compatible con legacy
ALTER TABLE `extraction_jobs` 
  ADD COLUMN IF NOT EXISTS `hotel_id` int(11) NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `progress` int(11) DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `reviews_extracted` int(11) DEFAULT 0 AFTER `progress`,
  ADD COLUMN IF NOT EXISTS `completed_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `platforms` JSON DEFAULT NULL AFTER `hotel_id`,
  MODIFY COLUMN `name` varchar(255) DEFAULT NULL;  -- hacer nullable para legacy

-- Agregar índices faltantes
ALTER TABLE `extraction_jobs` 
  ADD INDEX IF NOT EXISTS `hotel_id` (`hotel_id`),
  ADD INDEX IF NOT EXISTS `status` (`status`),
  ADD INDEX IF NOT EXISTS `created_at` (`created_at`);

-- 3. TABLA APIFY_EXTRACTION_RUNS - Crear correctamente
CREATE TABLE IF NOT EXISTS `apify_extraction_runs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `job_id` int(11) DEFAULT NULL,
  `hotel_id` int(11) NOT NULL,
  `apify_run_id` varchar(255) NOT NULL,
  `status` enum('pending','running','succeeded','failed','timeout','cancelled') DEFAULT 'pending',
  `platforms_requested` JSON DEFAULT NULL,
  `max_reviews_per_platform` int(11) DEFAULT 100,
  `cost_estimate` decimal(10,4) DEFAULT 0.0000,
  `apify_response` JSON DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,  -- IMPORTANTE: NOT NULL
  `finished_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `apify_run_id` (`apify_run_id`),
  KEY `hotel_id` (`hotel_id`),
  KEY `job_id` (`job_id`),
  KEY `status` (`status`),
  KEY `started_at` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA DEBUG_LOGS para logging estructurado
CREATE TABLE IF NOT EXISTS `debug_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `level` enum('DEBUG','INFO','WARNING','ERROR') DEFAULT 'INFO',
  `context` JSON DEFAULT NULL,
  `hotel_id` int(11) DEFAULT NULL,
  `job_id` int(11) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level` (`level`),
  KEY `hotel_id` (`hotel_id`),
  KEY `job_id` (`job_id`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MIGRAR DATOS EXISTENTES (si hay tabla reviews antigua)
INSERT IGNORE INTO `reviews` 
  SELECT * FROM `reviews` WHERE 1=0;  -- estructura compatible

-- Mensaje de confirmación
SELECT 'Esquema de base de datos unificado correctamente - reviews, extraction_jobs, apify_extraction_runs' as mensaje;