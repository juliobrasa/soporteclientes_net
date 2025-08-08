# 🔑 CONFIGURACIÓN DEL TOKEN REAL DE APIFY

## Problema actual:
- El sistema usa `demo_token_replace_with_real` 
- Todas las reseñas son simuladas
- No hay extracciones reales en Apify

## Pasos para configurar token real:

### 1. Obtener token real de Apify:
1. Ve a https://console.apify.com/
2. Inicia sesión en tu cuenta Apify
3. Ve a **Settings > Integrations**
4. Copia tu **API Token**

### 2. Actualizar configuración:
```bash
# Editar archivo .env
nano /root/soporteclientes_net/.env

# Cambiar esta línea:
APIFY_API_TOKEN=demo_token_replace_with_real

# Por tu token real:
APIFY_API_TOKEN=apify_api_TU_TOKEN_REAL_AQUI
```

### 3. Verificar que funciona:
```bash
# Probar extracción real
php -r "
require_once 'apify-config.php';
$client = new ApifyClient();
var_dump($client->getDebugInfo());
"
```

## Actor necesario:
- **Actor ID actual**: `tri_angle~hotel-review-aggregator`
- **¿Tienes este actor en tu cuenta?**: Verificar en https://console.apify.com/actors
- **¿Es actor público o privado?**: Necesitas acceso

## Costes de Apify:
- **Precio estimado**: $1.50 por cada 1000 reseñas
- **Extracción de 9 hoteles x 1000 reseñas**: ~$13.50
- **Extracción diaria (100 reseñas/hotel)**: ~$1.35/día

## Verificación del actor:
El actor `tri_angle~hotel-review-aggregator` debe:
- ✅ Existir en tu cuenta Apify
- ✅ Ser público o tener permisos
- ✅ Soportar las plataformas configuradas
- ✅ Aceptar los parámetros que enviamos