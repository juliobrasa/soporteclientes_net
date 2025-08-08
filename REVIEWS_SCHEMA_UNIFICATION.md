# 🔧 Plan de Unificación de Esquemas - Tabla Reviews

## 📋 Problema Identificado

### Inconsistencias de Columnas

**Esquema Actual (api/reviews.php):**
- `source_platform` → Plataforma de origen
- `property_response` → Respuesta del propietario
- `liked_text` → Aspectos positivos
- `disliked_text` → Aspectos negativos  
- `user_name` → Nombre del huésped

**Esquema Apify (mencionado por usuario):**
- `platform` → vs source_platform
- `response_from_owner` → vs property_response
- `review_text` → vs liked_text/disliked_text
- `reviewer_name` → vs user_name

## 🎯 Estrategia de Solución

### Opción 1: Migración con Alias (Recomendada)

Crear una migración que:
1. **Mantenga las columnas actuales** (compatibilidad)
2. **Agregue alias/vistas** para los nombres alternativos
3. **Use triggers o procedimientos** para sincronización

### Opción 2: Unificación Completa

Migrar a un esquema único:
1. **Renombrar columnas** al estándar más descriptivo
2. **Actualizar todo el código** simultáneamente
3. **Crear mappers** temporales durante transición

## 📊 Esquema Unificado Propuesto

```sql
CREATE TABLE reviews_unified (
    -- Campos principales
    id INT AUTO_INCREMENT PRIMARY KEY,
    unique_id VARCHAR(100) UNIQUE,
    hotel_id INT,
    
    -- Información del huésped (unificado)
    user_name VARCHAR(255),          -- Mantener nombre actual
    reviewer_name VARCHAR(255),      -- Alias para Apify
    user_location VARCHAR(255),
    
    -- Contenido de reseña (unificado)  
    review_title VARCHAR(500),
    liked_text TEXT,                 -- Aspectos positivos
    disliked_text TEXT,              -- Aspectos negativos
    review_text TEXT,                -- Texto completo (para Apify)
    
    -- Respuesta del hotel (unificado)
    property_response TEXT,          -- Mantener nombre actual
    response_from_owner TEXT,        -- Alias para Apify
    
    -- Metadatos de plataforma (unificado)
    source_platform VARCHAR(50),    -- Mantener nombre actual
    platform VARCHAR(50),           -- Alias para Apify
    
    -- Calificación (normalizado)
    rating DECIMAL(3,1),            -- 0.0-10.0 normalizado
    normalized_rating DECIMAL(3,1), -- Alias explícito
    
    -- Otros campos existentes
    review_date DATE,
    scraped_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    review_language VARCHAR(10),
    traveler_type_spanish VARCHAR(100),
    helpful_votes INT DEFAULT 0,
    
    -- Índices
    INDEX idx_hotel_id (hotel_id),
    INDEX idx_platform (source_platform),
    INDEX idx_rating (rating),
    INDEX idx_scraped_date (scraped_at)
);
```

## 🔄 Migración por Fases

### Fase 1: Preparación
```sql
-- Agregar columnas alias sin afectar funcionamiento actual
ALTER TABLE reviews ADD COLUMN platform VARCHAR(50) 
    AS (source_platform) VIRTUAL;
    
ALTER TABLE reviews ADD COLUMN reviewer_name VARCHAR(255) 
    AS (user_name) VIRTUAL;
    
ALTER TABLE reviews ADD COLUMN response_from_owner TEXT 
    AS (property_response) VIRTUAL;
    
ALTER TABLE reviews ADD COLUMN normalized_rating DECIMAL(3,1) 
    AS (rating) VIRTUAL;
```

### Fase 2: Sincronización
```sql
-- Crear triggers para mantener sincronía
DELIMITER //
CREATE TRIGGER reviews_sync_insert 
BEFORE INSERT ON reviews
FOR EACH ROW
BEGIN
    -- Sincronizar campos duales
    IF NEW.platform IS NOT NULL THEN
        SET NEW.source_platform = NEW.platform;
    END IF;
    
    IF NEW.reviewer_name IS NOT NULL THEN
        SET NEW.user_name = NEW.reviewer_name;
    END IF;
    
    IF NEW.response_from_owner IS NOT NULL THEN
        SET NEW.property_response = NEW.response_from_owner;
    END IF;
END//
DELIMITER ;
```

### Fase 3: Actualización de Código
```php
<?php
// Crear clase adaptadora para compatibilidad

class ReviewsSchemaAdapter 
{
    public static function mapApifyToStandard($apifyData) 
    {
        return [
            'source_platform' => $apifyData['platform'] ?? null,
            'user_name' => $apifyData['reviewer_name'] ?? null,
            'property_response' => $apifyData['response_from_owner'] ?? null,
            'rating' => $apifyData['normalized_rating'] ?? null,
            'review_text' => $apifyData['review_text'] ?? null,
            // ... mapear resto de campos
        ];
    }
    
    public static function mapStandardToApify($standardData) 
    {
        return [
            'platform' => $standardData['source_platform'] ?? null,
            'reviewer_name' => $standardData['user_name'] ?? null,
            'response_from_owner' => $standardData['property_response'] ?? null,
            'normalized_rating' => $standardData['rating'] ?? null,
            // ... mapear resto de campos
        ];
    }
}
?>
```

## 🛠️ Implementación Inmediata

### 1. Crear Migración Laravel
```bash
cd kavia-laravel
php artisan make:migration unify_reviews_schema --table=reviews
```

### 2. Script de Verificación
```php
<?php
// verify-reviews-schema.php - Para verificar estructura actual

$pdo = new PDO("mysql:host=localhost;dbname=soporteia_bookingkavia", $user, $pass);

// Verificar columnas existentes
$stmt = $pdo->query("DESCRIBE reviews");
$existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

$requiredColumns = [
    'source_platform', 'platform',
    'property_response', 'response_from_owner', 
    'liked_text', 'disliked_text', 'review_text',
    'user_name', 'reviewer_name'
];

echo "=== VERIFICACIÓN DE ESQUEMA REVIEWS ===\n";
foreach ($requiredColumns as $column) {
    $exists = in_array($column, $existingColumns);
    echo "[$column] " . ($exists ? "✅ EXISTS" : "❌ MISSING") . "\n";
}
?>
```

## ⚠️ Consideraciones de Riesgo

### Riesgos de la Migración
1. **Downtime:** Migración en tabla grande puede tardar
2. **Inconsistencia temporal:** Durante migración algunos datos pueden estar desfasados
3. **Rollback complejo:** Difícil deshacer si hay problemas

### Mitigación de Riesgos  
1. **Migración incremental** por lotes pequeños
2. **Backup completo** antes de iniciar
3. **Testing exhaustivo** en ambiente de desarrollo
4. **Rollback plan** documentado y probado

## 📅 Timeline Propuesto

| Fase | Duración | Actividades |
|------|----------|-------------|
| **Análisis** | 1 día | Verificar esquema actual, documentar discrepancias |
| **Desarrollo** | 2 días | Crear migraciones, adapters, scripts de verificación |
| **Testing** | 1 día | Pruebas en desarrollo, verificar compatibilidad |
| **Deployment** | 1 día | Ejecutar migración en producción, monitoreo |
| **Verificación** | 1 día | Validar funcionamiento, ajustes finales |

**Total estimado:** 5 días laborales

## 🎯 Resultado Esperado

Al final de la migración:
- ✅ **Compatibilidad total** entre sistemas Apify y API actual
- ✅ **Código unificado** sin lógica duplicada  
- ✅ **Escalabilidad mejorada** para nuevas integraciones
- ✅ **Mantenimiento simplificado** con esquema consistente
- ✅ **Zero downtime** durante la transición

---

**🚀 Próximo paso:** Ejecutar script de verificación para confirmar estado actual de la base de datos.