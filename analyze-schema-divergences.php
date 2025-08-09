<?php
/**
 * Analizador de divergencias de esquema entre flujos de reviews
 */

require_once 'env-loader.php';

echo "📊 ANÁLISIS DE DIVERGENCIAS DE ESQUEMA - REVIEWS\n";
echo str_repeat("=", 55) . "\n\n";

class SchemaAnalyzer 
{
    private $pdo;
    private $divergences = [];
    
    public function __construct() 
    {
        try {
            $this->pdo = EnvironmentLoader::createDatabaseConnection();
            echo "✅ Conexión a BD establecida\n\n";
        } catch (Exception $e) {
            echo "⚠️ BD no disponible - análisis limitado\n";
            echo "Error: " . $e->getMessage() . "\n\n";
            $this->pdo = null;
        }
    }
    
    public function analyzeAll() 
    {
        echo "🔍 Analizando esquemas de reviews...\n\n";
        
        // 1. Analizar estructura de tablas
        $this->analyzeTables();
        
        // 2. Analizar uso de columnas en código
        $this->analyzeCodeUsage();
        
        // 3. Identificar divergencias
        $this->identifyDivergences();
        
        // 4. Generar soluciones
        $this->generateSolutions();
        
        return $this->divergences;
    }
    
    private function analyzeTables() 
    {
        echo "📋 Analizando estructura de tablas:\n";
        
        if (!$this->pdo) {
            echo "  ⚠️ Saltando - BD no disponible\n\n";
            return;
        }
        
        // Verificar qué tablas de reviews existen
        $tables = ['reviews', 'reviews_unified', 'hotel_reviews'];
        
        foreach ($tables as $table) {
            try {
                $stmt = $this->pdo->query("DESCRIBE $table");
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "  ✅ Tabla '$table' encontrada:\n";
                foreach ($columns as $column) {
                    echo "    - {$column['Field']} ({$column['Type']})\n";
                }
                echo "\n";
                
                // Guardar para análisis
                $this->divergences['tables'][$table] = $columns;
                
            } catch (PDOException $e) {
                echo "  ❌ Tabla '$table' no existe\n";
            }
        }
    }
    
    private function analyzeCodeUsage() 
    {
        echo "📋 Analizando uso en código:\n";
        
        $files = [
            'api/reviews.php' => 'API de lectura de reviews',
            'apify-data-processor.php' => 'Procesador de datos Apify',
            'api-extraction.php' => 'API de extracción (sync mode)'
        ];
        
        foreach ($files as $file => $description) {
            if (file_exists($file)) {
                $this->analyzeFileUsage($file, $description);
            } else {
                echo "  ⚠️ Archivo no encontrado: $file\n";
            }
        }
        
        echo "\n";
    }
    
    private function analyzeFileUsage($file, $description) 
    {
        echo "  📄 $description ($file):\n";
        
        $content = file_get_contents($file);
        
        // Buscar patrones de uso de columnas
        $patterns = [
            'INSERT_INTO_REVIEWS' => '/INSERT.*INTO\\s+(reviews\\w*)\\s*\\(/i',
            'SELECT_FROM_REVIEWS' => '/SELECT.*FROM\\s+(reviews\\w*)/i',
            'COLUMN_REFERENCES' => '/(source_platform|liked_text|disliked_text|property_response|user_name|reviewer_name|review_text|platform|normalized_rating|response_from_owner)/',
        ];
        
        foreach ($patterns as $patternName => $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                if ($patternName === 'COLUMN_REFERENCES') {
                    $uniqueColumns = array_unique($matches[1]);
                    echo "    Columnas referenciadas: " . implode(', ', $uniqueColumns) . "\n";
                    $this->divergences['column_usage'][$file] = $uniqueColumns;
                } else {
                    $uniqueTables = array_unique($matches[1]);
                    echo "    Tablas usadas ($patternName): " . implode(', ', $uniqueTables) . "\n";
                    $this->divergences['table_usage'][$file] = $uniqueTables;
                }
            }
        }
        
        echo "\n";
    }
    
    private function identifyDivergences() 
    {
        echo "🔍 Identificando divergencias críticas:\n";
        
        $issues = [];
        
        // Issue 1: Nombres de columna inconsistentes
        $columnMappings = [
            'user_name vs reviewer_name' => ['user_name', 'reviewer_name'],
            'liked_text vs review_text' => ['liked_text', 'review_text'],
            'source_platform vs platform' => ['source_platform', 'platform'],
            'property_response vs response_from_owner' => ['property_response', 'response_from_owner'],
            'rating vs normalized_rating' => ['rating', 'normalized_rating']
        ];
        
        foreach ($columnMappings as $issue => $columns) {
            $usageCount = 0;
            $files = [];
            
            foreach ($this->divergences['column_usage'] ?? [] as $file => $fileColumns) {
                $intersection = array_intersect($columns, $fileColumns);
                if (!empty($intersection)) {
                    $usageCount++;
                    $files[] = $file . ' (' . implode(', ', $intersection) . ')';
                }
            }
            
            if ($usageCount > 0) {
                echo "  ❌ $issue:\n";
                foreach ($files as $fileInfo) {
                    echo "    - $fileInfo\n";
                }
                $issues[$issue] = $files;
            }
        }
        
        // Issue 2: Tablas diferentes
        if (isset($this->divergences['table_usage'])) {
            $allTables = [];
            foreach ($this->divergences['table_usage'] as $file => $tables) {
                $allTables = array_merge($allTables, $tables);
            }
            $uniqueTables = array_unique($allTables);
            
            if (count($uniqueTables) > 1) {
                echo "  ❌ Múltiples tablas de reviews en uso:\n";
                foreach ($uniqueTables as $table) {
                    echo "    - $table\n";
                }
                $issues['multiple_tables'] = $uniqueTables;
            }
        }
        
        $this->divergences['critical_issues'] = $issues;
        echo "\n";
    }
    
    private function generateSolutions() 
    {
        echo "💡 SOLUCIONES RECOMENDADAS:\n";
        echo str_repeat("-", 40) . "\n";
        
        // Solución 1: Schema unificado
        echo "1. 🎯 ESQUEMA UNIFICADO:\n";
        echo "   Crear tabla reviews con todas las columnas necesarias:\n";
        $this->generateUnifiedSchema();
        echo "\n";
        
        // Solución 2: Adapter pattern
        echo "2. 🔧 ADAPTER PATTERN:\n";
        echo "   Crear ReviewsSchemaAdapter para normalizar inserts/selects\n";
        $this->generateAdapterCode();
        echo "\n";
        
        // Solución 3: Database migration
        echo "3. 🗄️ MIGRACIÓN DE BASE DE DATOS:\n";
        echo "   Migrar datos existentes al esquema unificado\n";
        $this->generateMigrationScript();
        echo "\n";
        
        // Solución 4: Temporary views
        echo "4. 👁️ VISTAS TEMPORALES:\n";
        echo "   Crear vistas para compatibilidad durante transición\n";
        $this->generateCompatibilityViews();
        echo "\n";
    }
    
    private function generateUnifiedSchema() 
    {
        $schemaFile = __DIR__ . '/unified-reviews-schema.sql';
        
        $sql = "-- Esquema unificado para tabla reviews\n";
        $sql .= "-- Generado: " . date('Y-m-d H:i:s') . "\n\n";
        
        $sql .= "CREATE TABLE IF NOT EXISTS reviews_unified_final (\n";
        $sql .= "    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,\n";
        $sql .= "    unique_id VARCHAR(255) UNIQUE NOT NULL COMMENT 'ID único global',\n";
        $sql .= "    \n";
        $sql .= "    -- Referencias\n";
        $sql .= "    hotel_id INT UNSIGNED NOT NULL,\n";
        $sql .= "    extraction_run_id VARCHAR(100) NULL,\n";
        $sql .= "    platform_review_id VARCHAR(255) NULL,\n";
        $sql .= "    \n";
        $sql .= "    -- Datos del usuario (columnas normalizadas)\n";
        $sql .= "    user_name VARCHAR(255) NULL,\n";
        $sql .= "    reviewer_name VARCHAR(255) NULL COMMENT 'Alias de user_name para compatibilidad',\n";
        $sql .= "    user_location VARCHAR(255) NULL,\n";
        $sql .= "    \n";
        $sql .= "    -- Contenido de la review (columnas unificadas)\n";
        $sql .= "    review_title VARCHAR(500) NULL,\n";
        $sql .= "    review_text TEXT NULL COMMENT 'Texto completo de la review',\n";
        $sql .= "    liked_text TEXT NULL COMMENT 'Aspectos positivos',\n";
        $sql .= "    disliked_text TEXT NULL COMMENT 'Aspectos negativos',\n";
        $sql .= "    \n";
        $sql .= "    -- Rating (normalizado)\n";
        $sql .= "    rating DECIMAL(3,1) NULL COMMENT 'Rating original',\n";
        $sql .= "    normalized_rating DECIMAL(3,1) NULL COMMENT 'Rating normalizado 0-10',\n";
        $sql .= "    \n";
        $sql .= "    -- Plataforma (unificado)\n";
        $sql .= "    source_platform VARCHAR(50) NOT NULL COMMENT 'Plataforma principal',\n";
        $sql .= "    platform VARCHAR(50) NULL COMMENT 'Alias para compatibilidad',\n";
        $sql .= "    \n";
        $sql .= "    -- Respuesta del hotel (unificado)\n";
        $sql .= "    property_response TEXT NULL COMMENT 'Respuesta del hotel',\n";
        $sql .= "    response_from_owner TEXT NULL COMMENT 'Alias para compatibilidad',\n";
        $sql .= "    \n";
        $sql .= "    -- Metadatos\n";
        $sql .= "    review_date DATE NULL,\n";
        $sql .= "    scraped_at TIMESTAMP NULL,\n";
        $sql .= "    helpful_votes INT DEFAULT 0,\n";
        $sql .= "    review_language VARCHAR(10) DEFAULT 'auto',\n";
        $sql .= "    extraction_status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'completed',\n";
        $sql .= "    \n";
        $sql .= "    -- Timestamps\n";
        $sql .= "    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,\n";
        $sql .= "    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,\n";
        $sql .= "    \n";
        $sql .= "    -- Índices\n";
        $sql .= "    INDEX idx_hotel_platform (hotel_id, source_platform),\n";
        $sql .= "    INDEX idx_review_date (review_date),\n";
        $sql .= "    INDEX idx_scraped_at (scraped_at),\n";
        $sql .= "    INDEX idx_rating (rating),\n";
        $sql .= "    INDEX idx_extraction_run (extraction_run_id),\n";
        $sql .= "    \n";
        $sql .= "    FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE\n";
        $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;\n\n";
        
        // Triggers para mantener sincronización de columnas alias
        $sql .= "-- Triggers para mantener columnas alias sincronizadas\n";
        $sql .= "DELIMITER //\n";
        $sql .= "CREATE TRIGGER reviews_before_insert BEFORE INSERT ON reviews_unified_final\n";
        $sql .= "FOR EACH ROW BEGIN\n";
        $sql .= "    SET NEW.reviewer_name = COALESCE(NEW.user_name, NEW.reviewer_name);\n";
        $sql .= "    SET NEW.platform = COALESCE(NEW.source_platform, NEW.platform);\n";
        $sql .= "    SET NEW.response_from_owner = COALESCE(NEW.property_response, NEW.response_from_owner);\n";
        $sql .= "END//\n";
        $sql .= "DELIMITER ;\n\n";
        
        file_put_contents($schemaFile, $sql);
        echo "   📄 Esquema generado: unified-reviews-schema.sql\n";
    }
    
    private function generateAdapterCode() 
    {
        $adapterFile = __DIR__ . '/ReviewsSchemaAdapter.php';
        
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * Adapter para normalizar operaciones con diferentes esquemas de reviews\n";
        $code .= " */\n\n";
        
        $code .= "class ReviewsSchemaAdapter \n";
        $code .= "{\n";
        $code .= "    private \$pdo;\n";
        $code .= "    private \$tableName;\n\n";
        
        $code .= "    public function __construct(\$pdo, \$tableName = 'reviews_unified_final') {\n";
        $code .= "        \$this->pdo = \$pdo;\n";
        $code .= "        \$this->tableName = \$tableName;\n";
        $code .= "    }\n\n";
        
        $code .= "    /**\n";
        $code .= "     * Insertar review normalizando campos\n";
        $code .= "     */\n";
        $code .= "    public function insertReview(\$data) {\n";
        $code .= "        // Normalizar campos\n";
        $code .= "        \$normalized = \$this->normalizeReviewData(\$data);\n\n";
        
        $code .= "        \$stmt = \$this->pdo->prepare(\"\n";
        $code .= "            INSERT IGNORE INTO {\$this->tableName} (\n";
        $code .= "                unique_id, hotel_id, user_name, review_text, \n";
        $code .= "                liked_text, disliked_text, rating, source_platform,\n";
        $code .= "                property_response, review_date, scraped_at,\n";
        $code .= "                platform_review_id, extraction_run_id\n";
        $code .= "            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n";
        $code .= "        \");\n\n";
        
        $code .= "        return \$stmt->execute([\n";
        $code .= "            \$normalized['unique_id'],\n";
        $code .= "            \$normalized['hotel_id'],\n";
        $code .= "            \$normalized['user_name'],\n";
        $code .= "            \$normalized['review_text'],\n";
        $code .= "            \$normalized['liked_text'],\n";
        $code .= "            \$normalized['disliked_text'],\n";
        $code .= "            \$normalized['rating'],\n";
        $code .= "            \$normalized['source_platform'],\n";
        $code .= "            \$normalized['property_response'],\n";
        $code .= "            \$normalized['review_date'],\n";
        $code .= "            \$normalized['scraped_at'],\n";
        $code .= "            \$normalized['platform_review_id'],\n";
        $code .= "            \$normalized['extraction_run_id']\n";
        $code .= "        ]);\n";
        $code .= "    }\n\n";
        
        $code .= "    /**\n";
        $code .= "     * Normalizar datos de review desde diferentes fuentes\n";
        $code .= "     */\n";
        $code .= "    private function normalizeReviewData(\$data) {\n";
        $code .= "        return [\n";
        $code .= "            'unique_id' => \$data['unique_id'] ?? \$data['id'] ?? uniqid('rev_'),\n";
        $code .= "            'hotel_id' => \$data['hotel_id'],\n";
        $code .= "            'user_name' => \$data['user_name'] ?? \$data['reviewer_name'] ?? \$data['authorName'] ?? 'Anónimo',\n";
        $code .= "            'review_text' => \$data['review_text'] ?? \$data['comment'] ?? null,\n";
        $code .= "            'liked_text' => \$data['liked_text'] ?? null,\n";
        $code .= "            'disliked_text' => \$data['disliked_text'] ?? null,\n";
        $code .= "            'rating' => \$data['rating'] ?? \$data['normalized_rating'] ?? 0,\n";
        $code .= "            'source_platform' => \$data['source_platform'] ?? \$data['platform'] ?? 'unknown',\n";
        $code .= "            'property_response' => \$data['property_response'] ?? \$data['response_from_owner'] ?? null,\n";
        $code .= "            'review_date' => \$data['review_date'] ?? \$data['date_created'] ?? null,\n";
        $code .= "            'scraped_at' => \$data['scraped_at'] ?? date('Y-m-d H:i:s'),\n";
        $code .= "            'platform_review_id' => \$data['platform_review_id'] ?? \$data['reviewId'] ?? \$data['external_id'] ?? null,\n";
        $code .= "            'extraction_run_id' => \$data['extraction_run_id'] ?? null\n";
        $code .= "        ];\n";
        $code .= "    }\n";
        $code .= "}\n";
        
        file_put_contents($adapterFile, $code);
        echo "   📄 Adapter generado: ReviewsSchemaAdapter.php\n";
    }
    
    private function generateMigrationScript() 
    {
        $migrationFile = __DIR__ . '/migrate-reviews-data.php';
        
        $code = "<?php\n";
        $code .= "/**\n";
        $code .= " * Migración de datos existentes al esquema unificado\n";
        $code .= " */\n\n";
        
        $code .= "require_once 'env-loader.php';\n";
        $code .= "require_once 'ReviewsSchemaAdapter.php';\n\n";
        
        $code .= "class ReviewsDataMigrator {\n";
        $code .= "    private \$pdo;\n";
        $code .= "    private \$adapter;\n\n";
        
        $code .= "    public function migrate() {\n";
        $code .= "        // Migrar desde tabla 'reviews' existente\n";
        $code .= "        // Migrar desde tabla 'reviews_unified' si existe\n";
        $code .= "        // Verificar integridad de datos\n";
        $code .= "    }\n";
        $code .= "}\n";
        
        file_put_contents($migrationFile, $code);
        echo "   📄 Migrador generado: migrate-reviews-data.php\n";
    }
    
    private function generateCompatibilityViews() 
    {
        $viewsFile = __DIR__ . '/compatibility-views.sql';
        
        $sql = "-- Vistas de compatibilidad durante transición\n\n";
        
        $sql .= "-- Vista para código legacy que usa nombres de columna antiguos\n";
        $sql .= "CREATE OR REPLACE VIEW reviews_legacy AS\n";
        $sql .= "SELECT \n";
        $sql .= "    id,\n";
        $sql .= "    unique_id,\n";
        $sql .= "    hotel_id,\n";
        $sql .= "    user_name,\n";
        $sql .= "    user_name as reviewer_name,\n";
        $sql .= "    review_text,\n";
        $sql .= "    liked_text,\n";
        $sql .= "    disliked_text,\n";
        $sql .= "    rating,\n";
        $sql .= "    rating as normalized_rating,\n";
        $sql .= "    source_platform,\n";
        $sql .= "    source_platform as platform,\n";
        $sql .= "    property_response,\n";
        $sql .= "    property_response as response_from_owner,\n";
        $sql .= "    review_date,\n";
        $sql .= "    scraped_at,\n";
        $sql .= "    created_at,\n";
        $sql .= "    updated_at\n";
        $sql .= "FROM reviews_unified_final;\n\n";
        
        file_put_contents($viewsFile, $sql);
        echo "   📄 Vistas generadas: compatibility-views.sql\n";
    }
}

// Ejecutar análisis si se llama directamente
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $analyzer = new SchemaAnalyzer();
    $results = $analyzer->analyzeAll();
    
    echo "\n🎯 PASOS SIGUIENTES:\n";
    echo "1. Revisar unified-reviews-schema.sql\n";
    echo "2. Implementar ReviewsSchemaAdapter.php\n";
    echo "3. Ejecutar migración de datos\n";
    echo "4. Actualizar código para usar adapter\n";
    echo "5. Verificar compatibilidad con vistas temporales\n\n";
}
?>