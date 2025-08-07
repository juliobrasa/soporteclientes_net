#!/bin/bash

# ================================================================
# SCRIPT DE DEPLOYMENT PARA KAVIA LARAVEL
# ================================================================

echo "🚀 Iniciando deployment de Kavia Laravel..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para mostrar mensajes
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Función para verificar si un comando existe
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# Verificar prerrequisitos
print_status "Verificando prerrequisitos..."

if ! command_exists php; then
    print_error "PHP no está instalado"
    exit 1
fi

if ! command_exists composer; then
    print_error "Composer no está instalado"
    exit 1
fi

# Verificar versión de PHP
PHP_VERSION=$(php -v | head -n1 | cut -d" " -f2 | cut -d"." -f1,2)
if [[ $(echo "$PHP_VERSION < 8.2" | bc -l) ]]; then
    print_warning "Se recomienda PHP 8.2 o superior. Versión actual: $PHP_VERSION"
fi

print_success "Prerrequisitos verificados"

# ================================================================
# 1. BACKUP
# ================================================================

print_status "Creando backup..."

BACKUP_DIR="backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

# Backup de archivos críticos si existen
if [ -f ".env" ]; then
    cp .env "$BACKUP_DIR/.env.backup"
fi

if [ -d "storage" ]; then
    cp -r storage "$BACKUP_DIR/storage_backup"
fi

print_success "Backup creado en $BACKUP_DIR"

# ================================================================
# 2. INSTALACIÓN DE DEPENDENCIAS
# ================================================================

print_status "Instalando dependencias de Composer..."
composer install --optimize-autoloader --no-dev --no-interaction

if [ $? -ne 0 ]; then
    print_error "Error instalando dependencias"
    exit 1
fi

print_success "Dependencias instaladas"

# ================================================================
# 3. CONFIGURACIÓN DE ENTORNO
# ================================================================

print_status "Configurando entorno..."

# Crear .env si no existe
if [ ! -f ".env" ]; then
    print_status "Creando archivo .env desde .env.example"
    cp .env.example .env
    
    # Generar APP_KEY
    php artisan key:generate --no-interaction
    
    print_warning "⚠️  IMPORTANTE: Configura las variables de entorno en .env"
    print_warning "   - DB_DATABASE, DB_USERNAME, DB_PASSWORD"
    print_warning "   - APP_URL"
    print_warning "   - APP_ENV=production"
fi

# ================================================================
# 4. PERMISOS
# ================================================================

print_status "Configurando permisos..."

# Crear directorios si no existen
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Configurar permisos
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Si estamos ejecutando como www-data o similar
if [ "$USER" = "www-data" ] || [ "$USER" = "apache" ] || [ "$USER" = "nginx" ]; then
    chown -R $USER:$USER storage bootstrap/cache
else
    print_warning "Ejecuta como usuario web server para permisos óptimos"
fi

print_success "Permisos configurados"

# ================================================================
# 5. BASE DE DATOS
# ================================================================

print_status "Configurando base de datos..."

# Verificar conexión a base de datos
php artisan migrate:status > /dev/null 2>&1
if [ $? -ne 0 ]; then
    print_error "Error conectando a la base de datos"
    print_error "Verifica la configuración en .env"
    exit 1
fi

# Ejecutar migraciones
print_status "Ejecutando migraciones..."
php artisan migrate --force --no-interaction

if [ $? -ne 0 ]; then
    print_error "Error ejecutando migraciones"
    exit 1
fi

# Crear usuario administrador
print_status "Creando usuario administrador..."
php artisan db:seed --class=AdminUserSeeder --force

print_success "Base de datos configurada"

# ================================================================
# 6. OPTIMIZACIÓN
# ================================================================

print_status "Optimizando aplicación..."

# Limpiar caches
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Generar caches para producción
php artisan config:cache
php artisan route:cache
php artisan view:cache

print_success "Aplicación optimizada"

# ================================================================
# 7. VERIFICACIÓN
# ================================================================

print_status "Verificando deployment..."

# Test de conexión a base de datos
php artisan migrate:status > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "✓ Conexión a base de datos OK"
else
    print_error "✗ Error en conexión a base de datos"
fi

# Verificar que las rutas estén disponibles
php artisan route:list | grep -q "api/test"
if [ $? -eq 0 ]; then
    print_success "✓ Rutas API registradas"
else
    print_warning "⚠ No se encontraron algunas rutas API"
fi

# Verificar permisos de escritura
if [ -w "storage/logs" ]; then
    print_success "✓ Permisos de escritura OK"
else
    print_error "✗ Sin permisos de escritura en storage/logs"
fi

# ================================================================
# 8. INFORMACIÓN FINAL
# ================================================================

print_success "🎉 Deployment completado!"
echo ""
echo "==============================================="
echo "📋 INFORMACIÓN DEL DEPLOYMENT"
echo "==============================================="
echo "📅 Fecha: $(date)"
echo "🐘 PHP Version: $(php -v | head -n1)"
echo "🎼 Laravel Version: $(php artisan --version)"
echo ""
echo "🔐 USUARIOS CREADOS:"
echo "   - admin@kavia.com (admin123)"
echo "   - test@kavia.com (test123)"
echo ""
echo "🔧 ENDPOINTS PRINCIPALES:"
echo "   - GET  /api/test (público)"
echo "   - POST /api/auth/login (público)"
echo "   - GET  /api/hotels (requiere autenticación)"
echo "   - GET  /api/legacy/hotels (público)"
echo ""
echo "⚠️  NEXT STEPS:"
echo "   1. Configura el servidor web (Nginx/Apache)"
echo "   2. Configura SSL/HTTPS"
echo "   3. Configura cron para Laravel Scheduler:"
echo "      * * * * * cd $(pwd) && php artisan schedule:run >> /dev/null 2>&1"
echo "   4. Configura supervisord para queues si es necesario"
echo ""
echo "📁 Backup creado en: $BACKUP_DIR"
echo "==============================================="

# ================================================================
# 9. HEALTH CHECK OPCIONAL
# ================================================================

read -p "¿Ejecutar health check? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_status "Ejecutando health check..."
    
    # Test endpoint
    if command_exists curl; then
        RESPONSE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/api/test 2>/dev/null || echo "000")
        if [ "$RESPONSE" = "200" ]; then
            print_success "✓ API endpoint respondiendo correctamente"
        else
            print_warning "⚠ API endpoint no responde (código: $RESPONSE)"
        fi
    else
        print_warning "curl no disponible para health check"
    fi
fi

print_success "Deployment finalizado! 🚀"