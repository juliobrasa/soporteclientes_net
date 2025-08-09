# 🔄 ALTERNATIVAS DE EXTRACCIÓN

## Si no tienes acceso al actor de Apify:

### 1. **Apify Store - Actores públicos:**
- `apify/google-maps-reviews-scraper` - Para Google Reviews
- `apify/booking-scraper` - Para Booking.com  
- `apify/tripadvisor-scraper` - Para TripAdvisor

### 2. **ScrapingBee API:**
```php
// Configuración alternativa con ScrapingBee
$config = [
    'api_key' => 'YOUR_SCRAPINGBEE_KEY',
    'platforms' => ['google', 'booking', 'tripadvisor']
];
```

### 3. **Extractor directo con cURL:**
```php
// Implementar scraper básico
class DirectScraper {
    public function scrapeGoogleReviews($placeId) {
        // Lógica de scraping directo
    }
}
```

### 4. **APIs oficiales (limitadas):**
- **Google Places API**: Hasta 5 reseñas por lugar
- **Booking.com API**: Solo para partners
- **TripAdvisor API**: Descontinuada

## Recomendación:
1. **Mejor opción**: Configurar token real de Apify
2. **Alternativa**: Usar actores públicos de Apify Store
3. **Última opción**: Implementar scraper propio