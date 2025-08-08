<?php
/**
 * Adaptador de Esquemas para Tabla Reviews
 * 
 * Maneja la compatibilidad entre diferentes esquemas de la tabla reviews:
 * - Schema A: API Reviews actual (source_platform, property_response, etc.)
 * - Schema B: Sistema Apify (platform, response_from_owner, etc.)
 */

class ReviewsSchemaAdapter 
{
    /**
     * Mapear datos de Apify al esquema estándar
     */
    public static function mapApifyToStandard($apifyData) 
    {
        return [
            // Mapeo de plataforma
            'source_platform' => $apifyData['platform'] ?? null,
            'platform' => $apifyData['platform'] ?? null,
            
            // Mapeo de usuario  
            'user_name' => $apifyData['reviewer_name'] ?? null,
            'reviewer_name' => $apifyData['reviewer_name'] ?? null,
            'user_location' => $apifyData['reviewer_location'] ?? null,
            
            // Mapeo de contenido de reseña
            'review_title' => $apifyData['review_title'] ?? null,
            'review_text' => $apifyData['review_text'] ?? null,
            'liked_text' => $apifyData['positive_text'] ?? $apifyData['liked_text'] ?? null,
            'disliked_text' => $apifyData['negative_text'] ?? $apifyData['disliked_text'] ?? null,
            
            // Mapeo de respuesta del hotel
            'property_response' => $apifyData['response_from_owner'] ?? null,
            'response_from_owner' => $apifyData['response_from_owner'] ?? null,
            
            // Mapeo de calificaciones
            'rating' => $apifyData['normalized_rating'] ?? $apifyData['rating'] ?? null,
            'normalized_rating' => $apifyData['normalized_rating'] ?? $apifyData['rating'] ?? null,
            
            // Mapeo de fechas
            'review_date' => $apifyData['review_date'] ?? null,
            'scraped_at' => $apifyData['scraped_at'] ?? date('Y-m-d H:i:s'),
            
            // Mapeo de metadatos
            'review_language' => $apifyData['language'] ?? $apifyData['review_language'] ?? null,
            'traveler_type_spanish' => $apifyData['traveler_type'] ?? null,
            'helpful_votes' => $apifyData['helpful_votes'] ?? 0,
            
            // Campos específicos de migración
            'extraction_source' => 'apify',
            'platform_review_id' => $apifyData['review_id'] ?? $apifyData['platform_review_id'] ?? null,
            'hotel_id' => $apifyData['hotel_id'] ?? null,
            
            // Generar ID único si no existe
            'unique_id' => $apifyData['unique_id'] ?? self::generateUniqueId($apifyData),
        ];
    }
    
    /**
     * Mapear datos estándar al formato Apify
     */
    public static function mapStandardToApify($standardData) 
    {
        return [
            // Mapeo inverso de plataforma
            'platform' => $standardData['source_platform'] ?? $standardData['platform'] ?? null,
            
            // Mapeo inverso de usuario
            'reviewer_name' => $standardData['user_name'] ?? $standardData['reviewer_name'] ?? null,
            'reviewer_location' => $standardData['user_location'] ?? null,
            
            // Mapeo inverso de contenido
            'review_title' => $standardData['review_title'] ?? null,
            'review_text' => $standardData['review_text'] ?? self::combineReviewTexts($standardData),
            'positive_text' => $standardData['liked_text'] ?? null,
            'negative_text' => $standardData['disliked_text'] ?? null,
            
            // Mapeo inverso de respuesta
            'response_from_owner' => $standardData['property_response'] ?? $standardData['response_from_owner'] ?? null,
            
            // Mapeo inverso de calificaciones
            'normalized_rating' => $standardData['rating'] ?? $standardData['normalized_rating'] ?? null,
            'rating' => $standardData['rating'] ?? $standardData['normalized_rating'] ?? null,
            
            // Mapeo inverso de fechas y metadatos
            'review_date' => $standardData['review_date'] ?? null,
            'scraped_at' => $standardData['scraped_at'] ?? null,
            'language' => $standardData['review_language'] ?? $standardData['language_detected'] ?? null,
            'traveler_type' => $standardData['traveler_type_spanish'] ?? null,
            'helpful_votes' => $standardData['helpful_votes'] ?? 0,
            
            // IDs
            'review_id' => $standardData['platform_review_id'] ?? null,
            'hotel_id' => $standardData['hotel_id'] ?? null,
            'unique_id' => $standardData['unique_id'] ?? null,
        ];
    }
    
    /**
     * Validar datos de entrada antes de mapeo
     */
    public static function validateApifyData($data) 
    {
        $errors = [];
        
        // Campos requeridos para Apify
        $required = ['platform', 'reviewer_name'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = "Campo requerido faltante: $field";
            }
        }
        
        // Validar rating
        if (isset($data['rating']) || isset($data['normalized_rating'])) {
            $rating = $data['rating'] ?? $data['normalized_rating'];
            if (!is_numeric($rating) || $rating < 0 || $rating > 10) {
                $errors[] = "Rating debe ser numérico entre 0 y 10";
            }
        }
        
        // Validar plataforma
        $validPlatforms = ['booking', 'google', 'tripadvisor', 'expedia', 'despegar'];
        if (isset($data['platform']) && !in_array(strtolower($data['platform']), $validPlatforms)) {
            $errors[] = "Plataforma no válida: " . $data['platform'];
        }
        
        return $errors;
    }
    
    /**
     * Normalizar rating a escala 0-10
     */
    public static function normalizeRating($rating, $scale = 10) 
    {
        if (!is_numeric($rating)) return null;
        
        // Si es escala de 5 estrellas, convertir a 10
        if ($scale === 5) {
            return ($rating / 5) * 10;
        }
        
        // Si es escala de 100, convertir a 10
        if ($scale === 100) {
            return $rating / 10;
        }
        
        // Ya es escala de 10
        return (float) $rating;
    }
    
    /**
     * Combinar textos de reseña liked_text y disliked_text
     */
    private static function combineReviewTexts($data) 
    {
        $parts = [];
        
        if (!empty($data['liked_text'])) {
            $parts[] = "Aspectos positivos: " . $data['liked_text'];
        }
        
        if (!empty($data['disliked_text'])) {
            $parts[] = "Aspectos negativos: " . $data['disliked_text'];
        }
        
        return implode("\n\n", $parts);
    }
    
    /**
     * Generar ID único basado en datos de la reseña
     */
    private static function generateUniqueId($data) 
    {
        $components = [
            $data['platform'] ?? 'unknown',
            $data['reviewer_name'] ?? 'anonymous', 
            $data['review_date'] ?? date('Y-m-d'),
            $data['hotel_id'] ?? 'no-hotel'
        ];
        
        return md5(implode('|', $components));
    }
    
    /**
     * Preparar datos para inserción masiva (bulk insert)
     */
    public static function prepareBulkInsert($apifyDataArray) 
    {
        $prepared = [];
        $errors = [];
        
        foreach ($apifyDataArray as $index => $data) {
            // Validar datos
            $validation = self::validateApifyData($data);
            if (!empty($validation)) {
                $errors["row_$index"] = $validation;
                continue;
            }
            
            // Mapear datos
            $mapped = self::mapApifyToStandard($data);
            $prepared[] = $mapped;
        }
        
        return [
            'data' => $prepared,
            'errors' => $errors,
            'processed' => count($prepared),
            'failed' => count($errors)
        ];
    }
    
    /**
     * Crear query de inserción con datos mapeados
     */
    public static function buildInsertQuery($mappedData) 
    {
        if (empty($mappedData)) return null;
        
        $columns = array_keys($mappedData[0]);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO reviews (" . implode(', ', $columns) . ") VALUES ";
        $valueGroups = [];
        
        foreach ($mappedData as $row) {
            $valueGroups[] = "(" . implode(', ', $placeholders) . ")";
        }
        
        $sql .= implode(', ', $valueGroups);
        
        return [
            'sql' => $sql,
            'values' => array_merge(...array_map('array_values', $mappedData))
        ];
    }
    
    /**
     * Obtener estadísticas de compatibilidad
     */
    public static function getCompatibilityStats($pdo) 
    {
        try {
            // Verificar columnas existentes
            $stmt = $pdo->query("DESCRIBE reviews");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $schemaA = ['source_platform', 'property_response', 'liked_text', 'disliked_text', 'user_name'];
            $schemaB = ['platform', 'response_from_owner', 'review_text', 'reviewer_name', 'normalized_rating'];
            
            $schemaA_coverage = count(array_intersect($schemaA, $columns)) / count($schemaA) * 100;
            $schemaB_coverage = count(array_intersect($schemaB, $columns)) / count($schemaB) * 100;
            
            // Contar registros por origen
            $stmt = $pdo->query("SELECT extraction_source, COUNT(*) as count FROM reviews GROUP BY extraction_source");
            $sources = $stmt->fetchAll();
            
            return [
                'schema_a_coverage' => round($schemaA_coverage, 1),
                'schema_b_coverage' => round($schemaB_coverage, 1),
                'total_columns' => count($columns),
                'sources' => $sources,
                'unified' => $schemaA_coverage >= 100 && $schemaB_coverage >= 100
            ];
            
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}

/**
 * Ejemplo de uso del adaptador
 */
class ReviewsSchemaAdapterExample 
{
    public static function demonstrateUsage() 
    {
        echo "📋 EJEMPLO DE USO - ReviewsSchemaAdapter\n";
        echo str_repeat("-", 50) . "\n";
        
        // Datos simulados de Apify
        $apifyData = [
            'platform' => 'booking',
            'reviewer_name' => 'Juan Pérez',
            'review_text' => 'Hotel excelente, muy limpio y buen servicio',
            'normalized_rating' => 9.0,
            'review_date' => '2024-12-15',
            'hotel_id' => 6
        ];
        
        echo "Datos originales de Apify:\n";
        print_r($apifyData);
        
        // Mapear a esquema estándar
        $standardData = ReviewsSchemaAdapter::mapApifyToStandard($apifyData);
        
        echo "\nDatos mapeados a esquema estándar:\n";
        print_r($standardData);
        
        // Validar datos
        $errors = ReviewsSchemaAdapter::validateApifyData($apifyData);
        echo "\nValidación: " . (empty($errors) ? "✅ Sin errores" : "❌ " . implode(", ", $errors)) . "\n";
    }
}

// Ejecutar ejemplo si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    ReviewsSchemaAdapterExample::demonstrateUsage();
}
?>