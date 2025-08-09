<?php
/**
 * Adapter para unificar el esquema de reviews entre legacy y nuevo sistema
 * Maneja las diferencias de nombres de columnas entre diferentes versiones
 */

class ReviewsSchemaAdapter 
{
    private $pdo;
    private $useUnified;
    
    // Mapeo de columnas entre esquemas legacy y nuevo
    private $columnMapping = [
        // Nuevo -> Legacy
        'reviewer_name' => 'user_name',
        'review_text' => 'liked_text', // Puede requerir lógica especial
        'platform' => 'source_platform',
        'normalized_rating' => 'rating',
        'response_from_owner' => 'property_response'
    ];
    
    public function __construct($pdo, $useUnified = true) {
        $this->pdo = $pdo;
        $this->useUnified = $useUnified;
    }
    
    /**
     * Insertar review normalizando los nombres de columnas
     */
    public function insertReview($reviewData) {
        $tableName = $this->useUnified ? 'reviews_unified' : 'reviews';
        
        // Normalizar datos según el esquema de destino
        $normalizedData = $this->normalizeForInsert($reviewData);
        
        $columns = array_keys($normalizedData);
        $placeholders = ':' . implode(', :', $columns);
        $columnsList = implode(', ', $columns);
        
        $sql = "INSERT INTO {$tableName} ({$columnsList}) VALUES ({$placeholders})
                ON DUPLICATE KEY UPDATE 
                rating = VALUES(rating),
                review_text = VALUES(review_text),
                liked_text = VALUES(liked_text),
                updated_at = NOW()";
        
        try {
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($normalizedData);
        } catch (PDOException $e) {
            error_log("Error insertando review: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Data: " . json_encode($normalizedData));
            throw $e;
        }
    }
    
    /**
     * Obtener reviews con nombres de columnas normalizados
     */
    public function getReviewsByHotel($hotelId, $platform = null, $limit = 100) {
        $tableName = $this->useUnified ? 'reviews_unified' : 'reviews';
        
        $sql = "SELECT * FROM {$tableName} WHERE hotel_id = ?";
        $params = [$hotelId];
        
        if ($platform) {
            $sql .= " AND " . ($this->useUnified ? 'source_platform' : 'source_platform') . " = ?";
            $params[] = $platform;
        }
        
        $sql .= " ORDER BY review_date DESC, created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll();
        
        return array_map([$this, 'normalizeForOutput'], $reviews);
    }
    
    /**
     * Normalizar datos para inserción
     */
    private function normalizeForInsert($reviewData) {
        $normalized = [];
        
        foreach ($reviewData as $key => $value) {
            // Si estamos usando esquema unificado, mantener ambos nombres cuando sea necesario
            if ($this->useUnified) {
                $normalized[$key] = $value;
                
                // Agregar alias si existe mapeo
                if (isset($this->columnMapping[$key])) {
                    $legacyKey = $this->columnMapping[$key];
                    $normalized[$legacyKey] = $value;
                }
                
                // Mapeos especiales
                if ($key === 'review_text' && !empty($value)) {
                    // Si tenemos review_text, también llenamos liked_text si está vacío
                    if (empty($reviewData['liked_text'])) {
                        $normalized['liked_text'] = $value;
                    }
                }
            } else {
                // Para esquema legacy, mapear nombres nuevos a legacy
                $legacyKey = $this->columnMapping[$key] ?? $key;
                $normalized[$legacyKey] = $value;
            }
        }
        
        // Campos requeridos con valores por defecto
        $defaults = [
            'scraped_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'helpful_votes' => 0,
            'review_language' => 'auto'
        ];
        
        foreach ($defaults as $field => $defaultValue) {
            if (!isset($normalized[$field])) {
                $normalized[$field] = $defaultValue;
            }
        }
        
        return $normalized;
    }
    
    /**
     * Normalizar datos para salida (proporciona ambos nombres de columnas)
     */
    private function normalizeForOutput($reviewRow) {
        $normalized = $reviewRow;
        
        // Agregar aliases para compatibilidad
        foreach ($this->columnMapping as $newName => $legacyName) {
            if (isset($reviewRow[$legacyName]) && !isset($reviewRow[$newName])) {
                $normalized[$newName] = $reviewRow[$legacyName];
            }
            if (isset($reviewRow[$newName]) && !isset($reviewRow[$legacyName])) {
                $normalized[$legacyName] = $reviewRow[$newName];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Verificar si el esquema unificado existe
     */
    public function checkUnifiedSchemaExists() {
        try {
            $stmt = $this->pdo->query("SHOW TABLES LIKE 'reviews_unified'");
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Obtener estadísticas de reviews por plataforma
     */
    public function getPlatformStats($hotelId = null) {
        $tableName = $this->useUnified ? 'reviews_unified' : 'reviews';
        $platformColumn = $this->useUnified ? 'source_platform' : 'source_platform';
        
        $sql = "SELECT 
                    {$platformColumn} as platform,
                    COUNT(*) as total_reviews,
                    AVG(rating) as avg_rating,
                    MIN(review_date) as first_review,
                    MAX(review_date) as latest_review
                FROM {$tableName}";
        
        $params = [];
        if ($hotelId) {
            $sql .= " WHERE hotel_id = ?";
            $params[] = $hotelId;
        }
        
        $sql .= " GROUP BY {$platformColumn} ORDER BY total_reviews DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
}
?>