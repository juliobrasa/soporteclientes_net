# Security Configuration

## Environment Variables Setup

Para seguridad, las variables sensibles deben configurarse como variables de entorno del sistema en lugar del archivo `.env`.

### Configuración del Token Apify

```bash
# En el servidor de producción, configurar como variable de entorno:
export APIFY_API_TOKEN="your_real_apify_token_here"

# Agregar al ~/.bashrc para persistencia:
echo 'export APIFY_API_TOKEN="your_real_apify_token_here"' >> ~/.bashrc
```

### Variables de Entorno Requeridas

- `APIFY_API_TOKEN`: Token de API de Apify (obligatorio)
- Las demás configuraciones pueden mantenerse en `.env`

### Notas de Seguridad

1. **Nunca commitear tokens reales** al repositorio
2. Usar variables de entorno para datos sensibles
3. El archivo `.env.example` muestra la estructura sin exponer secretos
4. En producción, configurar todas las variables de entorno directamente en el sistema

### Verificación

Para verificar que las variables están configuradas:

```bash
echo $APIFY_API_TOKEN  # Debe mostrar tu token
```