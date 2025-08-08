<?php
/**
 * Script de Unificación de Esquema - Tabla Reviews
 * 
 * Ejecuta la migración para unificar los esquemas de reviews
 * y resolver las inconsistencias entre API actual y sistema Apify
 */

require_once 'env-loader.php';

class ReviewsSchemaUnifier 
{
    private $pdo;
    private $dryRun;
    private $existingColumns = [];
    
    public function __construct($dryRun = false) 
    {
        $this->dryRun = $dryRun;
        $this->connectDatabase();
        $this->loadExistingColumns();
    }
    
    private function connectDatabase() 
    {
        $host = $_ENV['DB_HOST'] ?? 'soporteclientes.net';
        $dbname = $_ENV['DB_NAME'] ?? 'soporteia_bookingkavia';
        $username = $_ENV['DB_USER'] ?? 'soporteia_admin';
        $password = $_ENV['DB_PASS'] ?? 'QCF8RhS*}.Oj0u(v';
        
        $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    }
    
    private function loadExistingColumns() 
    {
        $stmt = $this->pdo->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            $this->existingColumns[] = $column['Field'];
        }
    }
    
    private function log($message, $level = 'INFO') 
    {
        $prefix = match($level) {
            'SUCCESS' => '✅',
            'WARNING' => '⚠️ ',
            'ERROR' => '❌',
            'SKIP' => '⏭️ ',
            default => '📋'
        };
        
        echo "$prefix $message\n";
    }
    
    private function executeSQL($sql, $description) 
    {
        if ($this->dryRun) {
            $this->log("[DRY RUN] $description", 'INFO');
            echo "    SQL: $sql\n";
            return true;
        }
        
        try {
            $this->pdo->exec($sql);
            $this->log("$description", 'SUCCESS');
            return true;
        } catch (PDOException $e) {
            $this->log("Error en $description: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }
    
    public function unifySchema() 
    {
        echo "🚀 UNIFICACIÓN DE ESQUEMA - TABLA REVIEWS\n";
        echo str_repeat("=", 60) . "\n\n";
        
        if ($this->dryRun) {
            $this->log("MODO DRY RUN - No se ejecutarán cambios reales", 'WARNING');
            echo "\n";
        }
        
        // Crear backup de la tabla
        $this->createBackup();
        
        // Fase 1: Agregar columnas alias para Apify
        $this->addApifyColumns();
        
        // Fase 2: Agregar columnas para funcionalidad expandida  
        $this->addEnhancedColumns();
        
        // Fase 3: Crear triggers para sincronización
        $this->createSyncTriggers();
        
        // Fase 4: Migrar datos existentes
        $this->migrateExistingData();
        
        // Fase 5: Crear índices optimizados
        $this->createOptimizedIndexes();
        
        // Verificación final
        $this->verifyUnification();
        
        echo "\n" . str_repeat("=", 60) . "\n";
        $this->log("Unificación completada", 'SUCCESS');
    }
    
    private function createBackup() 
    {
        $this->log("Creando backup de tabla reviews...", 'INFO');
        
        $backupTable = 'reviews_backup_' . date('Y_m_d_H_i_s');
        $sql = "CREATE TABLE $backupTable AS SELECT * FROM reviews";
        
        if ($this->executeSQL($sql, "Backup creado como '$backupTable'")) {
            $this->log("Backup disponible para rollback si es necesario", 'INFO');
        }
    }
    
    private function addApifyColumns() 
    {
        $this->log("Fase 1: Agregando columnas para compatibilidad Apify...", 'INFO');
        
        $apifyColumns = [
            'platform' => "VARCHAR(50) COMMENT 'Alias para source_platform (Apify)'",
            'reviewer_name' => "VARCHAR(255) COMMENT 'Alias para user_name (Apify)'", 
            'review_text' => "TEXT COMMENT 'Texto completo de reseña (Apify)'",
            'normalized_rating' => "DECIMAL(3,1) COMMENT 'Rating normalizado (Apify)'",
            'response_from_owner' => "TEXT COMMENT 'Alias para property_response (Apify)'"
        ];
        
        foreach ($apifyColumns as $column => $definition) {
            if (in_array($column, $this->existingColumns)) {
                $this->log("Columna '$column' ya existe", 'SKIP');
                continue;
            }
            
            $sql = "ALTER TABLE reviews ADD COLUMN $column $definition";
            $this->executeSQL($sql, "Agregada columna '$column'");
        }
    }
    
    private function addEnhancedColumns() 
    {
        $this->log("Fase 2: Agregando columnas para funcionalidad expandida...", 'INFO');
        
        $enhancedColumns = [
            'hotel_id' => "INT COMMENT 'ID del hotel (relación con tabla hoteles)'",
            'platform_review_id' => "VARCHAR(100) COMMENT 'ID único de la reseña en la plataforma'",
            'sentiment_score' => "DECIMAL(3,2) COMMENT 'Puntuación de sentimiento (-1.00 a 1.00)'",
            'language_detected' => "VARCHAR(10) COMMENT 'Idioma detectado automáticamente'",
            'processed_at' => "TIMESTAMP NULL COMMENT 'Fecha de procesamiento por IA'",
            'tags' => "JSON COMMENT 'Etiquetas automáticas extraídas'",
            'is_verified' => "BOOLEAN DEFAULT FALSE COMMENT 'Reseña verificada'",
            'extraction_source' => "ENUM('apify', 'manual', 'api', 'bulk') DEFAULT 'manual' COMMENT 'Origen de extracción'"
        ];
        
        foreach ($enhancedColumns as $column => $definition) {
            if (in_array($column, $this->existingColumns)) {
                $this->log("Columna '$column' ya existe", 'SKIP');
                continue;
            }
            
            $sql = "ALTER TABLE reviews ADD COLUMN $column $definition";
            $this->executeSQL($sql, "Agregada columna '$column'");
        }
    }
    
    private function createSyncTriggers() 
    {
        $this->log("Fase 3: Creando triggers de sincronización...", 'INFO');
        
        // Trigger para INSERT
        $insertTrigger = "
        CREATE TRIGGER reviews_sync_insert 
        BEFORE INSERT ON reviews
        FOR EACH ROW
        BEGIN
            -- Sincronizar campos duales en INSERT
            IF NEW.platform IS NOT NULL AND NEW.source_platform IS NULL THEN
                SET NEW.source_platform = NEW.platform;
            ELSEIF NEW.source_platform IS NOT NULL AND NEW.platform IS NULL THEN
                SET NEW.platform = NEW.source_platform;
            END IF;
            
            IF NEW.reviewer_name IS NOT NULL AND NEW.user_name IS NULL THEN
                SET NEW.user_name = NEW.reviewer_name;
            ELSEIF NEW.user_name IS NOT NULL AND NEW.reviewer_name IS NULL THEN
                SET NEW.reviewer_name = NEW.user_name;
            END IF;
            
            IF NEW.response_from_owner IS NOT NULL AND NEW.property_response IS NULL THEN
                SET NEW.property_response = NEW.response_from_owner;
            ELSEIF NEW.property_response IS NOT NULL AND NEW.response_from_owner IS NULL THEN
                SET NEW.response_from_owner = NEW.property_response;
            END IF;
            
            IF NEW.normalized_rating IS NOT NULL AND NEW.rating IS NULL THEN
                SET NEW.rating = NEW.normalized_rating;
            ELSEIF NEW.rating IS NOT NULL AND NEW.normalized_rating IS NULL THEN
                SET NEW.normalized_rating = NEW.rating;
            END IF;
        END";
        
        // Trigger para UPDATE
        $updateTrigger = "
        CREATE TRIGGER reviews_sync_update 
        BEFORE UPDATE ON reviews
        FOR EACH ROW
        BEGIN
            -- Sincronizar campos duales en UPDATE
            IF NEW.platform != OLD.platform THEN
                SET NEW.source_platform = NEW.platform;
            ELSEIF NEW.source_platform != OLD.source_platform THEN
                SET NEW.platform = NEW.source_platform;
            END IF;
            
            IF NEW.reviewer_name != OLD.reviewer_name THEN
                SET NEW.user_name = NEW.reviewer_name;
            ELSEIF NEW.user_name != OLD.user_name THEN
                SET NEW.reviewer_name = NEW.user_name;
            END IF;
            
            IF NEW.response_from_owner != OLD.response_from_owner THEN
                SET NEW.property_response = NEW.response_from_owner;
            ELSEIF NEW.property_response != OLD.property_response THEN
                SET NEW.response_from_owner = NEW.property_response;
            END IF;
            
            IF NEW.normalized_rating != OLD.normalized_rating THEN
                SET NEW.rating = NEW.normalized_rating;
            ELSEIF NEW.rating != OLD.rating THEN
                SET NEW.normalized_rating = NEW.rating;
            END IF;
        END";
        
        // Eliminar triggers existentes si existen
        $this->pdo->exec("DROP TRIGGER IF EXISTS reviews_sync_insert");
        $this->pdo->exec("DROP TRIGGER IF EXISTS reviews_sync_update");
        
        // Crear nuevos triggers
        $this->executeSQL($insertTrigger, "Creado trigger de INSERT");
        $this->executeSQL($updateTrigger, "Creado trigger de UPDATE");
    }
    
    private function migrateExistingData() 
    {
        $this->log("Fase 4: Migrando datos existentes...", 'INFO');
        
        // Actualizar alias de plataforma
        $updates = [
            "UPDATE reviews SET platform = source_platform WHERE platform IS NULL AND source_platform IS NOT NULL",
            "UPDATE reviews SET reviewer_name = user_name WHERE reviewer_name IS NULL AND user_name IS NOT NULL",
            "UPDATE reviews SET response_from_owner = property_response WHERE response_from_owner IS NULL AND property_response IS NOT NULL", 
            "UPDATE reviews SET normalized_rating = rating WHERE normalized_rating IS NULL AND rating IS NOT NULL",
            "UPDATE reviews SET extraction_source = 'manual' WHERE extraction_source IS NULL"
        ];
        
        foreach ($updates as $sql) {
            $this->executeSQL($sql, "Sincronizando datos existentes");
        }
    }
    
    private function createOptimizedIndexes() 
    {
        $this->log("Fase 5: Creando índices optimizados...", 'INFO');
        
        $indexes = [
            "CREATE INDEX idx_reviews_platform ON reviews (source_platform, platform)" => "Índice de plataformas",
            "CREATE INDEX idx_reviews_hotel_date ON reviews (hotel_id, scraped_at)" => "Índice hotel-fecha", 
            "CREATE INDEX idx_reviews_rating ON reviews (rating, normalized_rating)" => "Índice de calificaciones",
            "CREATE INDEX idx_reviews_extraction ON reviews (extraction_source, scraped_at)" => "Índice de origen"
        ];
        
        foreach ($indexes as $sql => $description) {
            // Verificar si el índice ya existe
            $indexName = explode(' ', explode(' ON ', $sql)[0])[2];
            $checkIndex = "SHOW INDEX FROM reviews WHERE Key_name = '$indexName'";
            $stmt = $this->pdo->query($checkIndex);
            
            if ($stmt->fetch()) {
                $this->log("Índice '$indexName' ya existe", 'SKIP');
                continue;
            }
            
            $this->executeSQL($sql, $description);
        }
    }
    
    private function verifyUnification() 
    {
        $this->log("Verificando unificación...", 'INFO');
        
        // Recargar columnas después de modificaciones
        $this->loadExistingColumns();
        
        $requiredColumns = [
            'source_platform', 'platform',
            'property_response', 'response_from_owner',
            'user_name', 'reviewer_name', 
            'rating', 'normalized_rating'
        ];
        
        $missing = array_diff($requiredColumns, $this->existingColumns);
        
        if (empty($missing)) {
            $this->log("Todas las columnas requeridas están presentes", 'SUCCESS');
            
            // Verificar triggers
            $stmt = $this->pdo->query("SHOW TRIGGERS WHERE `Table` = 'reviews'");
            $triggers = $stmt->fetchAll();
            
            if (count($triggers) >= 2) {
                $this->log("Triggers de sincronización activos", 'SUCCESS');
            } else {
                $this->log("Advertencia: Algunos triggers pueden no haberse creado", 'WARNING');
            }
            
        } else {
            $this->log("Columnas faltantes: " . implode(', ', $missing), 'ERROR');
            return false;
        }
        
        return true;
    }
}

// Ejecutar script
try {
    $dryRun = isset($argv[1]) && $argv[1] === '--dry-run';
    
    if ($dryRun) {
        echo "🔍 Ejecutando en modo DRY RUN (solo simulación)\n";
        echo "Para ejecutar real: php unify-reviews-schema.php\n\n";
    }
    
    $unifier = new ReviewsSchemaUnifier($dryRun);
    $unifier->unifySchema();
    
    if (!$dryRun) {
        echo "\n🎉 MIGRACIÓN COMPLETADA EXITOSAMENTE\n";
        echo "📋 Ejecutar 'php verify-reviews-schema.php' para verificar\n";
    } else {
        echo "\n✅ SIMULACIÓN COMPLETADA\n";
        echo "📋 Ejecutar sin --dry-run para aplicar cambios reales\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR EN MIGRACIÓN: " . $e->getMessage() . "\n";
    echo "🔄 Revisar logs y considerar rollback si es necesario\n";
    exit(1);
}
?>