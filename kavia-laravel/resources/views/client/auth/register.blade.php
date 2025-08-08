<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - FidelitySuite</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-cyan-50">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo y Header -->
            <div class="text-center">
                <div class="text-3xl font-bold text-gray-900 mb-2">
                    <span class="text-blue-600">Fidelity</span>
                    <span class="text-cyan-500">Suite</span>
                </div>
                <p class="text-gray-600">Dashboard de Reputación Hotelera</p>
                <h2 class="mt-6 text-2xl font-semibold text-gray-900">
                    Crear nueva cuenta
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Comienza tu prueba gratuita de 30 días
                </p>
            </div>

            <!-- Formulario de Registro -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('client.register') }}">
                @csrf
                
                <div class="space-y-4">
                    <!-- Nombre -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700">
                            Nombre completo
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="name" 
                                name="name" 
                                type="text" 
                                autocomplete="name" 
                                required 
                                value="{{ old('name') }}"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('name') border-red-300 @enderror"
                                placeholder="Juan Pérez"
                            >
                            <i data-lucide="user" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Correo electrónico
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="email" 
                                name="email" 
                                type="email" 
                                autocomplete="email" 
                                required 
                                value="{{ old('email') }}"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('email') border-red-300 @enderror"
                                placeholder="tu@hotel.com"
                            >
                            <i data-lucide="mail" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            Teléfono (opcional)
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="phone" 
                                name="phone" 
                                type="tel" 
                                value="{{ old('phone') }}"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('phone') border-red-300 @enderror"
                                placeholder="+52 998 123 4567"
                            >
                            <i data-lucide="phone" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('phone')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nombre de la empresa -->
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">
                            Nombre del hotel/empresa
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="company_name" 
                                name="company_name" 
                                type="text" 
                                value="{{ old('company_name') }}"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('company_name') border-red-300 @enderror"
                                placeholder="Hotel Paradise"
                            >
                            <i data-lucide="building" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('company_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Plan -->
                    <div>
                        <label for="client_level_id" class="block text-sm font-medium text-gray-700">
                            Plan inicial
                        </label>
                        <div class="mt-1 relative">
                            <select 
                                id="client_level_id" 
                                name="client_level_id" 
                                required
                                class="appearance-none block w-full px-3 py-2 pl-10 pr-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('client_level_id') border-red-300 @enderror"
                            >
                                <option value="">Selecciona un plan</option>
                                @foreach($clientLevels as $level)
                                    <option value="{{ $level->id }}" {{ old('client_level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->display_name }} 
                                        @if($level->monthly_price > 0)
                                            - ${{ $level->monthly_price }}/mes
                                        @else
                                            - Gratis
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                            <i data-lucide="credit-card" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                            <i data-lucide="chevron-down" class="absolute right-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('client_level_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Contraseña
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="password" 
                                name="password" 
                                type="password" 
                                autocomplete="new-password" 
                                required 
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('password') border-red-300 @enderror"
                                placeholder="••••••••"
                            >
                            <i data-lucide="lock" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                        @error('password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Confirmar Password -->
                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                            Confirmar contraseña
                        </label>
                        <div class="mt-1 relative">
                            <input 
                                id="password_confirmation" 
                                name="password_confirmation" 
                                type="password" 
                                autocomplete="new-password" 
                                required 
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="••••••••"
                            >
                            <i data-lucide="lock" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Términos y condiciones -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input 
                            id="terms" 
                            name="terms" 
                            type="checkbox" 
                            required
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="terms" class="text-gray-700">
                            Acepto los 
                            <a href="#" class="text-blue-600 hover:text-blue-500">términos y condiciones</a>
                            y la 
                            <a href="#" class="text-blue-600 hover:text-blue-500">política de privacidad</a>
                        </label>
                    </div>
                </div>

                <!-- Botón de Registro -->
                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i data-lucide="user-plus" class="h-4 w-4 text-blue-300"></i>
                        </span>
                        Crear cuenta
                    </button>
                </div>
            </form>

            <!-- Link al login -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    ¿Ya tienes una cuenta?
                    <a href="{{ route('client.login') }}" class="font-medium text-blue-600 hover:text-blue-500">
                        Inicia sesión aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

    <!-- Notificaciones -->
    @if($errors->any())
        <div class="fixed top-4 right-4 bg-red-500 text-white p-4 rounded-lg shadow-lg max-w-sm">
            <div class="flex items-start">
                <i data-lucide="alert-circle" class="h-5 w-5 mr-2 mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="font-medium">Error en el registro</p>
                    <ul class="mt-1 text-sm">
                        @foreach($errors->all() as $error)
                            <li>• {{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <script>
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Auto-ocultar notificaciones después de 8 segundos
        setTimeout(() => {
            const notifications = document.querySelectorAll('.fixed.top-4.right-4');
            notifications.forEach(notification => {
                notification.style.opacity = '0';
                setTimeout(() => notification.remove(), 300);
            });
        }, 8000);
    </script>
</body>
</html>