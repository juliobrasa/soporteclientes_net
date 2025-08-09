-- Script para eliminar todos los datos demo/ejemplo de la base de datos
-- Ejecutar con precaución: esto eliminará datos que puedan ser de prueba

-- 1. Eliminar reseñas con nombres genéricos o IDs de ejemplo
DELETE FROM reviews WHERE 
    user_name LIKE '%Anónimo%' OR
    user_name LIKE '%Usuario%' OR
    user_name LIKE '%Ejemplo%' OR
    unique_id LIKE '%booking_%' OR
    unique_id LIKE '%example_%' OR
    unique_id LIKE '%demo_%' OR
    unique_id LIKE '%test_%' OR
    hotel_name LIKE '%Ejemplo%' OR
    hotel_name LIKE '%Test%' OR
    hotel_name LIKE '%Demo%' OR
    review_text = '' OR
    (review_text IS NULL AND liked_text = '' AND disliked_text = '');

-- 2. Eliminar extraction jobs de prueba
DELETE FROM extraction_jobs WHERE 
    created_at > '2025-01-01' AND
    (platforms LIKE '%test%' OR 
     platforms LIKE '%demo%' OR
     platforms LIKE '%ejemplo%');

-- 3. Eliminar runs de Apify de prueba
DELETE FROM apify_extraction_runs WHERE 
    apify_run_id LIKE '%test_%' OR
    apify_run_id LIKE '%demo_%' OR
    apify_run_id LIKE '%example_%';

-- 4. Limpiar logs de debug antiguos (opcional - mantener solo últimos 7 días)
DELETE FROM debug_logs WHERE 
    created_at < DATE_SUB(NOW(), INTERVAL 7 DAY) OR
    message LIKE '%ejemplo%' OR
    message LIKE '%test%' OR
    message LIKE '%demo%';

-- 5. Verificar qué queda después de la limpieza
SELECT 'Reviews restantes' as tabla, COUNT(*) as cantidad FROM reviews
UNION ALL
SELECT 'Jobs restantes' as tabla, COUNT(*) as cantidad FROM extraction_jobs
UNION ALL
SELECT 'Runs restantes' as tabla, COUNT(*) as cantidad FROM apify_extraction_runs
UNION ALL
SELECT 'Logs restantes' as tabla, COUNT(*) as cantidad FROM debug_logs;

-- 6. Mostrar muestra de lo que queda
SELECT 'Muestra reviews' as tipo, user_name, hotel_name, source_platform, created_at FROM reviews LIMIT 5;