# 🚀 Guía de Integración - Sistema Reviews Unificado

## 📋 Introducción

Esta guía te ayudará a integrar nuevas fuentes de datos (plataformas, scrapeadores, APIs) con el **sistema reviews unificado**, aprovechando la arquitectura robusta post-migración que elimina inconsistencias de esquema.

### ✨ **Beneficios del Sistema Unificado**
- ✅ **Compatibilidad garantizada** - Sin fallos por columnas faltantes
- ✅ **Adaptador automático** - Mapeo inteligente entre esquemas
- ✅ **Validación robusta** - Datos verificados antes de inserción  
- ✅ **Escalabilidad** - Preparado para cualquier nueva fuente
- ✅ **Monitoreo incluido** - Tracking automático de fuentes

---

## 🎯 **Casos de Uso Comunes**

### **1. Nueva Plataforma OTA** (ej. Expedia, Despegar)
### **2. Scrapeador Custom** (ej. TripAdvisor avanzado)  
### **3. API Externa** (ej. Google Reviews API)
### **4. Bulk Import** (ej. CSV de datos históricos)
### **5. Webhook/Real-time** (ej. notificaciones instantáneas)

---

## 🔧 **Opción 1: Integración Rápida con Adaptador**

### **Para casos simples - Usa ReviewsSchemaAdapter directamente**

```php
<?php
require_once 'ReviewsSchemaAdapter.php';

// 1. Tus datos en cualquier formato
$tusDatos = [
    'platform' => 'expedia',
    'reviewer_name' => 'Juan Pérez',
    'review_text' => 'Hotel excelente, muy recomendado',
    'normalized_rating' => 9.5,
    'review_date' => '2024-12-15',
    'hotel_id' => 6
];

// 2. Mapear al esquema estándar automáticamente
$datosUnificados = ReviewsSchemaAdapter::mapApifyToStandard($tusDatos);

// 3. Validar datos (opcional pero recomendado)
$errores = ReviewsSchemaAdapter::validateApifyData($tusDatos);
if (!empty($errores)) {
    throw new Exception("Datos inválidos: " . implode(', ', $errores));
}

// 4. Insertar en base de datos
$pdo = createDatabaseConnection();

$columns = array_keys($datosUnificados);
$placeholders = array_fill(0, count($columns), '?');

$sql = "INSERT INTO reviews (" . implode(', ', $columns) . ") 
        VALUES (" . implode(', ', $placeholders) . ")";

$stmt = $pdo->prepare($sql);
$resultado = $stmt->execute(array_values($datosUnificados));

echo $resultado ? "✅ Review insertada correctamente" : "❌ Error al insertar";
?>
```

---

## 🏗️ **Opción 2: Integración Completa con Procesador Custom**

### **Para casos avanzados - Crea tu propio procesador basado en el modelo**

```php
<?php
require_once 'ReviewsSchemaAdapter.php';
require_once 'env-loader.php';

class TuNuevoIntegrador 
{
    private $pdo;
    private $sourceIdentifier;
    private $logFile;
    
    public function __construct($sourceIdentifier = 'tu_fuente') 
    {
        $this->sourceIdentifier = $sourceIdentifier;
        $this->pdo = createDatabaseConnection();
        $this->logFile = __DIR__ . "/storage/logs/{$sourceIdentifier}-integrator.log";
    }
    
    /**
     * Procesar datos de tu fuente
     */
    public function procesarDatos($datosBrutos) 
    {
        $this->log("Iniciando procesamiento de " . count($datosBrutos) . " reviews");
        
        // Convertir tus datos al formato estándar
        $datosConvertidos = $this->convertirATuFormato($datosBrutos);
        
        // Usar el adaptador para unificar
        $resultado = ReviewsSchemaAdapter::prepareBulkInsert($datosConvertidos);
        
        if (!empty($resultado['errors'])) {
            foreach ($resultado['errors'] as $fila => $errores) {
                $this->log("Errores en $fila: " . implode(', ', $errores), 'ERROR');
            }
        }
        
        // Insertar datos válidos
        return $this->insertarReviews($resultado['data']);
    }
    
    /**
     * Convierte tus datos específicos al formato del adaptador
     */
    private function convertirATuFormato($datosBrutos) 
    {
        $convertidos = [];
        
        foreach ($datosBrutos as $review) {
            // Adapta según tu estructura de datos
            $convertidos[] = [
                'platform' => $this->sourceIdentifier,
                'reviewer_name' => $review['nombre_usuario'] ?? $review['user'] ?? 'Anónimo',
                'review_text' => $review['contenido'] ?? $review['texto'] ?? '',
                'normalized_rating' => $this->normalizarRating($review['puntuacion'] ?? 0),
                'review_date' => $this->formatearFecha($review['fecha'] ?? null),
                'hotel_id' => $this->mapearHotelId($review['hotel'] ?? null),
                'extraction_source' => 'api', // o 'bulk', 'manual', etc.
                'platform_review_id' => $review['id_externo'] ?? null,
                
                // Campos opcionales
                'sentiment_score' => $this->calcularSentimiento($review),
                'language_detected' => $this->detectarIdioma($review),
                'is_verified' => $this->esVerificada($review),
                'tags' => json_encode($this->extraerTags($review))
            ];
        }
        
        return $convertidos;
    }
    
    private function insertarReviews($reviews) 
    {
        if (empty($reviews)) {
            return ['success' => false, 'error' => 'No hay reviews válidas'];
        }
        
        try {
            $this->pdo->beginTransaction();
            
            $insertados = 0;
            $actualizados = 0;
            $omitidos = 0;
            
            foreach ($reviews as $review) {
                $resultado = $this->insertarOActualizarReview($review);
                
                switch ($resultado) {
                    case 'inserted': $insertados++; break;
                    case 'updated': $actualizados++; break;
                    case 'skipped': $omitidos++; break;
                }
            }
            
            $this->pdo->commit();
            
            $this->log("Completado: $insertados insertadas, $actualizados actualizadas, $omitidos omitidas", 'SUCCESS');
            
            return [
                'success' => true,
                'inserted' => $insertados,
                'updated' => $actualizados,
                'skipped' => $omitidos,
                'total' => count($reviews)
            ];
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            $this->log("Error en transacción: " . $e->getMessage(), 'ERROR');
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    private function insertarOActualizarReview($review) 
    {
        // Verificar si ya existe
        $stmt = $this->pdo->prepare("SELECT id FROM reviews WHERE unique_id = ? LIMIT 1");
        $stmt->execute([$review['unique_id']]);
        
        if ($stmt->fetch()) {
            // Actualizar existente
            $updateFields = [];
            $updateValues = [];
            
            foreach ($review as $campo => $valor) {
                if ($campo !== 'unique_id') {
                    $updateFields[] = "$campo = ?";
                    $updateValues[] = $valor;
                }
            }
            
            $sql = "UPDATE reviews SET " . implode(', ', $updateFields) . " WHERE unique_id = ?";
            $updateValues[] = $review['unique_id'];
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($updateValues) ? 'updated' : 'skipped';
            
        } else {
            // Insertar nuevo
            $columns = array_keys($review);
            $placeholders = array_fill(0, count($columns), '?');
            
            $sql = "INSERT INTO reviews (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute(array_values($review)) ? 'inserted' : 'skipped';
        }
    }
    
    // Métodos helper - personaliza según tu fuente
    private function normalizarRating($rating) 
    {
        // Convertir rating de tu escala a 0-10
        // Ejemplo: si tu fuente usa 0-5, multiplicar por 2
        return min(10, max(0, floatval($rating)));
    }
    
    private function formatearFecha($fecha) 
    {
        if (!$fecha) return date('Y-m-d');
        
        // Convertir tu formato de fecha a YYYY-MM-DD
        if (is_string($fecha)) {
            return date('Y-m-d', strtotime($fecha));
        }
        
        return date('Y-m-d');
    }
    
    private function mapearHotelId($hotelInfo) 
    {
        // Mapear identificador de hotel de tu fuente a tu base de datos
        // Implementar lógica específica según tu caso
        return is_numeric($hotelInfo) ? (int) $hotelInfo : 1;
    }
    
    private function calcularSentimiento($review) 
    {
        // Análisis de sentimiento básico
        $texto = strtolower($review['contenido'] ?? '');
        
        $positivas = ['excelente', 'bueno', 'genial', 'recomiendo', 'perfecto'];
        $negativas = ['malo', 'terrible', 'horrible', 'pésimo', 'no recomiendo'];
        
        $scorePositivo = 0;
        $scoreNegativo = 0;
        
        foreach ($positivas as $palabra) {
            $scorePositivo += substr_count($texto, $palabra);
        }
        
        foreach ($negativas as $palabra) {
            $scoreNegativo += substr_count($texto, $palabra);
        }
        
        $total = $scorePositivo + $scoreNegativo;
        if ($total === 0) return null;
        
        return round(($scorePositivo - $scoreNegativo) / $total, 2);
    }
    
    private function detectarIdioma($review) 
    {
        // Detección básica de idioma
        $texto = strtolower($review['contenido'] ?? '');
        
        if (preg_match('/\b(the|and|is|are|was|were)\b/', $texto)) {
            return 'en';
        } elseif (preg_match('/\b(el|la|es|está|muy|con)\b/', $texto)) {
            return 'es';
        }
        
        return 'auto';
    }
    
    private function esVerificada($review) 
    {
        // Lógica para determinar si una review está verificada
        return isset($review['verificado']) ? (bool) $review['verificado'] : false;
    }
    
    private function extraerTags($review) 
    {
        // Extraer tags automáticamente del contenido
        $texto = strtolower($review['contenido'] ?? '');
        $tags = [];
        
        $keywords = [
            'limpieza' => ['limpio', 'sucio', 'limpieza'],
            'servicio' => ['servicio', 'personal', 'atención'],
            'ubicación' => ['ubicación', 'centro', 'cerca'],
            'precio' => ['precio', 'caro', 'barato'],
            'comida' => ['comida', 'restaurante', 'desayuno']
        ];
        
        foreach ($keywords as $tag => $palabras) {
            foreach ($palabras as $palabra) {
                if (strpos($texto, $palabra) !== false) {
                    $tags[] = $tag;
                    break;
                }
            }
        }
        
        return array_unique($tags);
    }
    
    private function log($mensaje, $nivel = 'INFO') 
    {
        $timestamp = date('Y-m-d H:i:s');
        $entrada = "[$timestamp] [$nivel] $mensaje\n";
        
        echo $entrada;
        file_put_contents($this->logFile, $entrada, FILE_APPEND);
    }
}

// Ejemplo de uso
/*
$integrador = new TuNuevoIntegrador('expedia');

$datosDeExpedia = [
    [
        'nombre_usuario' => 'María García',
        'contenido' => 'Hotel excelente, muy limpio y buen servicio',
        'puntuacion' => 4.5, // escala 0-5
        'fecha' => '2024-12-15',
        'hotel' => 6,
        'verificado' => true
    ]
    // ... más datos
];

$resultado = $integrador->procesarDatos($datosDeExpedia);
echo "Procesados: {$resultado['total']}, Insertados: {$resultado['inserted']}\n";
*/
?>
```

---

## ⚡ **Opción 3: Integración Webhook/Real-time**

### **Para datos que llegan en tiempo real**

```php
<?php
// webhook-receiver.php
require_once 'ReviewsSchemaAdapter.php';

// Recibir webhook
$inputJSON = file_get_contents('php://input');
$datosWebhook = json_decode($inputJSON, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Validar datos del webhook
    $errores = ReviewsSchemaAdapter::validateApifyData($datosWebhook);
    if (!empty($errores)) {
        throw new Exception('Datos inválidos: ' . implode(', ', $errores));
    }
    
    // Mapear y procesar
    $datosUnificados = ReviewsSchemaAdapter::mapApifyToStandard($datosWebhook);
    
    // Insertar en base de datos
    $pdo = createDatabaseConnection();
    // ... lógica de inserción
    
    // Respuesta exitosa
    http_response_code(200);
    echo json_encode(['success' => true, 'id' => $reviewId]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    
    // Log del error
    file_put_contents('webhook-errors.log', date('Y-m-d H:i:s') . " - " . $e->getMessage() . "\n", FILE_APPEND);
}
?>
```

---

## 📊 **Opción 4: Bulk Import desde CSV/Excel**

### **Para importaciones masivas de datos históricos**

```php
<?php
require_once 'ReviewsSchemaAdapter.php';

class BulkImporter 
{
    public function importFromCSV($csvFilePath, $sourceIdentifier = 'bulk') 
    {
        if (!file_exists($csvFilePath)) {
            throw new Exception("Archivo CSV no encontrado: $csvFilePath");
        }
        
        $handle = fopen($csvFilePath, 'r');
        $header = fgetcsv($handle); // Primera fila = headers
        
        $datosParaImportar = [];
        $rowNumber = 1;
        
        while (($row = fgetcsv($handle)) !== FALSE) {
            $rowNumber++;
            
            // Mapear CSV a formato estándar
            $reviewData = array_combine($header, $row);
            
            // Convertir formato CSV a formato del adaptador
            $datosParaImportar[] = [
                'platform' => $reviewData['platform'] ?? $sourceIdentifier,
                'reviewer_name' => $reviewData['guest_name'] ?? 'Imported User',
                'review_text' => $reviewData['review_content'] ?? '',
                'normalized_rating' => floatval($reviewData['rating'] ?? 0),
                'review_date' => date('Y-m-d', strtotime($reviewData['date'] ?? 'now')),
                'hotel_id' => intval($reviewData['hotel_id'] ?? 1),
                'extraction_source' => 'bulk',
                'platform_review_id' => $reviewData['external_id'] ?? "bulk_row_$rowNumber"
            ];
            
            // Procesar en lotes de 100
            if (count($datosParaImportar) >= 100) {
                $this->procesarLote($datosParaImportar);
                $datosParaImportar = [];
            }
        }
        
        // Procesar último lote
        if (!empty($datosParaImportar)) {
            $this->procesarLote($datosParaImportar);
        }
        
        fclose($handle);
    }
    
    private function procesarLote($datos) 
    {
        $resultado = ReviewsSchemaAdapter::prepareBulkInsert($datos);
        
        if (!empty($resultado['data'])) {
            // Insertar usando query preparada
            $query = ReviewsSchemaAdapter::buildInsertQuery($resultado['data']);
            
            $pdo = createDatabaseConnection();
            $stmt = $pdo->prepare($query['sql']);
            $stmt->execute($query['values']);
            
            echo "✅ Lote procesado: {$resultado['processed']} reviews\n";
        }
        
        if (!empty($resultado['errors'])) {
            echo "⚠️  Errores en lote: {$resultado['failed']}\n";
        }
    }
}

// Uso:
/*
$importer = new BulkImporter();
$importer->importFromCSV('reviews_historicas.csv', 'tripadvisor_historical');
*/
?>
```

---

## 🔧 **Configuración y Personalización**

### **1. Configurar Nueva Fuente**

```php
// En tu integrador, personalizar estos valores:

class TuIntegrador extends BaseIntegrator 
{
    protected $sourceConfig = [
        'identifier' => 'tu_fuente',          // Identificador único
        'display_name' => 'Tu Plataforma',    // Nombre para mostrar
        'rating_scale' => 5,                   // Escala de rating (5, 10, 100)
        'date_format' => 'Y-m-d H:i:s',       // Formato de fecha esperado
        'required_fields' => ['user', 'rating'], // Campos obligatorios
        'rate_limit' => 100,                   // Requests por minuto
        'batch_size' => 50,                    // Tamaño de lote para bulk
        'retry_attempts' => 3,                 // Reintentos en caso de error
        'timeout' => 30                        // Timeout en segundos
    ];
}
```

### **2. Mapeo de Campos Personalizado**

```php
// Crear mapeo específico para tu fuente
protected function getFieldMapping() 
{
    return [
        // Tu campo => Campo estándar
        'user_name' => 'reviewer_name',
        'comment' => 'review_text', 
        'score' => 'normalized_rating',
        'date_posted' => 'review_date',
        'property_id' => 'hotel_id',
        'review_id' => 'platform_review_id'
    ];
}
```

### **3. Validación Personalizada**

```php
protected function validateCustomData($data) 
{
    $errors = [];
    
    // Validaciones específicas de tu fuente
    if (isset($data['score']) && ($data['score'] < 0 || $data['score'] > 5)) {
        $errors[] = "Score debe estar entre 0 y 5";
    }
    
    if (isset($data['date_posted']) && strtotime($data['date_posted']) === false) {
        $errors[] = "Formato de fecha inválido";
    }
    
    return $errors;
}
```

---

## 🚦 **Testing y Debugging**

### **1. Script de Prueba**

```php
<?php
// test-integration.php
require_once 'TuNuevoIntegrador.php';

// Datos de prueba
$datosTest = [
    [
        'nombre_usuario' => 'Test User',
        'contenido' => 'Review de prueba',
        'puntuacion' => 4.0,
        'fecha' => '2024-12-15',
        'hotel' => 6
    ]
];

try {
    $integrador = new TuNuevoIntegrador('test_source');
    $resultado = $integrador->procesarDatos($datosTest);
    
    echo "✅ Test exitoso: " . json_encode($resultado, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "❌ Test fallido: " . $e->getMessage() . "\n";
}
?>
```

### **2. Verificar Datos Insertados**

```sql
-- Verificar que los datos se insertaron correctamente
SELECT 
    extraction_source,
    platform,
    COUNT(*) as total_reviews,
    AVG(COALESCE(rating, normalized_rating)) as avg_rating,
    MAX(scraped_at) as last_inserted
FROM reviews 
WHERE extraction_source = 'tu_fuente'
GROUP BY extraction_source, platform;
```

### **3. Debugging Common Issues**

```php
// debug-helper.php
class IntegrationDebugger 
{
    public static function verifySchemaCompatibility() 
    {
        $pdo = createDatabaseConnection();
        
        // Verificar que todas las columnas necesarias existen
        $stmt = $pdo->query("DESCRIBE reviews");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $requiredColumns = [
            'platform', 'reviewer_name', 'review_text', 
            'normalized_rating', 'extraction_source'
        ];
        
        $missing = array_diff($requiredColumns, $columns);
        
        if (empty($missing)) {
            echo "✅ Esquema compatible\n";
            return true;
        } else {
            echo "❌ Columnas faltantes: " . implode(', ', $missing) . "\n";
            return false;
        }
    }
    
    public static function testDataFlow($sampleData) 
    {
        echo "🔍 Testing data flow...\n";
        
        // 1. Test mapping
        $mapped = ReviewsSchemaAdapter::mapApifyToStandard($sampleData);
        echo "✅ Mapping successful: " . count($mapped) . " fields mapped\n";
        
        // 2. Test validation  
        $errors = ReviewsSchemaAdapter::validateApifyData($sampleData);
        if (empty($errors)) {
            echo "✅ Validation passed\n";
        } else {
            echo "❌ Validation failed: " . implode(', ', $errors) . "\n";
        }
        
        // 3. Test unique ID generation
        $uniqueId = $mapped['unique_id'] ?? 'NOT_GENERATED';
        echo "🆔 Unique ID: $uniqueId\n";
        
        return empty($errors);
    }
}
```

---

## 📈 **Monitoreo de Integraciones**

### **1. Dashboard de Fuentes**

```php
// integration-dashboard.php
$pdo = createDatabaseConnection();

$stmt = $pdo->query("
    SELECT 
        COALESCE(extraction_source, 'legacy') as source,
        platform,
        COUNT(*) as total_reviews,
        COUNT(CASE WHEN scraped_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as last_24h,
        AVG(COALESCE(rating, normalized_rating)) as avg_rating,
        MIN(scraped_at) as first_review,
        MAX(scraped_at) as latest_review
    FROM reviews 
    GROUP BY COALESCE(extraction_source, 'legacy'), platform
    ORDER BY total_reviews DESC
");

$sources = $stmt->fetchAll();

echo "📊 DASHBOARD DE INTEGRACIONES\n";
echo str_repeat("=", 60) . "\n";

foreach ($sources as $source) {
    echo "🔗 {$source['source']} ({$source['platform']})\n";
    echo "   📝 Total: {$source['total_reviews']} reviews\n";
    echo "   📅 Últimas 24h: {$source['last_24h']} reviews\n";  
    echo "   ⭐ Rating promedio: " . round($source['avg_rating'], 2) . "/10\n";
    echo "   🕐 Última actividad: {$source['latest_review']}\n\n";
}
```

### **2. Alertas Automáticas**

```php
// alerts.php - Ejecutar vía cron cada hora
$pdo = createDatabaseConnection();

// Verificar fuentes sin actividad reciente
$stmt = $pdo->query("
    SELECT 
        extraction_source,
        MAX(scraped_at) as last_activity,
        TIMESTAMPDIFF(HOUR, MAX(scraped_at), NOW()) as hours_since_last
    FROM reviews 
    WHERE extraction_source != 'manual'
    GROUP BY extraction_source
    HAVING hours_since_last > 24
");

$inactiveSources = $stmt->fetchAll();

foreach ($inactiveSources as $source) {
    echo "⚠️  ALERTA: {$source['extraction_source']} sin actividad por {$source['hours_since_last']} horas\n";
    // Aquí podrías enviar email, Slack, etc.
}
```

---

## 🎯 **Best Practices**

### **1. ✅ DO's**
- ✅ **Usar siempre ReviewsSchemaAdapter** para compatibilidad
- ✅ **Validar datos antes de insertar** con validateApifyData()
- ✅ **Generar unique_id consistente** para evitar duplicados
- ✅ **Usar transacciones** para operaciones bulk
- ✅ **Loggear todas las operaciones** para debugging
- ✅ **Configurar extraction_source** único por integración
- ✅ **Normalizar ratings** a escala 0-10
- ✅ **Manejar errores gracefully** con rollback

### **2. ❌ DON'Ts**
- ❌ **No insertar datos directamente** sin usar el adaptador
- ❌ **No ignorar errores de validación** - siempre verificar
- ❌ **No usar extraction_source duplicado** entre integraciones
- ❌ **No olvidar rate limiting** en APIs externas
- ❌ **No procesar lotes muy grandes** (max 100 por lote)
- ❌ **No hardcodear configuraciones** - usar archivos config
- ❌ **No ignorar el schema unificado** - aprovecha COALESCE
- ❌ **No olvidar limpiar datos** antes de insertar

---

## 🚀 **Casos de Ejemplo Completos**

### **Ejemplo 1: Integrar Google Reviews API**
```bash
# 1. Crear integrador personalizado
cp TuNuevoIntegrador.php GoogleReviewsIntegrator.php

# 2. Personalizar para Google Reviews
# - Configurar API key
# - Mapear campos específicos de Google
# - Implementar paginación de Google Places API

# 3. Configurar cron job
0 */2 * * * cd /tu/directorio && php google-reviews-cron.php
```

### **Ejemplo 2: Webhook de TripAdvisor**
```bash
# 1. Configurar webhook receiver
cp webhook-receiver.php tripadvisor-webhook.php

# 2. Personalizar validación para formato TripAdvisor
# 3. Configurar URL en TripAdvisor: https://tudominio.com/tripadvisor-webhook.php
# 4. Testear con datos de prueba
```

### **Ejemplo 3: Import histórico CSV**
```bash
# 1. Preparar CSV con headers correctos
# 2. Ejecutar import
php bulk-import.php --file=reviews_historicas.csv --source=historical_data

# 3. Verificar resultados
php simple-monitor.php
```

---

## 📞 **Soporte y Troubleshooting**

### **Problemas Comunes**

| Problema | Causa | Solución |
|----------|-------|----------|
| "Column doesn't exist" | Esquema no unificado | Ejecutar `php verify-reviews-schema.php` |
| "Duplicate entry" | unique_id duplicado | Verificar generación de unique_id |
| "Invalid data" | Validación falla | Revisar datos con validateApifyData() |
| "Memory exceeded" | Lotes muy grandes | Reducir batch_size a 50 o menos |
| "Connection timeout" | Rate limiting | Implementar delays entre requests |

### **Scripts de Ayuda**
```bash
# Verificar esquema
php verify-reviews-schema.php

# Monitoreo simple
php simple-monitor.php

# Test de integración
php integration-debugger.php --test-source=tu_fuente

# Ver estadísticas
php integration-dashboard.php
```

---

## 🎉 **¡Tu Integración Está Lista!**

Siguiendo esta guía, tu nueva fuente de datos estará **completamente compatible** con el sistema unificado, aprovechando:

- ✅ **Zero downtime** - Sin interrumpir funcionamiento actual
- ✅ **Compatibilidad garantizada** - Funciona con esquemas legacy y nuevos  
- ✅ **Monitoreo automático** - Tracking de performance y errores
- ✅ **Escalabilidad** - Maneja desde 1 review hasta millones
- ✅ **Mantenimiento simplificado** - Código reutilizable y documentado

**¡Bienvenido al ecosistema reviews unificado!** 🚀