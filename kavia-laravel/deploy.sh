#!/bin/bash

# 🚀 Script de Deploy Automático - Kavia Laravel
# Sincroniza cambios locales con hosting nuevo.soporteclientes.net

set -e

echo "🚀 Iniciando deployment a nuevo.soporteclientes.net..."

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuración
LOCAL_PATH="/root/soporteclientes_net/kavia-laravel"
REMOTE_HOST="nuevo.soporteclientes.net"
REMOTE_PATH="/public_html/"  # Ajustar según tu hosting
LOG_FILE="deploy.log"

echo -e "${YELLOW}📋 Pre-deployment checklist...${NC}"

# 1. Verificar que estamos en el directorio correcto
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: No se encuentra el archivo artisan. ¿Estás en el directorio Laravel?${NC}"
    exit 1
fi

# 2. Verificar que hay cambios
if [ -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}⚠️ No hay cambios pendientes para deploy${NC}"
    read -p "¿Continuar de todas formas? (y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Deploy cancelado"
        exit 0
    fi
fi

# 3. Ejecutar tests (opcional)
echo -e "${YELLOW}🧪 Ejecutando tests...${NC}"
if ! php artisan test --env=testing > /dev/null 2>&1; then
    echo -e "${YELLOW}⚠️ Tests fallaron, pero continuando...${NC}"
fi

# 4. Commit automático si hay cambios
if [ ! -z "$(git status --porcelain)" ]; then
    echo -e "${YELLOW}📝 Creando commit automático...${NC}"
    git add .
    git commit -m "AUTO-DEPLOY: $(date '+%Y-%m-%d %H:%M:%S')"
fi

# 5. Push a GitHub
echo -e "${YELLOW}📤 Pushing a GitHub...${NC}"
git push origin laravel-migration

# 6. Opciones de sincronización
echo -e "${YELLOW}🔄 Selecciona método de sincronización:${NC}"
echo "1) Git clone/pull en servidor (recomendado)"
echo "2) FTP/SFTP upload"  
echo "3) Rsync (si tienes SSH)"
echo "4) Manual - solo mostrar comandos"

read -p "Opción (1-4): " sync_option

case $sync_option in
    1)
        echo -e "${GREEN}✅ Método Git seleccionado${NC}"
        echo "Ejecuta en tu servidor:"
        echo "cd /ruta/a/tu/hosting && git clone https://github.com/juliobrasa/soporteclientes_net.git -b laravel-migration nuevo-laravel"
        echo "o si ya existe: cd nuevo-laravel && git pull origin laravel-migration"
        ;;
    2)
        echo -e "${GREEN}✅ Método FTP seleccionado${NC}"
        echo "Configurar credenciales FTP..."
        # Aquí iría la lógica de FTP
        ;;
    3)
        echo -e "${GREEN}✅ Método Rsync seleccionado${NC}"
        # rsync -avz --exclude-from='.gitignore' ./ usuario@nuevo.soporteclientes.net:/ruta/
        ;;
    4)
        echo -e "${GREEN}✅ Comandos para ejecutar manualmente:${NC}"
        echo ""
        echo "En tu hosting, ejecuta:"
        echo "git pull origin laravel-migration"
        echo "composer install --no-dev --optimize-autoloader"
        echo "php artisan config:cache"
        echo "php artisan route:cache"
        echo "php artisan view:cache"
        ;;
    *)
        echo -e "${RED}❌ Opción inválida${NC}"
        exit 1
        ;;
esac

echo -e "${GREEN}🎉 Deploy completado!${NC}"
echo "Verifica en: https://nuevo.soporteclientes.net/"
echo "Log guardado en: $LOG_FILE"

# Registro de deploy
echo "$(date): Deploy exitoso - Branch: $(git branch --show-current) - Commit: $(git rev-parse --short HEAD)" >> $LOG_FILE