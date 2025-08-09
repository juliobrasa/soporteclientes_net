<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suscripción Expirada - FidelitySuite</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-red-50 via-white to-orange-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-lg w-full space-y-8">
            <!-- Logo y Header -->
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-4">
                    <span class="text-blue-600">Fidelity</span>
                    <span class="text-cyan-500">Suite</span>
                </div>
                
                <!-- Ícono de suscripción expirada -->
                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
                    <i data-lucide="credit-card" class="h-8 w-8 text-red-600"></i>
                </div>
                
                <h1 class="text-2xl font-bold text-gray-900 mb-2">
                    Suscripción Expirada
                </h1>
                <p class="text-gray-600">
                    Tu suscripción ha expirado o ha sido cancelada. Renueva tu plan para continuar accediendo a FidelitySuite.
                </p>
            </div>

            <!-- Información de la cuenta -->
            @if(session('user_email'))
                <div class="bg-gray-50 rounded-lg p-4 text-center">
                    <p class="text-sm text-gray-600 mb-1">Cuenta:</p>
                    <p class="font-medium text-gray-900">{{ session('user_email') }}</p>
                </div>
            @endif

            <!-- Planes disponibles -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Renueva tu suscripción</h3>
                
                <div class="space-y-4">
                    <!-- Plan Básico -->
                    <div class="border rounded-lg p-4 hover:border-blue-300 cursor-pointer">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium text-gray-900">Plan Básico</h4>
                                <p class="text-sm text-gray-600">Dashboard + reseñas básicas</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">$29.99</p>
                                <p class="text-xs text-gray-500">/mes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Profesional -->
                    <div class="border-2 border-blue-500 rounded-lg p-4 bg-blue-50">
                        <div class="flex justify-between items-center">
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="font-medium text-gray-900">Plan Profesional</h4>
                                    <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded">Recomendado</span>
                                </div>
                                <p class="text-sm text-gray-600">Funcionalidad completa + IA + reportes</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">$79.99</p>
                                <p class="text-xs text-gray-500">/mes</p>
                            </div>
                        </div>
                    </div>

                    <!-- Plan Empresarial -->
                    <div class="border rounded-lg p-4 hover:border-purple-300 cursor-pointer">
                        <div class="flex justify-between items-center">
                            <div>
                                <h4 class="font-medium text-gray-900">Plan Empresarial</h4>
                                <p class="text-sm text-gray-600">Todo incluido + análisis competencia</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-gray-900">$199.99</p>
                                <p class="text-xs text-gray-500">/mes</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 text-center">
                    <button class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:bg-blue-700 transition duration-200">
                        Contactar Ventas
                    </button>
                </div>
            </div>

            <!-- Beneficios incluidos -->
            <div class="bg-white rounded-lg shadow-sm border p-6">
                <h3 class="font-medium text-gray-900 mb-4">¿Por qué renovar?</h3>
                <ul class="space-y-3">
                    <li class="flex items-start">
                        <i data-lucide="check" class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-700">Acceso completo a tu dashboard de reputación</span>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="check" class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-700">Monitoreo en tiempo real de reseñas</span>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="check" class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-700">Reportes detallados y exportables</span>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="check" class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-700">Respuestas automáticas con IA</span>
                    </li>
                    <li class="flex items-start">
                        <i data-lucide="check" class="h-5 w-5 text-green-500 mr-3 mt-0.5 flex-shrink-0"></i>
                        <span class="text-sm text-gray-700">Soporte técnico prioritario</span>
                    </li>
                </ul>
            </div>

            <!-- Acciones -->
            <div class="flex flex-col sm:flex-row gap-3">
                <a 
                    href="{{ route('client.login') }}" 
                    class="flex-1 text-center py-3 px-4 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition duration-200"
                >
                    Volver al Login
                </a>
                <a 
                    href="mailto:soporte@soporteclientes.net?subject=Renovación de Suscripción" 
                    class="flex-1 text-center py-3 px-4 bg-blue-600 text-white rounded-lg font-medium hover:bg-blue-700 transition duration-200"
                >
                    Contactar Soporte
                </a>
            </div>

            <!-- Información de contacto -->
            <div class="text-center text-sm text-gray-500">
                <p>¿Necesitas ayuda?</p>
                <p>
                    Escríbenos a 
                    <a href="mailto:soporte@soporteclientes.net" class="text-blue-600 hover:text-blue-500">soporte@soporteclientes.net</a>
                    o llama al 
                    <a href="tel:+5299812345678" class="text-blue-600 hover:text-blue-500">+52 998 123 4567</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>