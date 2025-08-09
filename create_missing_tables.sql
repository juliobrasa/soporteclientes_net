-- SQL para crear tablas faltantes en el esquema de base de datos
-- Ejecutar este script para resolver incompatibilidades de esquema

-- Crear tabla apify_extraction_runs si no existe
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
  `started_at` timestamp NULL DEFAULT NULL,
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

-- Agregar columnas faltantes a extraction_jobs si no existen
ALTER TABLE `extraction_jobs` 
  ADD COLUMN IF NOT EXISTS `hotel_id` int(11) NOT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `progress` int(11) DEFAULT 0 AFTER `status`,
  ADD COLUMN IF NOT EXISTS `reviews_extracted` int(11) DEFAULT 0 AFTER `progress`,
  ADD COLUMN IF NOT EXISTS `completed_at` timestamp NULL DEFAULT NULL AFTER `updated_at`,
  ADD COLUMN IF NOT EXISTS `platforms` JSON DEFAULT NULL AFTER `hotel_id`;

-- Agregar índices a extraction_jobs si no existen
ALTER TABLE `extraction_jobs` 
  ADD INDEX IF NOT EXISTS `hotel_id` (`hotel_id`),
  ADD INDEX IF NOT EXISTS `status` (`status`),
  ADD INDEX IF NOT EXISTS `created_at` (`created_at`);

-- Crear tabla debug_logs para logging estructurado si no existe
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

-- Crear tabla reviews unificada con todos los campos necesarios
CREATE TABLE IF NOT EXISTS `reviews_unified` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(255) NOT NULL,
  `hotel_id` int(11) NOT NULL,
  `hotel_name` varchar(255) DEFAULT NULL,
  `hotel_destination` varchar(255) DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `reviewer_name` varchar(255) DEFAULT NULL COMMENT 'Alias para user_name',
  `review_date` date DEFAULT NULL,
  `rating` decimal(2,1) DEFAULT NULL,
  `normalized_rating` decimal(2,1) DEFAULT NULL COMMENT 'Alias para rating',
  `review_title` varchar(500) DEFAULT NULL,
  `review_text` text DEFAULT NULL,
  `liked_text` text DEFAULT NULL,
  `disliked_text` text DEFAULT NULL,
  `source_platform` varchar(50) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL COMMENT 'Alias para source_platform',
  `platform_review_id` varchar(255) DEFAULT NULL,
  `extraction_run_id` varchar(255) DEFAULT NULL,
  `extraction_status` varchar(50) DEFAULT NULL,
  `scraped_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `helpful_votes` int(11) DEFAULT 0,
  `review_language` varchar(10) DEFAULT 'auto',
  `traveler_type_spanish` varchar(100) DEFAULT NULL,
  `was_translated` tinyint(1) DEFAULT 0,
  `number_of_nights` int(11) DEFAULT NULL,
  `property_response` text DEFAULT NULL,
  `response_from_owner` text DEFAULT NULL COMMENT 'Alias para property_response',
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

-- Crear vistas de compatibilidad si queremos mantener ambos nombres
CREATE OR REPLACE VIEW `reviews_legacy` AS 
SELECT 
  `id`,
  `unique_id`,
  `hotel_id`,
  `hotel_name`,
  `hotel_destination`,
  `user_name`,
  `review_date`,
  `rating`,
  `review_title`,
  `liked_text`,
  `disliked_text`,
  `source_platform`,
  `platform_review_id`,
  `extraction_run_id`,
  `extraction_status`,
  `scraped_at`,
  `helpful_votes`,
  `review_language`,
  `traveler_type_spanish`,
  `was_translated`,
  `number_of_nights`,
  `property_response`,
  `created_at`,
  `updated_at`
FROM `reviews_unified`;

-- Mensaje de confirmación
SELECT 'Esquema de base de datos actualizado correctamente' as mensaje;