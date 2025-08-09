-- Vistas de compatibilidad durante transición

-- Vista para código legacy que usa nombres de columna antiguos
CREATE OR REPLACE VIEW reviews_legacy AS
SELECT 
    id,
    unique_id,
    hotel_id,
    user_name,
    user_name as reviewer_name,
    review_text,
    liked_text,
    disliked_text,
    rating,
    rating as normalized_rating,
    source_platform,
    source_platform as platform,
    property_response,
    property_response as response_from_owner,
    review_date,
    scraped_at,
    created_at,
    updated_at
FROM reviews_unified_final;

