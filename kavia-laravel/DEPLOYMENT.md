# 🚀 Guía de Deployment - Kavia Laravel

## Workflow de Sincronización

### 🔄 **Desarrollo → Hosting**

```
[LOCAL]                    [GITHUB]                [HOSTING]
kavia-laravel/       →     laravel-migration  →    nuevo.soporteclientes.net/
```

---

## 📋 **Setup Inicial en Hosting**

### 1. Clonar repositorio en hosting
```bash
cd /ruta/a/tu/hosting/
git clone https://github.com/juliobrasa/soporteclientes_net.git -b laravel-migration nuevo-laravel
cd nuevo-laravel
```

### 2. Configurar ambiente
```bash
# Copiar configuración de producción
cp .env.production.example .env

# Editar .env con datos específicos de tu hosting
nano .env

# Generar nueva APP_KEY
php artisan key:generate

# Instalar dependencias
composer install --no-dev --optimize-autoloader

# Ejecutar migraciones
php artisan migrate
```

### 3. Configurar permisos
```bash
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache  # Ajustar según tu hosting
```

---

## ⚡ **Desarrollo Diario**

### Para cambios rápidos:
```bash
# Desarrollo local
./sync.sh "Descripción del cambio"

# En hosting
git pull origin laravel-migration
```

### Para releases importantes:
```bash
# Desarrollo local  
./deploy.sh

# Seguir las instrucciones del script
```

---

## 🔧 **Scripts Disponibles**

| Script | Uso | Descripción |
|--------|-----|-------------|
| `./sync.sh "mensaje"` | Sync rápido | Commit + push automático |
| `./deploy.sh` | Release completo | Tests + deploy con opciones |
| `php artisan serve` | Test local | Servidor desarrollo local |

---

## 🌐 **URLs de Testing**

- **Local:** http://localhost:8000/
- **Hosting:** https://nuevo.soporteclientes.net/
- **API Local:** http://localhost:8000/api/
- **API Hosting:** https://nuevo.soporteclientes.net/api/

---

## 🐛 **Troubleshooting**

### Si el hosting no actualiza:
```bash
# En hosting, forzar actualización
git reset --hard HEAD
git clean -fd  
git pull origin laravel-migration
```

### Si hay problemas de permisos:
```bash
# Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Si la BD no conecta:
- Verificar credenciales en `.env`
- Verificar que la BD existe
- Ejecutar: `php artisan tinker --execute="DB::connection()->getPdo()"`

---

## 📝 **Notas Importantes**

1. **Siempre probar en local** antes de hacer sync
2. **El sistema actual sigue funcionando** en soporteclientes.net
3. **Usar branch laravel-migration** para todo el desarrollo
4. **Backup de BD** antes de migraciones importantes

---

## 🎯 **Próximos Pasos**

1. **Crear primer commit** con setup inicial
2. **Probar sync** con hosting 
3. **Configurar .env** en hosting
4. **Empezar migración** módulo Hotels