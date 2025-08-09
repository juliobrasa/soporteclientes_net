<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FidelitySuite</title>
    <meta name="csrf-token" content="<?php echo bin2hex(random_bytes(32)); ?>">
    
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
                    Inicia sesión en tu cuenta
                </h2>
            </div>

            <!-- Mensaje de estado -->
            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-300 rounded-lg p-4">
                    <div class="flex items-center">
                        <i data-lucide="alert-circle" class="h-5 w-5 text-red-600 mr-2"></i>
                        <span class="text-red-700">
                            <?php 
                            if ($_GET['error'] === 'credentials') echo 'Credenciales incorrectas';
                            elseif ($_GET['error'] === 'inactive') echo 'Tu cuenta ha sido desactivada';
                            else echo 'Error al iniciar sesión';
                            ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-300 rounded-lg p-4">
                    <div class="flex items-center">
                        <i data-lucide="check-circle" class="h-5 w-5 text-green-600 mr-2"></i>
                        <span class="text-green-700">Login exitoso. Redirigiendo...</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Formulario de Login -->
            <form class="mt-8 space-y-6" method="POST" action="client-auth.php">
                <input type="hidden" name="action" value="login">
                
                <div class="space-y-4">
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
                                value="<?php echo htmlspecialchars($_GET['email'] ?? ''); ?>"
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="tu@email.com"
                            >
                            <i data-lucide="mail" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
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
                                autocomplete="current-password" 
                                required 
                                class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-lg placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="••••••••"
                            >
                            <i data-lucide="lock" class="absolute left-3 top-2.5 h-4 w-4 text-gray-400"></i>
                        </div>
                    </div>
                </div>

                <!-- Recordar sesión -->
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input 
                            id="remember" 
                            name="remember" 
                            type="checkbox" 
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                        >
                        <label for="remember" class="ml-2 block text-sm text-gray-700">
                            Recordar sesión
                        </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                </div>

                <!-- Botón de Login -->
                <div>
                    <button 
                        type="submit" 
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-200"
                    >
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i data-lucide="log-in" class="h-4 w-4 text-blue-300"></i>
                        </span>
                        Iniciar Sesión
                    </button>
                </div>
            </form>

            <!-- Usuarios de Prueba -->
            <div class="mt-8 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Usuarios de Prueba:</h3>
                <div class="space-y-2 text-xs text-gray-600">
                    <div class="flex justify-between">
                        <span><strong>Demo:</strong> demo@cliente.com</span>
                        <span class="text-gray-400">demo123</span>
                    </div>
                    <div class="flex justify-between">
                        <span><strong>Profesional:</strong> admin@terracaribe.com</span>
                        <span class="text-gray-400">terracaribe2025</span>
                    </div>
                    <div class="flex justify-between">
                        <span><strong>Empresarial:</strong> admin@grupohotels.com</span>
                        <span class="text-gray-400">premium2025</span>
                    </div>
                </div>
            </div>

            <!-- Link al registro -->
            <div class="text-center">
                <p class="text-sm text-gray-600">
                    ¿No tienes una cuenta?
                    <a href="client-register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Regístrate aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Auto-redirigir si es exitoso
        <?php if (isset($_GET['success'])): ?>
            setTimeout(() => {
                window.location.href = 'client-dashboard.php';
            }, 2000);
        <?php endif; ?>
    </script>
</body>
</html>