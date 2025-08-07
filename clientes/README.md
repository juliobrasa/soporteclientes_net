# Panel de Clientes - FidelitySuite

Este es el panel de usuarios/clientes que replica exactamente el prototipo React proporcionado. Es una interfaz separada del panel de administración, diseñada para que los clientes finales puedan ver sus datos de reputación hotelera.

## Estructura del Proyecto

```
clientes/
├── index.php           # Página principal del dashboard
├── css/
│   └── dashboard.css   # Estilos personalizados
├── js/
│   └── dashboard.js    # Funcionalidad JavaScript
├── api/
│   └── dashboard.php   # API backend para datos
└── README.md           # Este archivo
```

## Características Implementadas

### ✅ Diseño Exacto del Prototipo React
- **Header**: Logo FidelitySuite, selector de hotel, selector de rango de fechas, botón de reporte
- **Sidebar**: Navegación con íconos (Resumen, OTAs, Reseñas)
- **Secciones**: Tres secciones principales con funcionalidad completa

### ✅ Sección Resumen
- **Índice de Reputación Online (IRO)**: Círculo de progreso animado con métricas
- **Índice Semántico**: Círculo de progreso con alertas y mensajes
- **Recomendaciones**: Sistema de sugerencias con navegación
- **Tabla de Dimensiones**: Estadísticas comparativas período vs acumulado

### ✅ Sección OTAs
- **Tabla de Ranking**: Comparación de todas las plataformas (Booking, Google, TripAdvisor, etc.)
- **Métricas por plataforma**: Calificación, cantidad de reseñas, datos acumulados
- **Indicadores visuales**: Tendencias con íconos y colores
- **Botón de contacto**: Para activar más OTAs

### ✅ Sección Reseñas
- **Tarjetas de estadísticas**: Reseñas, calificación promedio, cobertura, NPS, casos
- **Lista de reseñas**: Tarjetas detalladas con toda la información
- **Botones de acción**: Integrar OTA, Traducir, Generar respuesta, Crear caso
- **Sistema de estrellas**: Visualización de ratings
- **Badges de plataforma**: Identificación visual por OTA

### ✅ Funcionalidades Avanzadas
- **API Backend completa**: Conecta con la base de datos real
- **Datos dinámicos**: Se actualiza según hotel y rango de fechas seleccionado
- **Animaciones**: Transiciones suaves y círculos de progreso animados
- **Responsive**: Diseño adaptable a diferentes pantallas
- **Notificaciones**: Sistema de alertas para acciones
- **Loading states**: Indicadores de carga

## Tecnologías Utilizadas

- **Frontend**: HTML5, TailwindCSS, JavaScript ES6+
- **Iconos**: Lucide Icons (exactamente como React)
- **Backend**: PHP 7+, MySQL
- **Animaciones**: CSS3 Transitions y Keyframes

## Configuración

### 1. Acceso
- URL: `https://soporteclientes.net/clientes/`
- El panel usa los datos del sistema de administración existente

### 2. Base de Datos
El panel se conecta automáticamente a las tablas:
- `hoteles`: Información de hoteles
- `reviews`: Reseñas extraídas
- Usa las mismas APIs que el panel admin

### 3. Personalización
- **Hoteles**: Se cargan dinámicamente desde la base de datos
- **Rangos de fecha**: 30, 60, 90 días configurables
- **Límites**: API configurable para paginación

## API Endpoints

### Dashboard Principal
```
GET api/dashboard.php?action=dashboard&hotel_id=6&date_range=30
```

### Datos de OTAs
```
GET api/dashboard.php?action=otas&hotel_id=6&date_range=30
```

### Reseñas
```
GET api/dashboard.php?action=reviews&hotel_id=6&date_range=30&limit=20
```

### Estadísticas
```
GET api/dashboard.php?action=stats&hotel_id=6&date_range=30
```

## Diferencias con el Panel Admin

| Característica | Panel Admin | Panel Clientes |
|----------------|-------------|----------------|
| **Propósito** | Gestión y configuración | Visualización y análisis |
| **Usuarios** | Administradores internos | Clientes/usuarios finales |
| **Funciones** | CRUD, extracciones, configuración | Solo lectura y reportes |
| **Diseño** | Funcional, tablas, formularios | Visualmente atractivo, dashboards |
| **Datos** | Todos los hoteles | Hotel específico del cliente |

## Métricas Calculadas

### Índice de Reputación Online (IRO)
- **Rating Score**: 40% del peso total
- **Volume Score**: 30% del peso total  
- **Sentiment Score**: 30% del peso total

### Índice Semántico
- Análisis de palabras clave negativas/positivas
- Cálculo basado en el contenido de las reseñas
- Estados: Bueno, Regular, Malo

## Funcionalidades Futuras

### 🔄 En desarrollo
- **Autenticación**: Login específico por cliente
- **Multi-hotel**: Soporte para cadenas hoteleras
- **Exportar reportes**: PDF, Excel
- **Alertas tiempo real**: Notificaciones automáticas
- **Comparación competencia**: Benchmarking
- **Análisis predictivo**: IA para tendencias

### 🎯 Planeadas
- **App móvil**: Version responsive completa
- **Integración WhatsApp**: Notificaciones directas
- **Dashboard personalizable**: Widgets configurables
- **Análisis de sentimientos avanzado**: NLP mejorado

## Soporte

Para soporte técnico o preguntas sobre la implementación, contacta al equipo de desarrollo.

## Changelog

### v1.0.0 (Actual)
- ✅ Replica exacta del prototipo React
- ✅ API backend completa
- ✅ Datos reales desde base de datos
- ✅ Responsive design
- ✅ Animaciones y transiciones