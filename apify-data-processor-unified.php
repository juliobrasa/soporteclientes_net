<?php
/**
 * Procesador de Datos Apify - Versión Unificada
 * 
 * Esta es una versión actualizada que usa el ReviewsSchemaAdapter
 * para manejar las inconsistencias de esquema de forma automática
 */

require_once 'env-loader.php';
require_once 'ReviewsSchemaAdapter.php';

class ApifyDataProcessorUnified 
{
    private $pdo;
    private $logFile;
    
    public function __construct() 
    {
        $this->connectDatabase();
        $this->logFile = __DIR__ . '/storage/logs/apify-processor.log';
        $this->ensureLogDirectory();
    }
    
    private function connectDatabase() 
    {
        try {
            $this->pdo = createDatabaseConnection();
        } catch (PDOException $e) {
            $this->log("ERROR: No se pudo conectar a la base de datos: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
    
    private function ensureLogDirectory() 
    {
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    private function log($message, $level = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$level] $message\n";
        
        echo $logEntry;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND);
    }
    
    /**
     * Procesar datos de Apify usando el adaptador unificado
     */
    public function processApifyData($apifyData) 
    {
        $this->log("Iniciando procesamiento de datos Apify...");
        
        if (empty($apifyData)) {
            $this->log("No hay datos para procesar", 'WARNING');
            return false;
        }
        
        // Si es un solo elemento, convertir a array
        if (!is_array($apifyData[0] ?? null)) {
            $apifyData = [$apifyData];
        }
        
        $this->log("Procesando " . count($apifyData) . " reviews de Apify");
        
        // Preparar datos usando el adaptador
        $result = ReviewsSchemaAdapter::prepareBulkInsert($apifyData);
        
        $this->log("Preparación completada: {$result['processed']} éxitos, {$result['failed']} fallos");
        
        if (!empty($result['errors'])) {
            foreach ($result['errors'] as $row => $errors) {
                $this->log("Errores en $row: " . implode(', ', $errors), 'WARNING');
            }
        }
        
        if (empty($result['data'])) {
            $this->log("No hay datos válidos para insertar", 'ERROR');
            return false;
        }
        
        // Insertar datos usando transacción
        return $this->insertReviews($result['data']);
    }
    
    /**
     * Insertar reviews usando el esquema unificado
     */
    private function insertReviews($reviewsData) 
    {
        try {
            $this->pdo->beginTransaction();
            
            $inserted = 0;
            $updated = 0;
            $skipped = 0;
            
            foreach ($reviewsData as $reviewData) {
                $result = $this->insertOrUpdateReview($reviewData);
                
                switch ($result) {
                    case 'inserted':
                        $inserted++;
                        break;
                    case 'updated':
                        $updated++;
                        break;
                    case 'skipped':
                        $skipped++;
                        break;
                }
            }
            
            $this->pdo->commit();
            
            $this->log("Transacción completada: $inserted insertados, $updated actualizados, $skipped omitidos", 'SUCCESS');
            
            return [
                'success' => true,
                'inserted' => $inserted,
                'updated' => $updated,
                'skipped' => $skipped,
                'total' => count($reviewsData)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error en transacción: " . $e->getMessage(), 'ERROR');
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Insertar o actualizar una review individual
     */
    private function insertOrUpdateReview($reviewData) 
    {
        // Verificar si ya existe usando unique_id
        $checkSql = "SELECT id FROM reviews WHERE unique_id = ? LIMIT 1";
        $stmt = $this->pdo->prepare($checkSql);
        $stmt->execute([$reviewData['unique_id']]);
        
        $existingId = $stmt->fetchColumn();
        
        if ($existingId) {
            // Actualizar registro existente
            return $this->updateExistingReview($existingId, $reviewData);
        } else {
            // Insertar nuevo registro
            return $this->insertNewReview($reviewData);
        }
    }
    
    /**
     * Insertar nueva review
     */
    private function insertNewReview($reviewData) 
    {
        // Construir query de inserción dinámicamente
        $columns = array_keys($reviewData);
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO reviews (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute(array_values($reviewData));
        
        if ($result) {
            return 'inserted';
        } else {
            throw new Exception("Error insertando review: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    /**
     * Actualizar review existente
     */
    private function updateExistingReview($reviewId, $reviewData) 
    {
        // Campos que no deben actualizarse
        $excludeFromUpdate = ['id', 'unique_id', 'scraped_at'];
        
        $updateFields = [];
        $updateValues = [];
        
        foreach ($reviewData as $column => $value) {
            if (!in_array($column, $excludeFromUpdate)) {
                $updateFields[] = "$column = ?";
                $updateValues[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            return 'skipped';
        }
        
        $updateValues[] = $reviewId; // Para WHERE id = ?
        
        $sql = "UPDATE reviews SET " . implode(', ', $updateFields) . " WHERE id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $result = $stmt->execute($updateValues);
        
        if ($result) {
            return 'updated';
        } else {
            throw new Exception("Error actualizando review: " . implode(', ', $stmt->errorInfo()));
        }
    }
    
    /**
     * Procesar archivo JSON de Apify
     */
    public function processApifyFile($filePath) 
    {
        if (!file_exists($filePath)) {
            $this->log("Archivo no encontrado: $filePath", 'ERROR');
            return false;
        }
        
        $this->log("Procesando archivo: $filePath");
        
        $jsonContent = file_get_contents($filePath);
        $apifyData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Error decodificando JSON: " . json_last_error_msg(), 'ERROR');
            return false;
        }
        
        return $this->processApifyData($apifyData);
    }
    
    /**
     * Procesar desde URL de Apify Dataset
     */
    public function processApifyDataset($datasetId, $token = null) 
    {
        $token = $token ?: EnvLoader::get('APIFY_API_TOKEN');
        
        if (!$token) {
            $this->log("Token de Apify no configurado", 'ERROR');
            return false;
        }
        
        $url = "https://api.apify.com/v2/datasets/$datasetId/items?format=json&clean=true";
        
        $context = stream_context_create([
            'http' => [
                'header' => "Authorization: Bearer $token\r\n",
                'timeout' => 60
            ]
        ]);
        
        $this->log("Descargando datos de dataset: $datasetId");
        
        $jsonContent = file_get_contents($url, false, $context);
        
        if ($jsonContent === false) {
            $this->log("Error descargando dataset de Apify", 'ERROR');
            return false;
        }
        
        $apifyData = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log("Error decodificando respuesta de Apify: " . json_last_error_msg(), 'ERROR');
            return false;
        }
        
        return $this->processApifyData($apifyData);
    }
    
    /**
     * Obtener estadísticas de procesamiento
     */
    public function getProcessingStats($days = 7) 
    {
        try {
            // Estadísticas por fuente de extracción
            $sql = "
                SELECT 
                    extraction_source,
                    COUNT(*) as total_reviews,
                    COUNT(CASE WHEN scraped_at >= DATE_SUB(NOW(), INTERVAL ? DAY) THEN 1 END) as recent_reviews,
                    AVG(rating) as avg_rating,
                    COUNT(CASE WHEN property_response IS NOT NULL THEN 1 END) as with_response
                FROM reviews 
                GROUP BY extraction_source
                ORDER BY total_reviews DESC
            ";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$days]);
            $stats = $stmt->fetchAll();
            
            // Compatibilidad de esquemas
            $compatibility = ReviewsSchemaAdapter::getCompatibilityStats($this->pdo);
            
            return [
                'extraction_sources' => $stats,
                'schema_compatibility' => $compatibility,
                'period_days' => $days
            ];
            
        } catch (Exception $e) {
            $this->log("Error obteniendo estadísticas: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }
}

/**
 * Ejemplo de uso del procesador unificado
 */
function demonstrateProcessorUsage() 
{
    echo "🚀 EJEMPLO DE USO - ApifyDataProcessorUnified\n";
    echo str_repeat("-", 60) . "\n";
    
    // Datos de ejemplo de Apify
    $sampleApifyData = [
        [
            'platform' => 'booking',
            'reviewer_name' => 'María González',
            'review_text' => 'Hotel excelente, muy limpio y ubicación perfecta. El personal muy amable.',
            'normalized_rating' => 9.2,
            'review_date' => '2024-12-10',
            'hotel_id' => 6,
            'review_id' => 'booking_123456'
        ],
        [
            'platform' => 'google',
            'reviewer_name' => 'Carlos Ruiz', 
            'review_text' => 'Buen hotel pero el WiFi era lento. La habitación estaba bien.',
            'normalized_rating' => 7.5,
            'review_date' => '2024-12-11',
            'hotel_id' => 6,
            'review_id' => 'google_789012'
        ]
    ];
    
    try {
        $processor = new ApifyDataProcessorUnified();
        
        echo "Procesando datos de ejemplo...\n";
        $result = $processor->processApifyData($sampleApifyData);
        
        if ($result['success']) {
            echo "✅ Procesamiento exitoso:\n";
            echo "   - Insertados: {$result['inserted']}\n";
            echo "   - Actualizados: {$result['updated']}\n";
            echo "   - Omitidos: {$result['skipped']}\n";
            echo "   - Total: {$result['total']}\n";
        } else {
            echo "❌ Error en procesamiento: {$result['error']}\n";
        }
        
        // Mostrar estadísticas
        echo "\nEstadísticas actuales:\n";
        $stats = $processor->getProcessingStats();
        
        if ($stats) {
            foreach ($stats['extraction_sources'] as $source) {
                printf("  %s: %d reviews (promedio: %.1f)\n",
                    $source['extraction_source'] ?? 'unknown',
                    $source['total_reviews'],
                    $source['avg_rating'] ?? 0
                );
            }
        }
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

// Ejecutar ejemplo si es llamado directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    demonstrateProcessorUsage();
}
?>