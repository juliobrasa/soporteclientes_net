<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'FidelitySuite - Dashboard de Reputación Hotelera')</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>
    
    <!-- Custom CSS -->
    @vite(['resources/css/client.css'])
    
    @stack('styles')
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
                            @yield('hotel-options')
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
                    
                    <button id="reportButton" class="px-4 py-2 border border-blue-300 text-blue-600 rounded-lg text-sm font-medium hover:bg-blue-50">
                        Reporte
                    </button>

                    @auth
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-600">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-700">
                                    Cerrar Sesión
                                </button>
                            </form>
                        </div>
                    @endauth
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
            @yield('content')
        </div>
    </div>

    <!-- Notification Container -->
    <div id="notification-container" class="fixed top-4 right-4 z-50"></div>

    <!-- Scripts -->
    @vite(['resources/js/client.js'])
    
    @stack('scripts')
    
    <script>
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // CSRF token for AJAX requests
        window.Laravel = {
            csrfToken: '{{ csrf_token() }}',
            baseUrl: '{{ url('/') }}'
        };
    </script>
</body>
</html>