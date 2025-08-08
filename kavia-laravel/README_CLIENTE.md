# 🏨 FidelitySuite - Panel de Clientes

Sistema completo de autenticación y gestión de niveles de clientes para el dashboard de reputación hotelera.

## 🚀 Características Implementadas

### ✅ Sistema de Autenticación
- **Guards separados** para clientes y administradores
- **Middleware personalizado** con control granular de permisos
- **Sesiones seguras** con verificación de suscripción
- **API REST** completa para aplicaciones móviles

### 🏆 Niveles de Cliente

| Nivel | Precio | Hoteles | Reseñas/mes | Características |
|-------|--------|---------|-------------|-----------------|
| **Demo** | Gratis | 1 | 100 | Solo resumen básico |
| **Básico** | $29.99 | 1 | 500 | Dashboard + reseñas |
| **Profesional** | $79.99 | 3 | 2,000 | + IA + reportes + OTAs |
| **Empresarial** | $199.99 | 10 | 10,000 | + análisis competencia |

### 🔐 Control de Permisos

#### Por Módulos:
- `resumen` - Dashboard principal con IRO y métricas
- `otas` - Análisis por plataformas (Booking, Google, etc.)
- `reseñas` - Gestión completa de reseñas

#### Por Características:
- `dashboard_view` - Acceso al dashboard
- `reviews_view` - Ver reseñas detalladas
- `export_reports` - Exportar reportes
- `ai_responses` - Generar respuestas con IA
- `advanced_analytics` - Analytics avanzados
- `competitor_analysis` - Análisis de competencia
- `custom_alerts` - Alertas personalizadas

## 🌐 URLs de Acceso

### Panel Web
- **Login:** https://soporteclientes.net/client/login
- **Registro:** https://soporteclientes.net/client/register
- **Dashboard:** https://soporteclientes.net/client/dashboard
- **Suscripción Expirada:** https://soporteclientes.net/client/subscription-expired

### API REST
- **Auth Login:** `POST /api/client/auth/login`
- **Auth Info:** `GET /api/client/auth/me`
- **Auth Logout:** `POST /api/client/auth/logout`
- **Dashboard:** `GET /api/client/dashboard`
- **OTAs:** `GET /api/client/otas`
- **Reseñas:** `GET /api/client/reviews`
- **Stats:** `GET /api/client/stats`

## 🔑 Usuarios de Prueba

### Demo - Plan Gratuito
```
Email: demo@cliente.com
Password: demo123
Permisos: Solo resumen básico
```

### Profesional - Plan Completo
```
Email: admin@terracaribe.com
Password: terracaribe2025
Permisos: Dashboard + OTAs + IA + reportes
```

### Empresarial - Plan Premium
```
Email: admin@grupohotels.com
Password: premium2025
Permisos: Todo incluido + análisis competencia
```

## 🏗️ Arquitectura Técnica

### Base de Datos
```sql
client_levels           -- Definición de planes
├── id, name, display_name
├── features (JSON)     -- Características permitidas
├── modules (JSON)      -- Módulos accesibles
└── max_hotels, max_reviews_per_month

client_users           -- Usuarios del sistema
├── id, name, email, password
├── client_level_id    -- Relación con nivel
├── subscription_start, subscription_end
├── subscription_status (active|trial|expired|canceled)
└── custom_limits (opcionales)

client_hotel_access    -- Acceso a hoteles específicos
├── client_user_id, hotel_id
├── active, permissions (JSON)
└── timestamps
```

### Middleware
```php
ClientAuth              -- Autenticación base
├── Verifica login activo
├── Valida usuario activo
└── Confirma suscripción vigente

ClientPermissions       -- Control granular
├── Verifica permisos por módulo
├── Valida características específicas
└── Controla acceso a hoteles
```

### Controladores
```php
AuthController          -- Gestión de autenticación
├── login/logout (web y API)
├── registro con selección de plan
└── manejo de suscripciones

DashboardController     -- Panel principal
├── dashboard data con IRO/semántico
├── análisis por OTAs
├── gestión de reseñas
└── estadísticas generales
```

## 🔄 Estados de Suscripción

- **`active`** - Suscripción pagada y vigente
- **`trial`** - Período de prueba (30 días)
- **`expired`** - Suscripción vencida
- **`canceled`** - Suscripción cancelada por el usuario

## 🛡️ Seguridad Implementada

### Autenticación
- Guards separados por tipo de usuario
- Tokens CSRF en formularios web
- Sesiones seguras con Laravel

### Autorización  
- Middleware de permisos granular
- Validación por módulos y características
- Control de acceso a hoteles específicos

### Validaciones
- Verificación de suscripción activa
- Límites por plan (hoteles, reseñas)
- Estados de cuenta válidos

## 🚀 Deployment

El sistema está completamente integrado con la aplicación Laravel existente:

1. **Migraciones ejecutadas** - Tablas creadas con datos de prueba
2. **Rutas configuradas** - Web y API completamente funcionales  
3. **Middleware registrado** - Control de acceso activo
4. **Vistas responsive** - Compatible con móvil y desktop
5. **Assets compilados** - CSS y JS optimizados con Vite

## 📞 Soporte

Para soporte técnico o preguntas:
- **Email:** soporte@soporteclientes.net
- **Teléfono:** +52 998 123 4567

---

## 📝 Changelog

### v1.0.0 (Actual)
- ✅ Sistema completo de autenticación
- ✅ 4 niveles de cliente implementados
- ✅ Panel migrado a Laravel MVC
- ✅ Control granular de permisos
- ✅ APIs REST completas
- ✅ Vistas responsive
- ✅ Usuarios de prueba incluidos

**🤖 Generado con [Claude Code](https://claude.ai/code)**