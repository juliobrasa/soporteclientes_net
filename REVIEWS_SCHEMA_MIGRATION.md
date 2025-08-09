# Migración de Esquema de Reviews - Documentación Final

## 📋 Resumen

Este documento describe la implementación del esquema unificado de reviews que resuelve las inconsistencias entre 13+ tablas existentes y normaliza los nombres de columnas.

## 🎯 Objetivos Cumplidos

### ✅ 1. Esquema Final Unificado
- **Tabla principal**: `reviews_final` 
- **Columnas normalizadas**:
  - `user_name` (principal) ← `reviewer_name`, `guest_name`
  - `source_platform` (principal) ← `platform`, `platform_name`
  - `property_response` (principal) ← `response_from_owner`, `hotel_response`
  - `review_text` (principal) ← `comment`, `full_review_text`
  - `normalized_rating` (0-10 scale) ← `rating`, `unified_rating`

### ✅ 2. Compatibilidad Legacy
- **Triggers automáticos**: Sincronizan campos alias
- **Vistas de compatibilidad**: 
  - `reviews_legacy_compat` → Compatible con tabla `reviews`
  - `reviews_unified_compat` → Compatible con tabla `reviews_unified`
  - `recent_reviews_compat` → Compatible con tabla `recent_reviews`

### ✅ 3. Adaptador Inteligente
- **Clase**: `ReviewsSchemaAdapter`
- **Mapeo automático** de 15+ variaciones de nombres de columnas
- **Normalización inteligente** de ratings, fechas, plataformas
- **Validación y corrección** automática de datos
- **Migración en lotes** con manejo de errores

## 🏗️ Arquitectura del Esquema Final

### Columnas Principales (usar estas en código nuevo)
```sql
-- IDENTIDAD
id BIGINT AUTO_INCREMENT PRIMARY KEY
unique_id VARCHAR(255) UNIQUE -- Previene duplicados
hotel_id INT UNSIGNED -- FK a hoteles.id

-- USUARIO (NORMALIZADO)
user_name VARCHAR(255) -- ✅ Campo principal
reviewer_name VARCHAR(255) -- ⚠️ Alias legacy

-- CONTENIDO (NORMALIZADO) 
review_text TEXT -- ✅ Campo principal
liked_text TEXT -- Aspectos positivos
disliked_text TEXT -- Aspectos negativos

-- RATING (NORMALIZADO)
normalized_rating DECIMAL(4,2) -- ✅ Escala 0-10 unificada
rating DECIMAL(3,1) -- Rating original

-- PLATAFORMA (NORMALIZADO)
source_platform VARCHAR(50) -- ✅ Campo principal
platform VARCHAR(50) -- ⚠️ Alias legacy

-- RESPUESTA HOTEL (NORMALIZADO)
property_response TEXT -- ✅ Campo principal
response_from_owner TEXT -- ⚠️ Alias legacy
hotel_response TEXT -- ⚠️ Alias legacy
```

### Campos con Compatibilidad Automática
Los triggers mantienen sincronizados automáticamente:
- `user_name` ↔ `reviewer_name`
- `source_platform` ↔ `platform` 
- `property_response` ↔ `response_from_owner` ↔ `hotel_response`

## 🔄 Proceso de Migración

### Archivos Creados
1. **`reviews-final-schema.sql`** - DDL completo del esquema
2. **`ReviewsSchemaAdapter.php`** - Clase adaptadora con normalización
3. **`implement-unified-reviews.php`** - Script de migración completo
4. **`analyze-review-schemas.php`** - Análisis de esquemas existentes

### Ejecución de la Migración
```bash
# 1. Analizar esquemas existentes
php analyze-review-schemas.php

# 2. Ejecutar migración completa
php implement-unified-reviews.php

# 3. Revisar reporte generado
cat migration-report-*.json
```

## 📊 Normalización de Datos

### Mapeo de Columnas Legacy → Unificado
```php
'reviewer_name' => 'user_name',
'guest_name' => 'user_name', 
'Nombre del usuario' => 'user_name',
'platform_name' => 'source_platform',
'full_review_text' => 'review_text',
'Reseña buena' => 'liked_text',
'Reseña mala' => 'disliked_text',
'unified_rating' => 'normalized_rating',
'response_from_owner' => 'property_response',
'hotel_response' => 'property_response',
'contestado' => 'property_response'
```

### Normalización de Valores

#### Plataformas
```php
'booking.com' → 'booking'
'trip advisor' → 'tripadvisor'
'hotels.com' → 'hotels'
'google maps' → 'google'
'unknown' → fallback por defecto
```

#### Ratings (normalizado a escala 0-10)
```php
Rating 1-5 (TripAdvisor) → * 2 = 0-10
Rating 0-10 (Booking) → sin cambio  
Rating 0-100 → / 10 = 0-10
```

## 🔌 Uso del Adaptador

### Inserción Básica
```php
$adapter = new ReviewsSchemaAdapter($pdo, true);

$reviewData = [
    'hotel_id' => 6,
    'reviewer_name' => 'Juan', // Se mapea a user_name
    'platform' => 'booking.com', // Se normaliza a 'booking'
    'unified_rating' => 5.0, // Se mapea a normalized_rating
    'full_review_text' => 'Excelente hotel', // Se mapea a review_text
    'hotel_response' => 'Gracias por su visita' // Se mapea a property_response
];

$reviewId = $adapter->insertReview($reviewData);
```

### Migración desde Tabla Legacy
```php
$adapter = new ReviewsSchemaAdapter($pdo);
$result = $adapter->migrateFromLegacyTable('reviews', 100);
// Returns: ['migrated' => 1000, 'errors' => 5]
```

## 🔍 Vistas de Compatibilidad

### Para código que usa tabla `reviews` legacy
```sql
SELECT * FROM reviews_legacy_compat WHERE hotel_id = 6;
-- Funciona idéntico a la tabla original
```

### Para código que usa tabla `reviews_unified`
```sql  
SELECT * FROM reviews_unified_compat WHERE hotel_id = 6;
-- Mantiene compatibilidad con guest_name, platform_name, etc.
```

## ⚡ Beneficios Implementados

### 1. Consistencia de Datos
- ✅ Nombres de columnas unificados
- ✅ Formatos de fecha normalizados  
- ✅ Escala de rating consistente (0-10)
- ✅ Plataformas estandarizadas

### 2. Performance
- ✅ Índices optimizados para consultas frecuentes
- ✅ Foreign keys para integridad referencial
- ✅ Charset UTF-8 para soporte internacional

### 3. Compatibilidad
- ✅ Código legacy sigue funcionando
- ✅ Migración gradual sin downtime
- ✅ Rollback posible via vistas

### 4. Extensibilidad
- ✅ Campos JSON para metadatos extensibles
- ✅ ENUMs para valores controlados
- ✅ Triggers para automatización

## 🚀 Próximos Pasos Recomendados

### Fase 1: Validación (Inmediato)
1. ✅ Ejecutar migración en desarrollo
2. ✅ Validar integridad de datos migrados
3. ✅ Probar vistas de compatibilidad
4. ✅ Verificar performance de consultas

### Fase 2: Integración (1-2 semanas)
1. 🔄 Actualizar código de inserción para usar `reviews_final`
2. 🔄 Migrar consultas complejas a nuevas columnas
3. 🔄 Actualizar APIs para retornar datos normalizados
4. 🔄 Configurar monitoreo de la nueva tabla

### Fase 3: Optimización (2-4 semanas) 
1. ⏳ Evaluar performance con datos reales
2. ⏳ Añadir índices adicionales si necesario
3. ⏳ Considerar particionado por fecha
4. ⏳ Implementar archivado de reviews antiguas

### Fase 4: Limpieza (1+ mes)
1. ⏳ Validar que código legacy no se usa
2. ⏳ Eliminar tablas redundantes
3. ⏳ Remover vistas de compatibilidad
4. ⏳ Documentar esquema final para equipo

## 📈 Métricas de Éxito

- **Consistencia**: 100% de reviews con campos normalizados
- **Performance**: Consultas ≤ 100ms en promedio
- **Integridad**: 0 duplicados por unique_id
- **Compatibilidad**: 100% código legacy funcional

## 🛠️ Comandos Útiles

### Verificar migración
```sql
SELECT 
    COUNT(*) as total_reviews,
    COUNT(DISTINCT source_platform) as platforms,
    AVG(normalized_rating) as avg_rating
FROM reviews_final;
```

### Comparar con tabla legacy
```sql
SELECT 
    (SELECT COUNT(*) FROM reviews) as legacy_count,
    (SELECT COUNT(*) FROM reviews_final) as new_count,
    (SELECT COUNT(*) FROM reviews_final) - (SELECT COUNT(*) FROM reviews) as difference;
```

### Verificar duplicados
```sql
SELECT unique_id, COUNT(*) 
FROM reviews_final 
GROUP BY unique_id 
HAVING COUNT(*) > 1;
```

---

**✅ Migración Completada**: El esquema unificado está listo para producción con compatibilidad total hacia atrás y normalización completa de datos.