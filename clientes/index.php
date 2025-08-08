<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FidelitySuite - Dashboard de Reputación Hotelera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>
    <link rel="stylesheet" href="css/dashboard.css">
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
                            <option value="Hotel Terracaribe Cancun">Hotel Terracaribe Cancun</option>
                            <option value="Hotel Plaza Kokai Cancún">Hotel Plaza Kokai Cancún</option>
                            <option value="Top 10% myHotel">Top 10% myHotel</option>
                            <option value="Suites Cancun Center">Suites Cancun Center</option>
                            <option value="Promedio myHotel">Promedio myHotel</option>
                        </select>
                        <i data-lucide="chevron-down" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4"></i>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <select id="dateRange" class="border border-gray-300 rounded-lg px-3 py-1 text-sm">
                        <option value="30">Últimos 30 días</option>
                        <option value="60">Últimos 60 días</option>
                        <option value="90">Últimos 90 días</option>
                    </select>
                    
                    <button class="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50">
                        Reporte
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-800 min-h-screen">
            <nav class="mt-8">
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors bg-cyan-500 text-white" data-section="resumen">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span>Resumen</span>
                </button>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors text-gray-300" data-section="otas">
                    <i data-lucide="external-link" class="w-5 h-5"></i>
                    <span>OTAs</span>
                </button>
                <button class="menu-button w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-700 transition-colors text-gray-300" data-section="reseñas">
                    <i data-lucide="message-square" class="w-5 h-5"></i>
                    <span>Reseñas</span>
                </button>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <!-- Resumen Section -->
            <div id="resumen-section" class="content-section space-y-6">
                <!-- Header con selector de período -->
                <div class="bg-yellow-100 border border-yellow-300 rounded-lg p-4">
                    <p class="text-yellow-800">
                        Conoce el IRO con <a href="#" class="text-blue-600 underline">este video</a> y cuéntanos qué te parece esta sección <a href="#" class="text-blue-600 underline">aquí</a>
                    </p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- IRO Score -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Índice de Reputación Online (IRO)</h3>
                                <p class="text-sm text-gray-600" id="iro-status">Regular</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-center mb-6">
                            <div class="relative">
                                <svg width="120" height="120" class="transform -rotate-90">
                                    <circle cx="60" cy="60" r="52" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                                    <circle 
                                        id="iro-progress"
                                        cx="60" cy="60" r="52" 
                                        stroke="#fbbf24" 
                                        stroke-width="8" 
                                        fill="none"
                                        stroke-dasharray="326.73"
                                        stroke-dashoffset="84.15"
                                        stroke-linecap="round"
                                        class="transition-all duration-500"
                                    ></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span id="iro-score" class="text-3xl font-bold text-gray-900">74%</span>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Calificación</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 77%"></div>
                                    </div>
                                    <span class="text-sm font-medium">77%</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Cobertura</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 82%"></div>
                                    </div>
                                    <span class="text-sm font-medium">82%</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">Reseñas</span>
                                <div class="flex items-center gap-2">
                                    <div class="w-24 bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-500 h-2 rounded-full" style="width: 56%"></div>
                                    </div>
                                    <span class="text-sm font-medium">56%</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 text-center">
                            <span class="text-green-600 text-sm font-medium">+9% respecto al período anterior</span>
                        </div>
                    </div>

                    <!-- Índice Semántico -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">Índice Semántico</h3>
                                <p class="text-sm text-red-600">Muy Malo</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-center mb-6">
                            <div class="relative">
                                <svg width="120" height="120" class="transform -rotate-90">
                                    <circle cx="60" cy="60" r="52" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                                    <circle 
                                        cx="60" cy="60" r="52" 
                                        stroke="#ef4444" 
                                        stroke-width="8" 
                                        fill="none"
                                        stroke-dasharray="326.73"
                                        stroke-dashoffset="232.06"
                                        stroke-linecap="round"
                                        class="transition-all duration-500"
                                    ></circle>
                                </svg>
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-3xl font-bold text-gray-900">29%</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                            <div class="flex gap-2">
                                <i data-lucide="alert-circle" class="text-yellow-600 flex-shrink-0 mt-0.5 w-4 h-4"></i>
                                <p class="text-sm text-yellow-800">Cuidado, tu propiedad tiene bastantes menciones negativas en los comentarios.</p>
                            </div>
                        </div>

                        <div class="text-center">
                            <span class="text-red-600 text-sm font-medium">-50% respecto al período anterior</span>
                        </div>
                    </div>

                    <!-- Recomendaciones -->
                    <div class="bg-white rounded-lg p-6 shadow-sm">
                        <div class="flex items-center gap-2 mb-4">
                            <i data-lucide="award" class="text-yellow-500 w-5 h-5"></i>
                            <h3 class="text-lg font-semibold text-gray-900">Recomendaciones</h3>
                            <span class="text-sm text-gray-500">1 / 3</span>
                        </div>
                        
                        <p class="text-sm text-gray-700 mb-4">
                            Revisa tus calificaciones por OTA para ver cuáles están afectando negativamente tu reputación y así para tomar acciones de mejora.
                        </p>
                        
                        <button class="text-blue-600 text-sm font-medium hover:underline">
                            SIGUIENTE
                        </button>
                    </div>
                </div>

                <!-- Dimensiones de la reputación online -->
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
                                            <span class="text-sm font-medium">3.85</span>
                                            <span class="text-red-500 text-xs">-4%</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">4</span>
                                            <span class="text-green-500 text-xs">+10%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 text-sm text-gray-900">Cantidad de reseñas</td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">30</span>
                                            <span class="text-green-500 text-xs">+11%</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">341</span>
                                            <span class="text-green-500 text-xs">+231%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b">
                                    <td class="py-3 text-sm text-gray-900">Cobertura de reseñas</td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">67%</span>
                                            <span class="text-red-500 text-xs">-5%</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">62%</span>
                                            <span class="text-green-500 text-xs">+99%</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-3 text-sm text-gray-900">NPS</td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">+17</span>
                                            <span class="text-red-500 text-xs">-9</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-3">
                                        <div class="flex items-center justify-center gap-1">
                                            <span class="text-sm font-medium">+27</span>
                                            <span class="text-green-500 text-xs">+16</span>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- OTAs Section -->
            <div id="otas-section" class="content-section space-y-6" style="display: none;">
                <div class="bg-white rounded-lg p-6 shadow-sm">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Ranking por OTA's <span id="current-hotel">Hotel Terracaribe Cancun</span></h3>
                        <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                            Seleccionar: Competidor
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b">
                                    <th class="text-left py-3 text-sm font-medium text-gray-600">OTAs</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-600">Calificación</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-600">Cantidad De Reseñas</th>
                                    <th class="text-center py-3 text-sm font-medium text-gray-600">Acumulado 2025</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center text-white font-bold">E</div>
                                            <span class="text-sm font-medium text-gray-900">Expedia Group</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">5</span>
                                            <div class="flex items-center gap-1 text-xs text-green-600">
                                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                                66.67%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">1</span>
                                            <div class="flex items-center gap-1 text-xs text-red-600">
                                                <i data-lucide="trending-down" class="w-3 h-3"></i>
                                                66.67%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">3.85</span>
                                            <span class="text-xs text-gray-500">Promedio</span>
                                            <div class="text-xs text-gray-500 mt-1">20 Cant. Reseñas</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-blue-700 flex items-center justify-center text-white font-bold">B</div>
                                            <span class="text-sm font-medium text-gray-900">Booking.com</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">3.98</span>
                                            <div class="flex items-center gap-1 text-xs text-green-600">
                                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                                0.25%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">23</span>
                                            <div class="flex items-center gap-1 text-xs text-green-600">
                                                <i data-lucide="trending-up" class="w-3 h-3"></i>
                                                35.29%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">3.97</span>
                                            <span class="text-xs text-gray-500">Promedio</span>
                                            <div class="text-xs text-gray-500 mt-1">262 Cant. Reseñas</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-red-500 flex items-center justify-center text-white font-bold">G</div>
                                            <span class="text-sm font-medium text-gray-900">Google</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">3.17</span>
                                            <div class="flex items-center gap-1 text-xs text-red-600">
                                                <i data-lucide="trending-down" class="w-3 h-3"></i>
                                                30.63%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">6</span>
                                            <div class="flex items-center gap-1 text-xs text-red-600">
                                                <i data-lucide="trending-down" class="w-3 h-3"></i>
                                                14.29%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">4.19</span>
                                            <span class="text-xs text-gray-500">Promedio</span>
                                            <div class="text-xs text-gray-500 mt-1">54 Cant. Reseñas</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-green-600 flex items-center justify-center text-white font-bold">T</div>
                                            <span class="text-sm font-medium text-gray-900">TripAdvisor</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <span class="text-gray-400">-</span>
                                    </td>
                                    <td class="text-center py-4">
                                        <span class="text-gray-400">-</span>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-gray-400">-</span>
                                            <div class="text-xs text-gray-500 mt-1">0 Cant. Reseñas</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full bg-purple-600 flex items-center justify-center text-white font-bold">D</div>
                                            <span class="text-sm font-medium text-gray-900">Despegar Group</span>
                                        </div>
                                    </td>
                                    <td class="text-center py-4">
                                        <span class="text-gray-400">-</span>
                                    </td>
                                    <td class="text-center py-4">
                                        <span class="text-gray-400">-</span>
                                    </td>
                                    <td class="text-center py-4">
                                        <div class="flex flex-col items-center">
                                            <span class="text-lg font-semibold">4.05</span>
                                            <span class="text-xs text-gray-500">Promedio</span>
                                            <div class="text-xs text-gray-500 mt-1">5 Cant. Reseñas</div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-6 text-center">
                        <button class="text-blue-600 hover:underline text-sm font-medium">
                            Contáctanos para activar más OTA's
                        </button>
                    </div>
                </div>
            </div>

            <!-- Reseñas Section -->
            <div id="reseñas-section" class="content-section space-y-6" style="display: none;">
                <!-- Stats Cards -->
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Reseñas</h3>
                        <span class="text-2xl font-semibold text-gray-900">30</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Calificación Promedio</h3>
                        <span class="text-2xl font-semibold text-gray-900">3.85</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Cobertura Total</h3>
                        <span class="text-2xl font-semibold text-gray-900">67%</span>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Cobertura por NPS</h3>
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                                <span class="text-xs">50%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                                <span class="text-xs">57%</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                                <span class="text-xs">100%</span>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg p-4 shadow-sm">
                        <h3 class="text-sm text-gray-600 mb-2">Casos Creados</h3>
                        <span class="text-2xl font-semibold text-gray-900">0</span>
                    </div>
                </div>

                <!-- Reviews List -->
                <div class="space-y-4" id="reviews-container">
                    <!-- Las reseñas se cargarán aquí dinámicamente -->
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js"></script>
</body>
</html>