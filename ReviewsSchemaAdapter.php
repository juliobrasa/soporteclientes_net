<?php
/**
 * Adaptador de Esquema de Reviews
 * Maneja la normalización entre esquemas legacy y el esquema final unificado
 * 
 * @version 2.0 
 * @date 2025-08-09
 */

class ReviewsSchemaAdapter 
{
    private $pdo;
    private $useUnifiedSchema;
    
    // Mapeo de columnas: legacy -> unificado
    private const COLUMN_MAPPING = [
        // Nombres de usuario
        'reviewer_name' => 'user_name',
        'guest_name' => 'user_name',
        'Nombre del usuario' => 'user_name',
        
        // Plataforma
        'platform' => 'source_platform', 
        'platform_name' => 'source_platform',
        
        // Texto de review
        'full_review_text' => 'review_text',
        'comment' => 'review_text',
        'content' => 'review_text',
        'Reseña buena' => 'liked_text',
        'Reseña mala' => 'disliked_text',
        'Titulo' => 'review_title',
        
        // Rating  
        'unified_rating' => 'normalized_rating',
        'score' => 'rating',
        'puntuacion' => 'rating',
        
        // Respuesta del hotel
        'response_from_owner' => 'property_response',
        'hotel_response' => 'property_response',
        'contestado' => 'property_response',
        
        // Fechas
        'Fecha' => 'review_date',
        
        // Otros campos legacy
        'id hotel' => 'platform_hotel_id',
        'residencia' => 'user_location'
    ];
    
    // Plataformas válidas
    private const VALID_PLATFORMS = [
        'booking', 'tripadvisor', 'expedia', 'hotels', 'agoda', 'google', 'airbnb'
    ];
    
    public function __construct($pdo, $useUnifiedSchema = true) 
    {
        $this->pdo = $pdo;
        $this->useUnifiedSchema = $useUnifiedSchema;
    }
    
    /**
     * Normalizar datos de review para inserción
     */
    public function normalizeReviewData($rawData, $sourceTable = null) 
    {
        $normalized = [];
        
        foreach ($rawData as $key => $value) {
            // Mapear nombre de columna
            $normalizedKey = $this->mapColumnName($key);
            $normalized[$normalizedKey] = $this->normalizeValue($normalizedKey, $value);
        }
        
        // Validaciones y correcciones
        $normalized = $this->validateAndFix($normalized);
        
        // Añadir metadatos
        if (!isset($normalized['unique_id'])) {
            $normalized['unique_id'] = $this->generateUniqueId($normalized);
        }
        
        if (!isset($normalized['scraped_at'])) {
            $normalized['scraped_at'] = date('Y-m-d H:i:s');
        }
        
        return $normalized;
    }
    
    /**
     * Mapear nombre de columna legacy a unificado
     */
    private function mapColumnName($legacyName) 
    {
        return self::COLUMN_MAPPING[$legacyName] ?? $legacyName;
    }
    
    /**
     * Normalizar valor según el tipo de campo
     */
    private function normalizeValue($fieldName, $value) 
    {
        if ($value === null || $value === '') {
            return null;
        }
        
        switch ($fieldName) {
            case 'source_platform':
                return $this->normalizePlatform($value);
                
            case 'rating':
            case 'normalized_rating':
                return $this->normalizeRating($value, $fieldName === 'normalized_rating');
                
            case 'review_date':
                return $this->normalizeDate($value);
                
            case 'user_name':
            case 'hotel_name':
                return $this->normalizeText($value, 255);
                
            case 'review_text':
            case 'liked_text':
            case 'disliked_text':
            case 'property_response':
                return $this->normalizeText($value);
                
            case 'is_verified':
                return $this->normalizeBoolean($value);
                
            case 'hotel_id':
                return (int) $value;
                
            default:
                return $value;
        }
    }
    
    /**
     * Normalizar nombre de plataforma
     */
    private function normalizePlatform($platform) 
    {
        if (!$platform) return 'unknown';
        
        $platform = strtolower(trim($platform));
        
        // Mapear variaciones comunes
        $platformMap = [
            'booking.com' => 'booking',
            'trip advisor' => 'tripadvisor', 
            'trip-advisor' => 'tripadvisor',
            'hotels.com' => 'hotels',
            'google maps' => 'google',
            'googlemaps' => 'google'
        ];
        
        $mapped = $platformMap[$platform] ?? $platform;
        
        return in_array($mapped, self::VALID_PLATFORMS) ? $mapped : 'unknown';
    }
    
    /**
     * Normalizar rating 
     */
    private function normalizeRating($rating, $isNormalized = false) 
    {
        if (!is_numeric($rating)) return null;
        
        $rating = (float) $rating;
        
        if ($isNormalized) {
            // Ya normalizado, verificar rango 0-10
            return max(0, min(10, $rating));
        }
        
        // Detectar escala y normalizar a 0-10
        if ($rating <= 5) {
            // Escala 1-5 (TripAdvisor) -> 0-10
            return round($rating * 2, 2);
        } elseif ($rating <= 10) {
            // Ya en escala 0-10 (Booking)
            return round($rating, 2);
        } elseif ($rating <= 100) {
            // Escala 0-100 -> 0-10
            return round($rating / 10, 2);
        }
        
        return null;
    }
    
    /**
     * Normalizar fecha
     */
    private function normalizeDate($date) 
    {
        if (!$date) return null;
        
        // Si ya es timestamp, convertir
        if (is_numeric($date)) {
            return date('Y-m-d', $date);
        }
        
        // Intentar parsear fecha
        $timestamp = strtotime($date);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }
    
    /**
     * Normalizar texto
     */
    private function normalizeText($text, $maxLength = null) 
    {
        if (!$text) return null;
        
        // Limpiar espacios y caracteres especiales
        $text = trim($text);
        $text = preg_replace('/\s+/', ' ', $text); // Múltiples espacios -> uno
        
        // Truncar si es necesario
        if ($maxLength && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength - 3) . '...';
        }
        
        return $text ?: null;
    }
    
    /**
     * Normalizar booleano
     */
    private function normalizeBoolean($value) 
    {
        if (is_bool($value)) return $value;
        if (is_numeric($value)) return (bool) $value;
        
        $value = strtolower(trim($value));
        return in_array($value, ['true', '1', 'yes', 'on', 'verified']);
    }
    
    /**
     * Validar y corregir datos normalizados
     */
    private function validateAndFix($data) 
    {
        // Hotel ID es requerido
        if (empty($data['hotel_id'])) {
            throw new InvalidArgumentException('hotel_id es requerido');
        }
        
        // Source platform es requerido
        if (empty($data['source_platform'])) {
            $data['source_platform'] = 'unknown';
        }
        
        // Al menos uno de review_text, liked_text, disliked_text debe existir
        $hasContent = !empty($data['review_text']) || 
                     !empty($data['liked_text']) || 
                     !empty($data['disliked_text']);
        
        if (!$hasContent) {
            throw new InvalidArgumentException('Se requiere al menos un campo de contenido de review');
        }
        
        // Si no hay rating pero hay liked/disliked, intentar inferir
        if (empty($data['rating']) && !empty($data['liked_text']) && empty($data['disliked_text'])) {
            $data['rating'] = 8.0; // Asumir positivo
        } elseif (empty($data['rating']) && empty($data['liked_text']) && !empty($data['disliked_text'])) {
            $data['rating'] = 4.0; // Asumir negativo
        }
        
        return $data;
    }
    
    /**
     * Generar unique_id basado en los datos
     */
    private function generateUniqueId($data) 
    {
        // Usar hotel_id + platform + fecha + hash de contenido
        $components = [
            $data['hotel_id'] ?? 'unknown',
            $data['source_platform'] ?? 'unknown',
            $data['review_date'] ?? date('Y-m-d'),
            substr(md5($data['review_text'] . $data['liked_text'] . $data['user_name']), 0, 8)
        ];
        
        return implode('_', $components);
    }
    
    /**
     * Insertar review usando esquema unificado
     */
    public function insertReview($reviewData) 
    {
        $normalized = $this->normalizeReviewData($reviewData);
        
        if ($this->useUnifiedSchema) {
            return $this->insertIntoUnifiedTable($normalized);
        } else {
            return $this->insertIntoLegacyTable($normalized);
        }
    }
    
    /**
     * Insertar en tabla reviews_final
     */
    private function insertIntoUnifiedTable($data) 
    {
        // Verificar si ya existe
        $existing = $this->findExistingReview($data['unique_id']);
        if ($existing) {
            return $existing['id'];
        }
        
        // Preparar campos para inserción
        $fields = [];
        $values = [];
        $params = [];
        
        foreach ($data as $field => $value) {
            if ($value !== null) {
                $fields[] = $field;
                $values[] = '?';
                $params[] = $value;
            }
        }
        
        $sql = "INSERT INTO reviews_final (" . implode(', ', $fields) . ") VALUES (" . implode(', ', $values) . ")";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Error insertando review: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Buscar review existente por unique_id
     */
    private function findExistingReview($uniqueId) 
    {
        $sql = "SELECT id FROM reviews_final WHERE unique_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$uniqueId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Migrar reviews desde tabla legacy
     */
    public function migrateFromLegacyTable($legacyTableName, $batchSize = 100) 
    {
        echo "🔄 Migrando reviews desde $legacyTableName...\n";
        
        // Contar total
        $totalStmt = $this->pdo->query("SELECT COUNT(*) FROM `$legacyTableName`");
        $total = $totalStmt->fetchColumn();
        
        echo "Total reviews a migrar: $total\n";
        
        $migrated = 0;
        $errors = 0;
        $offset = 0;
        
        while ($offset < $total) {
            $sql = "SELECT * FROM `$legacyTableName` LIMIT $batchSize OFFSET $offset";
            $stmt = $this->pdo->query($sql);
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($reviews as $review) {
                try {
                    $this->insertReview($review);
                    $migrated++;
                } catch (Exception $e) {
                    $errors++;
                    error_log("Error migrando review ID {$review['id']}: " . $e->getMessage());
                }
            }
            
            $offset += $batchSize;
            echo "Procesados: $offset/$total (Migrados: $migrated, Errores: $errors)\n";
        }
        
        echo "✅ Migración completada. Total: $migrated exitosos, $errors errores\n";
        return ['migrated' => $migrated, 'errors' => $errors];
    }
}
