# Herramientas de Administración Seguras

Este directorio contiene scripts de debug/test que fueron movidos del área pública por seguridad.

## Scripts movidos el 2025-08-09 00:41:48:

- `admin-debug-logs.php`
- `backup-database-secure.php`
- `backup-database.php`
- `backup-extraction-logs.php`
- `basic-test.php`
- `debug-apify-call.php`
- `debug-laravel.php`
- `debug-logger.php`
- `debug-routes.php`
- `direct-booking-test.php`
- `final-test.php`
- `laravel-test.php`
- `migrate-to-unified.php`
- `monitoring-setup.php`
- `performance-final-test.php`
- `repair-laravel.php`
- `run-booking-test.php`
- `secure-debug-scripts.php`
- `setup-automated-extractions.php`
- `setup-daily-cron-simple.php`
- `setup-google-places.php`
- `setup-review-tables.php`
- `test-booking-direct.php`
- `test-booking-extraction.php`
- `test-common-inputs.php`
- `test-compass-google-maps.php`
- `test-correct-actor-ids.php`
- `test-corrected-schema.php`
- `test-db.php`
- `test-different-hotel.php`
- `test-direct.php`
- `test-extraction-direct.php`
- `test-laravel11.php`
- `test-login.php`
- `test-portal-system.php`
- `test-real-extraction.php`
- `test-real-place-id.php`
- `test-unified-panel.php`
- `test.php`
- `test_ai.php`
- `ultra-test.php`
- `update-files.php`
- `verify-apify-setup.php`

## Uso seguro:

1. Estos scripts solo deben ejecutarse en entornos de desarrollo
2. Requieren autenticación de administrador si se acceden vía web
3. Para uso en producción, ejecutar solo desde CLI con acceso root

## Protecciones aplicadas:

- ✅ Movidos fuera del docroot público
- ✅ Permisos restrictivos (700)  
- ✅ .htaccess de protección
- ✅ Archivos de sustitución con autenticación

