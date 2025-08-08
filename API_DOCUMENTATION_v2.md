# 📖 API Documentation v2.0 - Reviews API Unificada

## 🚀 Introducción

La **Reviews API v2.0** es la versión unificada que combina compatibilidad total entre datos legacy y del sistema Apify, ofreciendo funcionalidades expandidas y mejor rendimiento.

### ✨ **Novedades de la v2.0**
- ✅ **Esquema unificado** - Compatibilidad total Apify + Legacy
- ✅ **Campos expandidos** - sentiment_score, tags, extraction_source
- ✅ **Filtros avanzados** - Por fuente, verificación, fechas
- ✅ **Performance optimizada** - Índices y queries mejoradas
- ✅ **Meta información** - Información de compatibilidad y versión

---

## 🔗 **Endpoints Disponibles**

### **Base URL:** `https://soporteclientes.net/api/`

| Endpoint | Método | Descripción | Versión |
|----------|--------|-------------|---------|
| `reviews.php` | GET | **API Principal** - Lista reviews con filtros | v2.0 |
| `reviews.php?action=stats` | GET | Estadísticas generales | v2.0 |
| `reviews.php?action=compatibility` | GET | Info de compatibilidad | v2.0 |
| `reviews.php?action=sources` | GET | Fuentes de extracción | v2.0 |
| `reviews-v1.php` | GET | **Alias compatibilidad** | v1.0 |

---

## 📊 **1. Listar Reviews - `/api/reviews.php`**

### **Parámetros de Consulta**

| Parámetro | Tipo | Descripción | Ejemplo |
|-----------|------|-------------|---------|
| `page` | int | Página (default: 1) | `?page=2` |
| `limit` | int | Elementos por página (max: 100) | `?limit=50` |
| `hotel_id` | int | Filtrar por hotel específico | `?hotel_id=6` |
| `platform` | string | Filtrar por plataforma | `?platform=booking` |
| `rating_min` | float | Calificación mínima | `?rating_min=7.5` |
| `rating_max` | float | Calificación máxima | `?rating_max=9.0` |
| `date_from` | date | Fecha desde (YYYY-MM-DD) | `?date_from=2024-01-01` |
| `date_to` | date | Fecha hasta (YYYY-MM-DD) | `?date_to=2024-12-31` |
| `has_response` | boolean | Solo con/sin respuesta | `?has_response=true` |
| `extraction_source` | enum | Fuente: apify, manual, api, bulk | `?extraction_source=apify` |
| `verified_only` | boolean | Solo reviews verificadas | `?verified_only=true` |
| `search` | string | Búsqueda en contenido | `?search=limpio` |

### **Ejemplo de Request**
```bash
GET /api/reviews.php?hotel_id=6&platform=booking&rating_min=8&limit=20&page=1
```

### **Ejemplo de Response**
```json
{
  "success": true,
  "data": [
    {
      "id": "57521f922df34b17d00132f82ddf848d",
      "internal_id": 11263,
      "guest": "María González",
      "country": "España",
      "date": "10 Dec 2024",
      "scraped_date": "08 Aug 2025 23:29",
      "platform": "booking",
      "platform_review_id": "booking_123456",
      "extraction_source": "apify",
      "rating": 9.2,
      "sentiment": "positive",
      "sentiment_score": 0.87,
      "title": "Excelente hotel",
      "content": {
        "full_text": "Hotel excelente, muy limpio y ubicación perfecta. El personal muy amable.",
        "positive": "Hotel excelente, muy limpio y ubicación perfecta",
        "negative": null
      },
      "response": {
        "has_response": true,
        "text": "Gracias por su reseña, esperamos verle pronto."
      },
      "metadata": {
        "language": "es",
        "traveler_type": "Pareja",
        "helpful_votes": 3,
        "is_verified": true,
        "processed_at": "2024-12-10T15:30:00Z",
        "hotel_id": 6
      },
      "tags": ["limpieza", "ubicación", "servicio"]
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 1144,
    "total_pages": 58,
    "has_next": true,
    "has_prev": false
  },
  "filters": {
    "platforms": [
      {"platform": "booking", "count": 1143},
      {"platform": "google", "count": 1}
    ],
    "ratings": [
      {"rating": "10.00", "count": 327},
      {"rating": "9.00", "count": 187}
    ]
  },
  "meta": {
    "unified_schema": true,
    "api_version": "2.0",
    "generated_at": "2025-08-08T23:33:24+00:00"
  }
}
```

---

## 📈 **2. Estadísticas - `/api/reviews.php?action=stats`**

### **Response**
```json
{
  "success": true,
  "data": {
    "total_reviews": 1144,
    "by_platform": [
      {"platform": "booking", "count": 1143, "avg_rating": 7.7},
      {"platform": "google", "count": 1, "avg_rating": 8.3}
    ],
    "by_extraction_source": [
      {"source": "manual", "count": 1142, "avg_rating": 7.7},
      {"source": "apify", "count": 2, "avg_rating": 8.3}
    ],
    "recent_activity": [
      {"date": "2025-08-08", "count": 5},
      {"date": "2025-08-04", "count": 353}
    ]
  }
}
```

---

## 🔧 **3. Compatibilidad - `/api/reviews.php?action=compatibility`**

### **Response**
```json
{
  "success": true,
  "data": {
    "schema_a_coverage": 100.0,
    "schema_b_coverage": 100.0,
    "total_columns": 58,
    "unified": true,
    "sources": [
      {"extraction_source": "manual", "count": 1142},
      {"extraction_source": "apify", "count": 2}
    ]
  }
}
```

---

## 🎯 **4. Fuentes de Extracción - `/api/reviews.php?action=sources`**

### **Response**
```json
{
  "success": true,
  "data": [
    {
      "source": "manual",
      "total_reviews": 1142,
      "recent_reviews": 5,
      "first_review": "2024-01-15T10:30:00Z",
      "latest_review": "2025-08-08T23:29:07Z"
    },
    {
      "source": "apify",
      "total_reviews": 2,
      "recent_reviews": 2,
      "first_review": "2025-08-08T23:29:07Z",
      "latest_review": "2025-08-08T23:34:34Z"
    }
  ]
}
```

---

## 🚦 **Códigos de Respuesta**

| Código | Descripción | Ejemplo |
|--------|-------------|---------|
| **200** | ✅ Éxito | Datos devueltos correctamente |
| **400** | ❌ Bad Request | Parámetros inválidos |
| **500** | ❌ Server Error | Error interno del servidor |

### **Formato de Error**
```json
{
  "success": false,
  "error": "Invalid action: invalid_action",
  "code": 400
}
```

---

## 🔄 **Migración desde v1.0**

### **Compatibilidad Backward**
La API v2.0 es **100% compatible** con v1.0. Los endpoints existentes siguen funcionando:

```bash
# ✅ Funciona igual que antes
GET /api/reviews.php?hotel=6&page=1&limit=20

# ✅ Nuevo alias de compatibilidad  
GET /api/reviews-v1.php?hotel=6&page=1&limit=20
```

### **Nuevas Funcionalidades v2.0**
```bash
# 🆕 Filtros por fuente de extracción
GET /api/reviews.php?extraction_source=apify

# 🆕 Solo reviews verificadas
GET /api/reviews.php?verified_only=true

# 🆕 Filtros de calificación más precisos
GET /api/reviews.php?rating_min=8.5&rating_max=9.2

# 🆕 Información de compatibilidad
GET /api/reviews.php?action=compatibility
```

---

## 📱 **Ejemplos de Uso**

### **JavaScript/Fetch**
```javascript
// Obtener reviews recientes de Apify
const response = await fetch('/api/reviews.php?extraction_source=apify&limit=10');
const data = await response.json();

if (data.success) {
  data.data.forEach(review => {
    console.log(`${review.guest}: ${review.rating}/10 - ${review.platform}`);
  });
}
```

### **Python/Requests**
```python
import requests

# Estadísticas generales
response = requests.get('https://soporteclientes.net/api/reviews.php?action=stats')
data = response.json()

print(f"Total reviews: {data['data']['total_reviews']}")
for platform in data['data']['by_platform']:
    print(f"{platform['platform']}: {platform['count']} reviews")
```

### **cURL**
```bash
# Reviews con alta calificación
curl "https://soporteclientes.net/api/reviews.php?rating_min=9&limit=5" \
  -H "Accept: application/json"
```

---

## 🔒 **Autenticación y Límites**

### **Rate Limiting**
- **Límite:** 1000 requests/hora por IP
- **Headers de respuesta:**
  ```
  X-RateLimit-Limit: 1000
  X-RateLimit-Remaining: 999
  X-RateLimit-Reset: 1623456789
  ```

### **CORS**
- **Origins permitidos:** `*` (configurar según necesidades)
- **Métodos:** `GET, POST, OPTIONS`
- **Headers:** `Content-Type, Authorization`

---

## 🆕 **Funcionalidades Avanzadas v2.0**

### **1. Campos Unificados**
La API automáticamente maneja la compatibilidad entre esquemas:
- `user_name` ↔️ `reviewer_name`
- `rating` ↔️ `normalized_rating`
- `property_response` ↔️ `response_from_owner`
- `source_platform` ↔️ `platform`

### **2. Sentiment Analysis**
```json
{
  "sentiment": "positive",        // positive, negative, neutral
  "sentiment_score": 0.87,       // -1.00 a 1.00
}
```

### **3. Tagging Automático**
```json
{
  "tags": ["limpieza", "ubicación", "servicio", "precio"]
}
```

### **4. Metadatos Expandidos**
```json
{
  "metadata": {
    "is_verified": true,
    "processed_at": "2024-12-10T15:30:00Z",
    "language": "es",
    "extraction_source": "apify"
  }
}
```

---

## 🛠️ **Herramientas de Desarrollo**

### **Postman Collection**
Importar collection para testing rápido:
```json
{
  "info": {
    "name": "Reviews API v2.0",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "List Reviews",
      "request": {
        "method": "GET",
        "header": [],
        "url": {
          "raw": "{{base_url}}/api/reviews.php?limit=10",
          "host": ["{{base_url}}"],
          "path": ["api", "reviews.php"],
          "query": [{"key": "limit", "value": "10"}]
        }
      }
    }
  ]
}
```

### **Testing Endpoints**
```bash
# Health check
curl https://soporteclientes.net/api/reviews.php?action=compatibility

# Performance test  
curl https://soporteclientes.net/api/reviews.php?limit=100&page=1 -w "@curl-format.txt"
```

---

## 📞 **Soporte**

### **Contacto**
- **Email:** soporte@soporteclientes.net
- **Documentación:** Esta documentación se actualiza con cada release

### **Versionado**
- **Actual:** v2.0
- **Deprecation:** v1.0 será soportada hasta 2026
- **Migration guide:** Incluido en esta documentación

### **Changelog**
- **v2.0.0** (2025-08-08): Esquema unificado, compatibilidad Apify
- **v1.0.0** (2024-01-01): Versión inicial

---

**🚀 ¡La API Reviews v2.0 está lista para integraciones avanzadas!**