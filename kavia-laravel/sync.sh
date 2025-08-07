#!/bin/bash

# ⚡ Script de Sync Rápido - Para desarrollo iterativo
# Uso: ./sync.sh "mensaje del commit"

set -e

# Colores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

MESSAGE=${1:-"Quick sync: $(date '+%H:%M')"}

echo -e "${YELLOW}⚡ Sync rápido iniciado...${NC}"

# Quick commit
git add .
git commit -m "$MESSAGE" || echo "No hay cambios nuevos"

# Push
git push origin laravel-migration

echo -e "${GREEN}✅ Cambios subidos a GitHub${NC}"
echo -e "${YELLOW}💡 En tu hosting ejecuta: git pull origin laravel-migration${NC}"
echo ""
echo -e "${GREEN}🔗 Testing: https://nuevo.soporteclientes.net/${NC}"