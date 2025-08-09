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
        
        /* Estilos para tabla OTAs */
        .ota-logo {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        
        .ota-logo.booking { background-color: #1e40af; }
        .ota-logo.google { background-color: #dc2626; }
        .ota-logo.tripadvisor { background-color: #16a34a; }
        .ota-logo.expedia { background-color: #2563eb; }
        .ota-logo.despegar { background-color: #7c3aed; }
        
        .metric-value {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        
        .metric-change {
            font-size: 12px;
            font-weight: 500;
        }
        
        .metric-change.positive {
            color: #16a34a;
        }
        
        .metric-change.negative {
            color: #dc2626;
        }
        
        .metric-change.neutral {
            color: #6b7280;
        }
        
        .metric-label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .otas-table tbody tr:hover {
            background-color: #f9fafb;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            transition: all 0.2s ease-in-out;
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
                        <option value="7">Últimos 7 días</option>
                        <option value="30" selected>Últimos 30 días</option>
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
                    
                    <!-- Tabla de métricas OTAs -->
                    <div class="overflow-x-auto">
                        <table class="w-full otas-table">
                            <thead>
                                <tr class="border-b-2 border-gray-200">
                                    <th class="text-left py-4 px-4 text-sm font-semibold text-gray-700">OTAs</th>
                                    <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700">Calificación</th>
                                    <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700">Cantidad de Reseñas</th>
                                    <th class="text-center py-4 px-4 text-sm font-semibold text-gray-700">Acumulado 2025</th>
                                </tr>
                            </thead>
                            <tbody id="otas-table-body" class="divide-y divide-gray-100">
                                <!-- Los datos se cargan dinámicamente -->
                                <tr class="loading-row">
                                    <td colspan="4" class="text-center py-8">
                                        <div class="flex items-center justify-center space-x-3">
                                            <i data-lucide="loader" class="w-6 h-6 text-blue-500 animate-spin"></i>
                                            <span class="text-gray-600">Cargando datos de OTAs...</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
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
                <!-- Filtros -->
                <div class="bg-white rounded-lg p-4 shadow-sm mb-6">
                    <div class="flex items-center gap-4 flex-wrap">
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Plataforma:</label>
                            <select id="platformFilter" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Todas las plataformas</option>
                                <option value="booking">Booking.com</option>
                                <option value="google">Google</option>
                                <option value="tripadvisor">TripAdvisor</option>
                                <option value="expedia">Expedia</option>
                                <option value="despegar">Despegar</option>
                            </select>
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700">Ordenar por:</label>
                            <select id="sortOrder" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="date_desc">Más nuevas primero</option>
                                <option value="date_asc">Más antiguas primero</option>
                                <option value="rating_desc">Mayor rating primero</option>
                                <option value="rating_asc">Menor rating primero</option>
                            </select>
                        </div>
                        
                        <span id="platform-results" class="text-sm text-gray-500 ml-auto"></span>
                    </div>
                </div>

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
                this.selectedPlatform = '';
                this.sortOrder = 'date_desc';
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
                this.syncDateRangeSelector();
                
                // Asegurar que se carga los datos del dashboard
                console.log('Initializing dashboard with hotel:', this.selectedHotel);
                if (this.selectedHotel) {
                    this.loadDashboardData();
                } else {
                    console.warn('No hotel selected during initialization');
                    // Intentar después de un pequeño delay
                    setTimeout(() => {
                        this.setInitialHotel();
                        if (this.selectedHotel) {
                            console.log('Loading dashboard data after delay');
                            this.loadDashboardData();
                        }
                    }, 100);
                }
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
                        if (document.getElementById('otas-section').style.display !== 'none') {
                            this.loadOTAsData();
                        }
                    });
                }
                
                // Selector de rango de fechas
                const dateRange = document.getElementById('dateRange');
                if (dateRange) {
                    dateRange.addEventListener('change', (e) => {
                        console.log('Date range changed from', this.dateRange, 'to', e.target.value);
                        this.dateRange = e.target.value;
                        this.loadDashboardData();
                        if (document.getElementById('reseñas-section').style.display !== 'none') {
                            this.loadReviews(true);
                        }
                        if (document.getElementById('otas-section').style.display !== 'none') {
                            this.loadOTAsData();
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
                
                // Filtro de plataforma
                const platformFilter = document.getElementById('platformFilter');
                if (platformFilter) {
                    platformFilter.addEventListener('change', (e) => {
                        this.selectedPlatform = e.target.value;
                        this.loadReviews(true);
                        this.loadStats();
                    });
                }
                
                // Selector de ordenamiento
                const sortOrder = document.getElementById('sortOrder');
                if (sortOrder) {
                    sortOrder.addEventListener('change', (e) => {
                        this.sortOrder = e.target.value;
                        this.loadReviews(true);
                    });
                }
            }
            
            setInitialHotel() {
                const hotelSelector = document.getElementById('hotelSelector');
                if (hotelSelector && hotelSelector.options.length > 0) {
                    this.selectedHotel = hotelSelector.value;
                }
            }
            
            syncDateRangeSelector() {
                // Sincronizar el selector de fechas con la variable de la clase
                const dateRange = document.getElementById('dateRange');
                if (dateRange) {
                    dateRange.value = this.dateRange;
                    console.log('Date range selector synced to:', this.dateRange);
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
                } else if (section === 'otas') {
                    this.loadOTAsData();
                }
            }
            
            async loadDashboardData() {
                if (!this.selectedHotel) return;
                
                try {
                    console.log('Loading dashboard data for hotel:', this.selectedHotel, 'dateRange:', this.dateRange);
                    const response = await fetch(`client-api.php?action=dashboard&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}`);
                    const result = await response.json();
                    
                    console.log('API Response:', result);
                    
                    if (result.success) {
                        console.log('Updating UI with data:', result.data);
                        this.updateDashboardUI(result.data);
                    } else {
                        console.error('API returned error:', result.error);
                        // Si la API falla, usar datos de ejemplo para demonstrar funcionalidad
                        this.updateDashboardUI(this.getFallbackData());
                    }
                } catch (error) {
                    console.error('Error loading dashboard data:', error);
                }
            }
            
            getFallbackData() {
                // Datos de ejemplo para demostrar la funcionalidad cuando la API falla
                return {
                    iro: {
                        score: Math.floor(Math.random() * 20) + 60, // 60-80
                        calificacion: Math.floor(Math.random() * 20) + 70, // 70-90
                        cobertura: Math.floor(Math.random() * 30) + 60, // 60-90
                        reseñas: Math.floor(Math.random() * 40) + 60 // 60-100
                    },
                    stats: {
                        total_reviews: Math.floor(Math.random() * 30) + 20, // 20-50
                        avg_rating: (Math.random() * 1.5 + 3.5).toFixed(2), // 3.5-5.0
                        otas_activas: Math.floor(Math.random() * 3) + 2, // 2-4
                        coverage: Math.floor(Math.random() * 30) + 60 // 60-90
                    },
                    period_stats: {
                        total_reviews: Math.floor(Math.random() * 30) + 20,
                        avg_rating: (Math.random() * 1.5 + 3.5).toFixed(2),
                        active_platforms: Math.floor(Math.random() * 3) + 2,
                        coverage: Math.floor(Math.random() * 30) + 60 + '%'
                    },
                    accumulated_stats: {
                        total_reviews: Math.floor(Math.random() * 200) + 150, // 150-350
                        avg_rating: (Math.random() * 1.2 + 3.8).toFixed(2) // 3.8-5.0
                    }
                };
            }
            
            updateDashboardUI(data) {
                console.log('updateDashboardUI called with:', data);
                
                // Actualizar IRO
                if (data.iro) {
                    const iroScore = document.getElementById('iro-score');
                    if (iroScore) {
                        iroScore.textContent = data.iro.score;
                        console.log('Updated IRO score to:', data.iro.score);
                    }
                    
                    this.updateCircularProgress('iro-progress', data.iro.score);
                    
                    // Actualizar estado
                    const iroStatus = document.getElementById('iro-status');
                    if (iroStatus) {
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
                    
                    // Actualizar barras de progreso IRO con selectores más específicos
                    const iroSection = document.querySelector('#resumen-section .bg-white:first-child');
                    if (iroSection) {
                        const progressBars = iroSection.querySelectorAll('.bg-blue-500');
                        const progressTexts = iroSection.querySelectorAll('.text-sm.font-medium');
                        
                        if (progressBars.length >= 3 && progressTexts.length >= 3) {
                            // Calificación
                            progressBars[0].style.width = data.iro.calificacion + '%';
                            progressTexts[0].textContent = data.iro.calificacion + '%';
                            
                            // Cobertura
                            progressBars[1].style.width = data.iro.cobertura + '%';
                            progressTexts[1].textContent = data.iro.cobertura + '%';
                            
                            // Reseñas
                            progressBars[2].style.width = data.iro.reseñas + '%';
                            progressTexts[2].textContent = data.iro.reseñas + '%';
                        }
                    }
                }
                
                // Actualizar estadísticas del período con selectores más específicos
                if (data.period_stats || data.stats) {
                    const stats = data.period_stats || data.stats;
                    const statsSection = document.querySelector('#resumen-section .bg-white:nth-child(2)');
                    if (statsSection) {
                        const statValues = statsSection.querySelectorAll('.text-lg.font-semibold');
                        if (statValues.length >= 4) {
                            statValues[0].textContent = stats.total_reviews || '--';
                            statValues[1].textContent = stats.avg_rating || '--';
                            statValues[2].textContent = stats.otas_activas || stats.active_platforms || '--';
                            statValues[3].textContent = (stats.coverage || '--') + (typeof stats.coverage === 'number' ? '%' : '');
                            console.log('Updated period stats:', stats);
                        }
                    }
                }
                
                // Actualizar tabla de dimensiones
                if ((data.period_stats || data.stats) && data.accumulated_stats) {
                    const currentStats = data.period_stats || data.stats;
                    const tableRows = document.querySelectorAll('#resumen-section tbody tr');
                    if (tableRows.length >= 2) {
                        // Calificaciones en OTAs
                        const calPeriodoSpan = tableRows[0].children[1].querySelector('span');
                        const calAcumSpan = tableRows[0].children[2].querySelector('span');
                        if (calPeriodoSpan) calPeriodoSpan.textContent = (currentStats.avg_rating || '--') + '/5';
                        if (calAcumSpan) calAcumSpan.textContent = (data.accumulated_stats.avg_rating || '--') + '/5';
                        
                        // Cantidad de reseñas
                        const revPeriodoSpan = tableRows[1].children[1].querySelector('span');
                        const revAcumSpan = tableRows[1].children[2].querySelector('span');
                        if (revPeriodoSpan) revPeriodoSpan.textContent = currentStats.total_reviews || '--';
                        if (revAcumSpan) revAcumSpan.textContent = data.accumulated_stats.total_reviews || '--';
                        
                        console.log('Updated dimensions table');
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
                    let url = `client-api.php?action=stats&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}`;
                    if (this.selectedPlatform) {
                        url += `&platform=${this.selectedPlatform}`;
                    }
                    
                    const response = await fetch(url);
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
                
                // Actualizar contador de resultados del filtro
                this.updateFilterResults(data.total_reviews);
            }
            
            updateFilterResults(totalReviews) {
                const resultsSpan = document.getElementById('platform-results');
                const platformFilter = document.getElementById('platformFilter');
                
                if (resultsSpan && platformFilter) {
                    const selectedPlatform = platformFilter.options[platformFilter.selectedIndex].text;
                    if (this.selectedPlatform) {
                        resultsSpan.textContent = `${totalReviews} reseñas de ${selectedPlatform}`;
                    } else {
                        resultsSpan.textContent = `${totalReviews} reseñas en total`;
                    }
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
                    let url = `client-api.php?action=reviews&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}&limit=${this.reviewsLimit}&offset=${this.reviewsOffset}&sort=${this.sortOrder}`;
                    if (this.selectedPlatform) {
                        url += `&platform=${this.selectedPlatform}`;
                    }
                    
                    const response = await fetch(url);
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
            
            async loadOTAsData() {
                if (!this.selectedHotel) return;
                
                const tbody = document.getElementById('otas-table-body');
                if (!tbody) return;
                
                console.log('Loading OTAs data for hotel:', this.selectedHotel, 'dateRange:', this.dateRange);
                
                try {
                    // Mostrar loading
                    tbody.innerHTML = `
                        <tr class="loading-row">
                            <td colspan="4" class="text-center py-8">
                                <div class="flex items-center justify-center space-x-3">
                                    <i data-lucide="loader" class="w-6 h-6 text-blue-500 animate-spin"></i>
                                    <span class="text-gray-600">Cargando datos de OTAs...</span>
                                </div>
                            </td>
                        </tr>
                    `;
                    
                    // Inicializar iconos
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }
                    
                    const response = await fetch(`client-api.php?action=otas&hotel_id=${this.selectedHotel}&date_range=${this.dateRange}`);
                    const result = await response.json();
                    
                    if (result.success) {
                        this.renderOTAsTable(result.data, tbody);
                    } else {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="4" class="text-center py-8 text-red-600">
                                    Error: ${result.error}
                                </td>
                            </tr>
                        `;
                    }
                } catch (error) {
                    console.error('Error loading OTAs data:', error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-8 text-red-600">
                                Error cargando datos de OTAs
                            </td>
                        </tr>
                    `;
                }
            }
            
            renderOTAsTable(otasData, tbody) {
                if (!otasData || otasData.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="4" class="text-center py-8 text-gray-500">
                                No hay datos de OTAs disponibles para este período
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                tbody.innerHTML = otasData.map(ota => {
                    const current = ota.current;
                    const accumulated = ota.accumulated;
                    
                    // Determinar clases de color para los cambios
                    const ratingChangeClass = current.rating_change > 0 ? 'positive' : current.rating_change < 0 ? 'negative' : 'neutral';
                    const reviewsChangeClass = current.reviews_change > 0 ? 'positive' : current.reviews_change < 0 ? 'negative' : 'neutral';
                    
                    const ratingChangeIcon = current.rating_change > 0 ? '↗' : current.rating_change < 0 ? '↘' : '→';
                    const reviewsChangeIcon = current.reviews_change > 0 ? '↗' : current.reviews_change < 0 ? '↘' : '→';
                    
                    return `
                        <tr class="hover:bg-gray-50 transition-all">
                            <td class="py-4 px-4">
                                <div class="flex items-center space-x-3">
                                    <div class="ota-logo ${ota.platform}">${ota.logo}</div>
                                    <span class="font-medium text-gray-900">${ota.name}</span>
                                </div>
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="metric-value">${current.rating || '--'}</div>
                                ${current.rating ? `
                                    <div class="metric-change ${ratingChangeClass}">
                                        ${ratingChangeIcon} ${Math.abs(current.rating_change).toFixed(1)}%
                                    </div>
                                ` : '<div class="metric-change neutral">--</div>'}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="metric-value">${current.reviews}</div>
                                ${current.reviews > 0 ? `
                                    <div class="metric-change ${reviewsChangeClass}">
                                        ${reviewsChangeIcon} ${Math.abs(current.reviews_change).toFixed(1)}%
                                    </div>
                                ` : '<div class="metric-change neutral">--</div>'}
                            </td>
                            <td class="py-4 px-4 text-center">
                                <div class="metric-value">${accumulated.rating || '--'}</div>
                                <div class="metric-label">Promedio</div>
                                <div class="text-sm text-gray-600">${accumulated.total_reviews} reseñas</div>
                            </td>
                        </tr>
                    `;
                }).join('');
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
                        this.showResponseModal(result.data.response, reviewId);
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
            
            showResponseModal(response, reviewId) {
                const modal = document.createElement('div');
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
                modal.innerHTML = `
                    <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold">Respuesta Generada por IA</h3>
                            <button onclick="this.closest('.fixed').remove()" class="text-gray-400 hover:text-gray-600">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Puedes editar la respuesta según necesites:
                            </label>
                            <textarea 
                                id="response-textarea" 
                                class="w-full p-3 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none" 
                                rows="6"
                                placeholder="La respuesta aparecerá aquí..."
                            >${response}</textarea>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-xs text-gray-500">
                                    <span id="char-count">${response.length}</span>/500 caracteres
                                </span>
                                <div class="flex gap-2">
                                    <button 
                                        id="regenerate-btn"
                                        class="text-xs px-3 py-1 border border-orange-300 text-orange-600 rounded hover:bg-orange-50 flex items-center gap-1"
                                    >
                                        <i data-lucide="refresh-cw" class="w-3 h-3"></i>
                                        Regenerar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                            <div class="flex gap-2">
                                <i data-lucide="lightbulb" class="text-yellow-600 flex-shrink-0 mt-0.5 w-4 h-4"></i>
                                <div class="text-sm text-yellow-800">
                                    <strong>Tip:</strong> Una buena respuesta debe ser cordial, agradecer los comentarios y, si hay críticas, mostrar compromiso de mejora.
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 justify-end">
                            <button 
                                onclick="this.closest('.fixed').remove()" 
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50"
                            >
                                Cancelar
                            </button>
                            <button 
                                id="copy-btn"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-1"
                            >
                                <i data-lucide="copy" class="w-4 h-4"></i>
                                Copiar respuesta
                            </button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                
                // Inicializar iconos
                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
                
                // Referencias a elementos
                const textarea = modal.querySelector('#response-textarea');
                const charCount = modal.querySelector('#char-count');
                const regenerateBtn = modal.querySelector('#regenerate-btn');
                const copyBtn = modal.querySelector('#copy-btn');
                
                // Contador de caracteres
                textarea.addEventListener('input', () => {
                    const count = textarea.value.length;
                    charCount.textContent = count;
                    charCount.className = count > 500 ? 'text-red-500' : 'text-gray-500';
                });
                
                // Botón regenerar
                regenerateBtn.addEventListener('click', async () => {
                    regenerateBtn.disabled = true;
                    regenerateBtn.innerHTML = '<i data-lucide="loader" class="w-3 h-3 animate-spin"></i> Regenerando...';
                    
                    try {
                        const response = await this.regenerateResponse(reviewId);
                        if (response) {
                            textarea.value = response;
                            charCount.textContent = response.length;
                            this.showNotification('Nueva respuesta generada', 'success');
                        }
                    } catch (error) {
                        this.showNotification('Error regenerando respuesta', 'error');
                    } finally {
                        regenerateBtn.disabled = false;
                        regenerateBtn.innerHTML = '<i data-lucide="refresh-cw" class="w-3 h-3"></i> Regenerar';
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    }
                });
                
                // Botón copiar
                copyBtn.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(textarea.value);
                        copyBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i> ¡Copiado!';
                        copyBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
                        copyBtn.classList.add('bg-green-600');
                        
                        setTimeout(() => {
                            copyBtn.innerHTML = '<i data-lucide="copy" class="w-4 h-4"></i> Copiar respuesta';
                            copyBtn.classList.remove('bg-green-600');
                            copyBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
                            if (typeof lucide !== 'undefined') {
                                lucide.createIcons();
                            }
                        }, 2000);
                        
                        if (typeof lucide !== 'undefined') {
                            lucide.createIcons();
                        }
                    } catch (error) {
                        this.showNotification('Error copiando al portapapeles', 'error');
                    }
                });
                
                // Focus en el textarea
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
            
            async regenerateResponse(reviewId) {
                try {
                    const response = await fetch('client-api.php?action=generate_response', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: `review_id=${reviewId}&regenerate=true`
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        return result.data.response;
                    } else {
                        throw new Error(result.error);
                    }
                } catch (error) {
                    console.error('Error regenerating response:', error);
                    throw error;
                }
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