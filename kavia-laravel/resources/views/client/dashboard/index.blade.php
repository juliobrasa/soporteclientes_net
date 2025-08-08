@extends('layouts.client')

@section('title', 'FidelitySuite - Dashboard de Reputación')

@section('hotel-options')
    @foreach($hotels as $hotel)
        <option value="{{ $hotel->id }}" {{ $loop->first ? 'selected' : '' }}>
            {{ $hotel->nombre_hotel }}
        </option>
    @endforeach
@endsection

@section('content')
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
                        <p class="text-sm text-gray-600" id="iro-status">Cargando...</p>
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
                                stroke-dashoffset="326.73"
                                stroke-linecap="round"
                                class="transition-all duration-500"
                            ></circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span id="iro-score" class="text-3xl font-bold text-gray-900">--%</span>
                        </div>
                    </div>
                </div>

                <div class="space-y-3" id="iro-metrics">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Calificación</span>
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div id="calificacion-bar" class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                            <span id="calificacion-value" class="text-sm font-medium">--%</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Cobertura</span>
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div id="cobertura-bar" class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                            <span id="cobertura-value" class="text-sm font-medium">--%</span>
                        </div>
                    </div>
                    
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Reseñas</span>
                        <div class="flex items-center gap-2">
                            <div class="w-24 bg-gray-200 rounded-full h-2">
                                <div id="resenas-bar" class="bg-blue-500 h-2 rounded-full" style="width: 0%"></div>
                            </div>
                            <span id="resenas-value" class="text-sm font-medium">--%</span>
                        </div>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <span id="iro-change" class="text-green-600 text-sm font-medium">-- respecto al período anterior</span>
                </div>
            </div>

            <!-- Índice Semántico -->
            <div class="bg-white rounded-lg p-6 shadow-sm">
                <div class="flex justify-between items-start mb-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Índice Semántico</h3>
                        <p id="semantico-status" class="text-sm text-red-600">Cargando...</p>
                    </div>
                </div>
                
                <div class="flex items-center justify-center mb-6">
                    <div class="relative">
                        <svg width="120" height="120" class="transform -rotate-90">
                            <circle cx="60" cy="60" r="52" stroke="#e5e7eb" stroke-width="8" fill="none"></circle>
                            <circle 
                                id="semantico-progress"
                                cx="60" cy="60" r="52" 
                                stroke="#ef4444" 
                                stroke-width="8" 
                                fill="none"
                                stroke-dasharray="326.73"
                                stroke-dashoffset="326.73"
                                stroke-linecap="round"
                                class="transition-all duration-500"
                            ></circle>
                        </svg>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <span id="semantico-score" class="text-3xl font-bold text-gray-900">--%</span>
                        </div>
                    </div>
                </div>

                <div id="semantico-alert" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4" style="display: none;">
                    <div class="flex gap-2">
                        <i data-lucide="alert-circle" class="text-yellow-600 flex-shrink-0 mt-0.5 w-4 h-4"></i>
                        <p id="semantico-message" class="text-sm text-yellow-800"></p>
                    </div>
                </div>

                <div class="text-center">
                    <span id="semantico-change" class="text-red-600 text-sm font-medium">-- respecto al período anterior</span>
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
                <table class="w-full" id="dimensions-table">
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
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--</span>
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 text-sm text-gray-900">Cantidad de reseñas</td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--</span>
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--</span>
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-3 text-sm text-gray-900">Cobertura de reseñas</td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--%</span>
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--%</span>
                                    <span class="text-gray-500 text-xs">--%</span>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="py-3 text-sm text-gray-900">NPS</td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--</span>
                                    <span class="text-gray-500 text-xs">--</span>
                                </div>
                            </td>
                            <td class="text-center py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <span class="text-sm font-medium">--</span>
                                    <span class="text-gray-500 text-xs">--</span>
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
                <h3 class="text-lg font-semibold text-gray-900">Ranking por OTA's <span id="current-hotel">Cargando...</span></h3>
                <button class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">
                    Seleccionar: Competidor
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full" id="otas-table">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-3 text-sm font-medium text-gray-600">OTAs</th>
                            <th class="text-center py-3 text-sm font-medium text-gray-600">Calificación</th>
                            <th class="text-center py-3 text-sm font-medium text-gray-600">Cantidad De Reseñas</th>
                            <th class="text-center py-3 text-sm font-medium text-gray-600">Acumulado 2025</th>
                        </tr>
                    </thead>
                    <tbody id="otas-table-body">
                        <!-- Los datos se cargarán dinámicamente -->
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
        <div class="grid grid-cols-2 md:grid-cols-5 gap-4" id="reviews-stats">
            <!-- Las estadísticas se cargarán dinámicamente -->
        </div>

        <!-- Reviews List -->
        <div class="space-y-4" id="reviews-container">
            <div class="reviews-loading flex justify-center items-center min-h-48">
                <div class="spinner border-2 border-gray-200 border-t-blue-600 rounded-full w-8 h-8 animate-spin"></div>
                <span class="ml-3">Cargando reseñas...</span>
            </div>
        </div>
    </div>
@endsection

@push('styles')
<style>
    .spinner {
        border-width: 2px;
        border-style: solid;
        border-color: #e5e7eb;
        border-top-color: #2563eb;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
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

    .menu-button.active {
        background-color: #06b6d4 !important;
        color: white !important;
    }

    .menu-button:not(.active) {
        color: #d1d5db;
    }
</style>
@endpush

@push('scripts')
<script>
    // Dashboard instance will be initialized by client.js
    window.dashboardConfig = {
        apiBaseUrl: '{{ url('/api/client') }}',
        csrfToken: '{{ csrf_token() }}'
    };
</script>
@endpush