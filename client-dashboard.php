<?php
session_start();

// Verificar autenticación
if (!isset($_SESSION['client_user'])) {
    header('Location: client-login.php');
    exit();
}

$user = $_SESSION['client_user'];
$features = $user['features'];
$modules = $user['modules'];

// Función helper para verificar permisos
function hasFeature($feature, $features) {
    return isset($features[$feature]) && $features[$feature] === true;
}

function hasModule($module, $modules) {
    return isset($modules[$module]) && $modules[$module] === true;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FidelitySuite - Dashboard de Reputación Hotelera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>
    <style>
        /* Estilos personalizados para el dashboard */
        [data-lucide] {
            stroke-width: 2;
            stroke: currentColor;
            fill: none;
        }

        .menu-button {
            transition: all 0.2s ease-in-out;
        }

        .menu-button:hover {
            transform: translateX(2px);
        }

        .menu-button.active {
            background-color: #06b6d4 !important;
            color: white !important;
            border-left: 4px solid #0891b2;
        }

        .menu-button:not(.active) {
            color: #d1d5db;
        }

        .content-section {
            animation: slideInUp 0.4s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #hotelSelector {
            min-width: 220px;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-4">
                    <div class="text-xl font-bold text-gray-900">
                        <span class="text-blue-600">Fidelity</span>
                        <span class="text-cyan-500">Suite</span>
                    </div>
                    
                    <!-- Hotel Selector -->
                    <div class="relative">
                        <select id="hotelSelector" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <?php if (!empty($user['hotels'])): ?>
                                <?php foreach ($user['hotels'] as $hotel): ?>
                                    <option value="<?php echo htmlspecialchars($hotel['id']); ?>">
                                        <?php echo htmlspecialchars($hotel['nombre_hotel']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="">Sin hoteles asignados</option>
                            <?php endif; ?>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="text-right">
                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="text-xs text-gray-500">Plan <?php echo htmlspecialchars($user['level_display']); ?></div>
                    </div>
                    
                    <select id="dateRange" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                        <option value="30">Últimos 30 días</option>
                        <option value="60">Últimos 60 días</option>
                        <option value="90">Últimos 90 días</option>
                    </select>
                    
                    <button class="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50">
                        Reporte
                    </button>

                    <!-- Logout -->
                    <form method="POST" action="client-auth.php" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="flex items-center p-2 rounded-lg text-gray-400 hover:text-gray-600 transition">
                            <i data-lucide="log-out" class="h-4 w-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-800 min-h-screen">
            <nav class="mt-8">
                <?php if (hasModule('resumen', $modules)): ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors bg-cyan-500 text-white" data-section="resumen">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span>Resumen</span>
                </button>
                <?php else: ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left text-gray-500 cursor-not-allowed" disabled>
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span>Resumen</span>
                </button>
                <?php endif; ?>

                <?php if (hasModule('otas', $modules)): ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors text-gray-300" data-section="otas">
                    <i data-lucide="external-link" class="w-5 h-5"></i>
                    <span>OTAs</span>
                </button>
                <?php else: ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left text-gray-500 cursor-not-allowed" disabled>
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span>OTAs</span>
                </button>
                <?php endif; ?>

                <?php if (hasModule('reseñas', $modules)): ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors text-gray-300" data-section="reseñas">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                    <span>Reseñas</span>
                </button>
                <?php else: ?>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left text-gray-500 cursor-not-allowed" disabled>
                    <i data-lucide="lock" class="w-5 h-5"></i>
                    <span>Reseñas</span>
                </button>
                <?php endif; ?>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Resumen Section -->
            <div id="resumen-section" class="content-section space-y-6">
                <?php if (hasModule('resumen', $modules)): ?>
                <!-- Header informativo -->
                <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                    <p class="text-yellow-800">
                        Bienvenido a tu dashboard de reputación. Plan actual: <strong><?php echo htmlspecialchars($user['level_display']); ?></strong>
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- IRO Score -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Índice de Reputación Online (IRO)</h3>
                                <p class="text-sm text-gray-600" id="iro-status">Calculando...</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-center mb-6">
                            <div class="relative">
                                <svg width="120" height="120" class="transform -rotate-90">
                                    <circle cx="60" cy="60" r="52" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                                    <circle 
                                        id="iro-progress"
                                        cx="60" cy="60" r="52" 
                                        stroke="#06b6d4" 
                                        stroke-width="8" 
                                        fill="none"
                                        stroke-dasharray="326.73"
                                        stroke-dashoffset="163.365"
                                        stroke-linecap="round"
                                        class="transition-all duration-500"
                                    ></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span id="iro-score" class="text-3xl font-bold text-gray-900">--</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Calificación</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span class="text-sm font-medium">--</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Cobertura</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span class="text-sm font-medium">--</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Reseñas</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                                    </div>
                                    <span class="text-sm font-medium">--</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <span class="text-blue-600 text-sm font-medium">Cargando datos...</span>
                        </div>
                    </div>

                    <!-- Stats adicionales -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Estadísticas del período</h3>
                                <p class="text-sm text-gray-600">Últimos 30 días</p>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Total reseñas</span>
                                <span class="text-lg font-semibold">--</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Promedio calificación</span>
                                <span class="text-lg font-semibold">--</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">OTAs activas</span>
                                <span class="text-lg font-semibold">--</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-sm text-gray-600">Cobertura</span>
                                <span class="text-lg font-semibold">--%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Información del plan -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="star" class="text-yellow-500 w-5 h-5"></i>
                            <h3 class="text-lg font-semibold text-gray-900">Tu Plan</h3>
                        </div>
                        
                        <div class="space-y-3">
                            <div class="p-3 bg-blue-50 rounded-lg">
                                <div class="font-semibold text-blue-900"><?php echo htmlspecialchars($user['level_display']); ?></div>
                                <div class="text-sm text-blue-700">Estado: <?php echo ucfirst($user['subscription_status']); ?></div>
                            </div>
                            
                            <div class="space-y-2 text-sm">
                                <div class="flex items-center gap-2">
                                    <?php if (hasModule('resumen', $modules)): ?>
                                        <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                        <span class="text-green-700">Módulo Resumen</span>
                                    <?php else: ?>
                                        <i data-lucide="x" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-red-700">Módulo Resumen</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <?php if (hasModule('otas', $modules)): ?>
                                        <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                        <span class="text-green-700">Módulo OTAs</span>
                                    <?php else: ?>
                                        <i data-lucide="x" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-red-700">Módulo OTAs</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <?php if (hasModule('reseñas', $modules)): ?>
                                        <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                        <span class="text-green-700">Módulo Reseñas</span>
                                    <?php else: ?>
                                        <i data-lucide="x" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-red-700">Módulo Reseñas</span>
                                    <?php endif; ?>
                                </div>

                                <div class="flex items-center gap-2">
                                    <?php if (hasFeature('ai_responses', $features)): ?>
                                        <i data-lucide="check" class="w-4 h-4 text-green-500"></i>
                                        <span class="text-green-700">Respuestas IA</span>
                                    <?php else: ?>
                                        <i data-lucide="x" class="w-4 h-4 text-red-500"></i>
                                        <span class="text-red-700">Respuestas IA</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <span class="text-sm text-gray-600">Hoteles: <?php echo count($user['hotels']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de dimensiones -->
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Dimensiones de la reputación online</h3>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 text-sm font-medium text-gray-600"></th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-600">Período</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-600">Acumulado 2025</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b">
                                    <td class="py-3 text-sm text-gray-900">Calificaciones en OTAs</td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">--</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">--</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 text-sm text-gray-900">Cantidad de reseñas</td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">--</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">--</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php else: ?>
                <!-- Módulo no disponible -->
                <div class="bg-gray-100 rounded-lg p-8 text-center">
                    <i data-lucide="lock" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Módulo Resumen no disponible</h3>
                    <p class="text-gray-500 mb-4">Este módulo no está incluido en tu plan actual.</p>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Actualizar Plan
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- OTAs Section -->
            <div id="otas-section" class="content-section space-y-6" style="display: none;">
                <?php if (hasModule('otas', $modules)): ?>
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Ranking por OTA's</h3>
                    </div>
                    
                    <div class="text-center py-8">
                        <i data-lucide="loader" class="w-8 h-8 text-blue-500 mx-auto mb-4 animate-spin"></i>
                        <p class="text-gray-600">Cargando datos de OTAs...</p>
                    </div>
                </div>
                <?php else: ?>
                <div class="bg-gray-100 rounded-lg p-8 text-center">
                    <i data-lucide="lock" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Módulo OTAs no disponible</h3>
                    <p class="text-gray-500 mb-4">Este módulo no está incluido en tu plan actual.</p>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Actualizar Plan
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Reseñas Section -->
            <div id="reseñas-section" class="content-section space-y-6" style="display: none;">
                <?php if (hasModule('reseñas', $modules)): ?>
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Reseñas</h3>
                        <span id="stats-reviews" class="text-2xl font-semibold text-gray-900">--</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Calificación Promedio</h3>
                        <span id="stats-avg-rating" class="text-2xl font-semibold text-gray-900">--</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Cobertura Total</h3>
                        <span id="stats-coverage" class="text-2xl font-semibold text-gray-900">--%</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Cobertura por NPS</h3>
                        <div id="stats-nps-coverage" class="space-y-1">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-xs">--%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <span class="text-xs">--%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-xs">--%</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Casos Creados</h3>
                        <span id="stats-cases" class="text-2xl font-semibold text-gray-900">--</span>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="space-y-4" id="reviews-container">
                    <div class="text-center py-8">
                        <i data-lucide="loader" class="w-8 h-8 text-blue-500 mx-auto mb-4 animate-spin"></i>
                        <p class="text-gray-600">Cargando reseñas...</p>
                    </div>
                </div>

                <!-- Load More Button -->
                <div class="text-center" id="load-more-container" style="display: none;">
                    <button id="load-more-btn" class="px-6 py-3 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cargar más reseñas
                    </button>
                </div>
                <?php else: ?>
                <div class="bg-gray-100 rounded-lg p-8 text-center">
                    <i data-lucide="lock" class="w-12 h-12 text-gray-400 mx-auto mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-600 mb-2">Módulo Reseñas no disponible</h3>
                    <p class="text-gray-500 mb-4">Este módulo no está incluido en tu plan actual.</p>
                    <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Actualizar Plan
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        class ClientDashboard {
            constructor() {
                this.selectedHotel = null;
                this.dateRange = '30';
                this.reviewsOffset = 0;
                this.reviewsLimit = 20;
                this.loadingReviews = false;
                
                this.init();
            }
            
            init() {
                // Inicializar iconos de Lucide
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                
                this.bindEvents();
                this.setInitialHotel();
                this.loadDashboardData();
            }
            
            bindEvents() {
                // Navegación del sidebar
                document.querySelectorAll('.menu-button[data-section]').forEach(button => {
                    button.addEventListener('click', (e) => {
                        const section = e.target.closest('[data-section]').getAttribute('data-section');
                        this.switchSection(section);
                    });
                });
                
                // Selector de hotel
                const hotelSelector = document.getElementById('hotelSelector');
                if (hotelSelector) {
                    hotelSelector.addEventListener('change', (e) => {
                        this.selectedHotel = e.target.value;
                        this.loadDashboardData();
                        if (document.getElementById('reseñas-section').style.display !== 'none') {
                            this.loadReviews(true);
                        }
                    });
                }
                
                // Selector de rango de fechas
                const dateRange = document.getElementById('dateRange');
                if (dateRange) {
                    dateRange.addEventListener('change', (e) => {
                        this.dateRange = e.target.value;
                        this.loadDashboardData();
                        if (document.getElementById('reseñas-section').style.display !== 'none') {
                            this.loadReviews(true);
                        }
                    });
                }
                
                // Botón cargar más
                const loadMoreBtn = document.getElementById('load-more-btn');
                if (loadMoreBtn) {
                    loadMoreBtn.addEventListener('click', () => {
                        this.loadReviews(false);
                    });
                }
            }
            
            setInitialHotel() {
                const hotelSelector = document.getElementById('hotelSelector');
                if (hotelSelector && hotelSelector.options.length > 0) {
                    this.selectedHotel = hotelSelector.value;
                }
            }
            
            switchSection(section) {
                // Ocultar todas las secciones
                document.querySelectorAll('.content-section').forEach(sec => {
                    sec.style.display = 'none';
                });
                
                // Mostrar la sección seleccionada
                document.getElementById(section + '-section').style.display = 'block';
                
                // Actualizar botones activos
                document.querySelectorAll('.menu-button').forEach(btn => {
                    btn.classList.remove('active', 'bg-cyan-500', 'text-white');
                    btn.classList.add('text-gray-300');
                });
                
                const activeBtn = document.querySelector(`[data-section="${section}"]`);
                if (activeBtn) {
                    activeBtn.classList.add('active', 'bg-cyan-500', 'text-white');
                    activeBtn.classList.remove('text-gray-300');
                }
                
                // Cargar datos específicos de la sección
                if (section === 'reseñas') {
                    this.loadReviews(true);
                    this.loadStats();
                }
            }
            
            async loadDashboardData() {
                if (!this.selectedHotel) return;
                
                try {
                    const response = await fetch(`client-api.php?action=dashboard&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.updateDashboardUI(result.data);
                    }
                } catch (error) {
                    console.error('Error loading dashboard data:', error);
                }
            }
            
            updateDashboardUI(data) {
                // Actualizar IRO
                if (data.iro) {
                    document.getElementById('iro-score').textContent = data.iro.score + '%';
                    this.updateCircularProgress('iro-progress', data.iro.score);
                    
                    // Actualizar estado
                    const iroStatus = document.getElementById('iro-status');
                    if (data.iro.score >= 80) {
                        iroStatus.textContent = 'Excelente';
                        iroStatus.className = 'text-sm text-green-600';
                    } else if (data.iro.score >= 60) {
                        iroStatus.textContent = 'Regular';
                        iroStatus.className = 'text-sm text-yellow-600';
                    } else {
                        iroStatus.textContent = 'Malo';
                        iroStatus.className = 'text-sm text-red-600';
                    }
                }
            }
            
            updateCircularProgress(elementId, percentage) {
                const circle = document.getElementById(elementId);
                if (circle) {
                    const radius = 52;
                    const circumference = 2 * Math.PI * radius;
                    const offset = circumference - (percentage / 100) * circumference;
                    circle.style.strokeDashoffset = offset;
                }
            }
            
            async loadStats() {
                if (!this.selectedHotel) return;
                
                try {
                    const response = await fetch(`client-api.php?action=stats&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.updateStatsUI(result.data);
                    }
                } catch (error) {
                    console.error('Error loading stats:', error);
                }
            }
            
            updateStatsUI(data) {
                document.getElementById('stats-reviews').textContent = data.total_reviews;
                document.getElementById('stats-avg-rating').textContent = data.avg_rating;
                document.getElementById('stats-coverage').textContent = data.coverage_total + '%';
                document.getElementById('stats-cases').textContent = data.cases_created;
                
                // Actualizar NPS coverage
                const npsContainer = document.getElementById('stats-nps-coverage');
                const spans = npsContainer.querySelectorAll('span');
                if (spans.length >= 3) {
                    spans[0].textContent = data.coverage_nps.promoters + '%';
                    spans[1].textContent = data.coverage_nps.neutrals + '%';  
                    spans[2].textContent = data.coverage_nps.detractors + '%';
                }
            }
            
            async loadReviews(reset = false) {
                if (!this.selectedHotel || this.loadingReviews) return;
                
                this.loadingReviews = true;
                
                if (reset) {
                    this.reviewsOffset = 0;
                    document.getElementById('reviews-container').innerHTML = `
                        <div class="text-center py-8">
                            <i data-lucide="loader" class="w-8 h-8 text-blue-500 mx-auto mb-4 animate-spin"></i>
                            <p class="text-gray-600">Cargando reseñas...</p>
                        </div>
                    `;
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                }
                
                try {
                    const response = await fetch(`client-api.php?action=reviews&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}&limit=${this.reviewsLimit}&offset=${this.reviewsOffset}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderReviews(result.data, reset);
                        this.reviewsOffset += this.reviewsLimit;
                        
                        // Mostrar/ocultar botón "Cargar más"
                        const loadMoreContainer = document.getElementById('load-more-container');
                        if (result.pagination.hasMore) {
                            loadMoreContainer.style.display = 'block';
                        } else {
                            loadMoreContainer.style.display = 'none';
                        }
                    } else {
                        this.showError(result.error);
                    }
                } catch (error) {
                    console.error('Error loading reviews:', error);
                    this.showError('Error cargando reseñas');
                } finally {
                    this.loadingReviews = false;
                }
            }
            
            renderReviews(reviews, reset = false) {
                const container = document.getElementById('reviews-container');
                
                if (reviews.length === 0 && reset) {
                    container.innerHTML = `
                        <div class="text-center py-8 text-gray-500">
                            No hay reseñas disponibles para este período
                        </div>
                    `;
                    return;
                }
                
                const reviewsHTML = reviews.map(review => this.createReviewHTML(review)).join('');
                
                if (reset) {
                    container.innerHTML = reviewsHTML;
                } else {
                    container.insertAdjacentHTML('beforeend', reviewsHTML);
                }
                
                // Reinicializar iconos
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            }
            
            createReviewHTML(review) {
                const platformColors = {
                    booking: 'bg-blue-700',
                    google: 'bg-red-500', 
                    tripadvisor: 'bg-green-600',
                    expedia: 'bg-blue-600',
                    despegar: 'bg-purple-600'
                };
                
                const platformNames = {
                    booking: 'Booking.com',
                    google: 'Google',
                    tripadvisor: 'TripAdvisor', 
                    expedia: 'Expedia',
                    despegar: 'Despegar'
                };
                
                const starsHTML = Array.from({length: 5}, (_, i) => 
                    `<i data-lucide="star" class="w-4 h-4 ${i < review.rating ? 'text-yellow-400 fill-current' : 'text-gray-300'}"></i>`
                ).join('');
                
                return `
                    <div class="review-card bg-white rounded-lg p-6 shadow-sm border-left-4 border-transparent hover:border-cyan-500 transition-all">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-2">
                                    <h4 class="font-semibold text-gray-900">${review.guest}</h4>
                                    <span class="text-sm text-gray-600">${review.country}</span>
                                    <span class="text-sm text-gray-600">${review.date}</span>
                                    <span class="text-sm text-gray-600">${review.tripType}</span>
                                </div>
                                <div class="flex items-center gap-2 mb-2">
                                    <span class="text-xs text-gray-500">ID Reseña: ${review.reviewId}</span>
                                    <div class="platform-badge text-xs px-2 py-1 rounded text-white ${platformColors[review.platform] || 'bg-gray-500'}">
                                        ${platformNames[review.platform] || review.platform}
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mb-3">
                                    <div class="star-rating flex">
                                        ${starsHTML}
                                    </div>
                                    <span class="text-sm font-medium">${review.rating} / 5</span>
                                </div>
                                ${review.title ? `<h5 class="font-medium text-gray-900 mb-3">${review.title}</h5>` : ''}
                            </div>
                        </div>

                        ${review.positive ? `
                        <div class="mb-3">
                            <span class="text-green-600 font-medium text-sm">(+) </span>
                            <span class="text-sm text-gray-700">${review.positive}</span>
                        </div>
                        ` : ''}

                        ${review.negative ? `
                        <div class="mb-4">
                            <span class="text-red-600 font-medium text-sm">(-) </span>
                            <span class="text-sm text-gray-700">${review.negative}</span>
                        </div>
                        ` : ''}

                        ${!review.hasResponse ? `
                        <div class="flex items-center gap-2 p-3 bg-yellow-50 rounded-lg mb-4">
                            <i data-lucide="alert-circle" class="text-yellow-600 w-4 h-4"></i>
                            <span class="text-sm text-yellow-800">No respondida</span>
                            <span class="text-xs text-yellow-600">responde para mejorar tu IRO</span>
                        </div>
                        ` : ''}

                        <div class="review-actions flex gap-2 flex-wrap">
                            <button class="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50" 
                                    onclick="dashboard.integrateOTA('${review.id}')">
                                Integrar OTA
                            </button>
                            ${review.negative ? `
                            <button class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                                    onclick="dashboard.translateReview('${review.id}')">
                                Traducir
                            </button>
                            ` : ''}
                            <button class="px-4 py-2 bg-yellow-500 text-white rounded-lg text-sm font-medium hover:bg-yellow-600 flex items-center gap-1"
                                    onclick="dashboard.generateResponse('${review.id}')">
                                <i data-lucide="message-square" class="w-4 h-4"></i>
                                Generar respuesta
                            </button>
                            <button class="px-4 py-2 border border-gray-300 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50"
                                    onclick="dashboard.createCase('${review.id}')">
                                Crear Caso
                            </button>
                        </div>
                    </div>
                `;
            }
            
            // Métodos para las acciones de reseñas
            async integrateOTA(reviewId) {
                this.showNotification('Integrando con OTA...', 'info');
                console.log('Integrar OTA para reseña:', reviewId);
            }
            
            async translateReview(reviewId) {
                try {
                    const response = await fetch('client-api.php?action=translate', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `review_id=${reviewId}`
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showNotification('Reseña traducida exitosamente', 'success');
                    } else {
                        this.showNotification(result.error, 'error');
                    }
                } catch (error) {
                    this.showNotification('Error traduciendo reseña', 'error');
                }
            }
            
            async generateResponse(reviewId) {
                try {
                    this.showNotification('Generando respuesta con IA...', 'info');
                    
                    const response = await fetch('client-api.php?action=generate_response', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `review_id=${reviewId}`
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        // Mostrar modal con la respuesta generada
                        this.showResponseModal(result.data.response);
                    } else {
                        this.showNotification(result.error, 'error');
                    }
                } catch (error) {
                    this.showNotification('Error generando respuesta', 'error');
                }
            }
            
            async createCase(reviewId) {
                try {
                    const response = await fetch('client-api.php?action=create_case', {
                        method: 'POST', 
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `review_id=${reviewId}`
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        this.showNotification(`Caso creado: ${result.data.case_id}`, 'success');
                    } else {
                        this.showNotification(result.error, 'error');
                    }
                } catch (error) {
                    this.showNotification('Error creando caso', 'error');
                }
            }
            
            showResponseModal(response) {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `
                    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 class="text-lg font-semibold mb-4">Respuesta Generada</h3>
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <p class="text-sm">${response}</p>
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button onclick="this.closest('.fixed').remove()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                                Cerrar
                            </button>
                            <button onclick="navigator.clipboard.writeText('${response}'); this.textContent='Copiado!'" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                                Copiar
                            </button>
                        </div>
                    </div>
                `;
                document.body.appendChild(modal);
            }
            
            showNotification(message, type = 'info') {
                const colors = {
                    info: 'bg-blue-500',
                    success: 'bg-green-500', 
                    warning: 'bg-yellow-500',
                    error: 'bg-red-500'
                };
                
                const notification = document.createElement('div');
                notification.className = `fixed top-4 right-4 z-50 ${colors[type]} text-white p-4 rounded-lg shadow-lg max-w-sm`;
                notification.innerHTML = `
                    <div class="flex items-center gap-3">
                        <div class="flex-1">
                            <p class="text-sm font-medium">${message}</p>
                        </div>
                        <button class="text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                            <i data-lucide="x" class="w-4 h-4"></i>
                        </button>
                    </div>
                `;
                
                document.body.appendChild(notification);
                
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 5000);
            }
            
            showError(message) {
                this.showNotification(message, 'error');
            }
        }

        // Inicializar dashboard
        let dashboard;
        document.addEventListener('DOMContentLoaded', () => {
            dashboard = new ClientDashboard();
        });
        
        // Exponer globalmente para onclick handlers
        window.dashboard = dashboard;
    </script>
</body>
</html>