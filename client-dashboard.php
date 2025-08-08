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
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Análisis de Reseñas</h3>
                    
                    <div class="text-center py-8">
                        <i data-lucide="loader" class="w-8 h-8 text-blue-500 mx-auto mb-4 animate-spin"></i>
                        <p class="text-gray-600">Cargando reseñas...</p>
                    </div>
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
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Navegación del sidebar
        document.querySelectorAll('.menu-button[data-section]').forEach(button => {
            button.addEventListener('click', function() {
                const section = this.getAttribute('data-section');
                
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
                
                this.classList.add('active', 'bg-cyan-500', 'text-white');
                this.classList.remove('text-gray-300');
            });
        });

        // Simulación de datos (en producción vendría de API)
        setTimeout(() => {
            document.getElementById('iro-score').textContent = '74%';
            document.getElementById('iro-status').textContent = 'Regular';
            document.getElementById('iro-status').className = 'text-sm text-yellow-600';
        }, 1000);
    </script>
</body>
</html>