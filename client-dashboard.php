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
    <title>Dashboard - FidelitySuite</title>
    
    <!-- TailwindCSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lucide/0.263.1/lucide.min.css">
    <script src="https://unpkg.com/lucide@0.263.1/dist/umd/lucide.js"></script>

    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">
    
    <!-- Header -->
    <header class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div class="flex items-center">
                    <div class="text-2xl font-bold text-white mr-8">
                        <span class="text-blue-100">Fidelity</span>
                        <span class="text-cyan-200">Suite</span>
                    </div>
                    <div class="hidden md:block">
                        <nav class="flex space-x-8">
                            <a href="#dashboard" class="text-white hover:text-blue-100 px-3 py-2 rounded-md text-sm font-medium">
                                Dashboard
                            </a>
                            <?php if (hasModule('resumen', $modules)): ?>
                            <a href="#resumen" class="text-blue-100 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                Resumen
                            </a>
                            <?php endif; ?>
                            <?php if (hasModule('otas', $modules)): ?>
                            <a href="#otas" class="text-blue-100 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                OTAs
                            </a>
                            <?php endif; ?>
                            <?php if (hasModule('reseñas', $modules)): ?>
                            <a href="#reviews" class="text-blue-100 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                Reseñas
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- Información del usuario -->
                    <div class="text-right">
                        <div class="text-sm font-medium text-white"><?php echo htmlspecialchars($user['name']); ?></div>
                        <div class="text-xs text-blue-100">
                            Plan <?php echo htmlspecialchars($user['level_display']); ?>
                        </div>
                    </div>
                    
                    <!-- Menú usuario -->
                    <div class="relative">
                        <button class="flex items-center p-2 rounded-full text-white hover:bg-white hover:bg-opacity-20 transition">
                            <i data-lucide="user" class="h-5 w-5"></i>
                        </button>
                    </div>
                    
                    <!-- Logout -->
                    <form method="POST" action="client-auth.php" class="inline">
                        <input type="hidden" name="action" value="logout">
                        <button type="submit" class="flex items-center p-2 rounded-full text-white hover:bg-white hover:bg-opacity-20 transition">
                            <i data-lucide="log-out" class="h-5 w-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        
        <!-- Bienvenida -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">
                ¡Bienvenido, <?php echo htmlspecialchars($user['name']); ?>!
            </h1>
            <p class="mt-2 text-lg text-gray-600">
                Panel de control de reputación hotelera - Plan <?php echo htmlspecialchars($user['level_display']); ?>
            </p>
        </div>

        <!-- Stats generales -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Hoteles -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <i data-lucide="building" class="h-6 w-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Hoteles</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count($user['hotels']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Estado suscripción -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <i data-lucide="check-circle" class="h-6 w-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Estado</p>
                        <p class="text-2xl font-semibold text-gray-900 capitalize">
                            <?php echo ucfirst($user['subscription_status']); ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Módulos disponibles -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-600">
                        <i data-lucide="grid-3x3" class="h-6 w-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Módulos</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count(array_filter($modules)); ?></p>
                    </div>
                </div>
            </div>

            <!-- Features disponibles -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-orange-100 text-orange-600">
                        <i data-lucide="star" class="h-6 w-6"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Features</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo count(array_filter($features)); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos principales -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            
            <!-- Módulo Resumen -->
            <?php if (hasModule('resumen', $modules)): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-blue-500 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="bar-chart-3" class="h-5 w-5 mr-2"></i>
                        Resumen General
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4">
                        Vista general del rendimiento y reputación de tus hoteles.
                    </p>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Acceso básico:</span>
                            <span class="text-green-600">✓ Incluido</span>
                        </div>
                    </div>
                    <button class="mt-4 w-full bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded transition">
                        Ver Resumen
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-gray-100 rounded-lg shadow-lg overflow-hidden opacity-60">
                <div class="bg-gray-400 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="lock" class="h-5 w-5 mr-2"></i>
                        Resumen General
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 mb-4">No disponible en tu plan actual.</p>
                    <button disabled class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded cursor-not-allowed">
                        Actualizar Plan
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Módulo OTAs -->
            <?php if (hasModule('otas', $modules)): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-green-500 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="globe" class="h-5 w-5 mr-2"></i>
                        Plataformas OTA
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4">
                        Monitoreo de Booking.com, Expedia, TripAdvisor y más.
                    </p>
                    <div class="space-y-2">
                        <?php if (hasFeature('booking_monitoring', $features)): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Booking.com:</span>
                            <span class="text-green-600">✓ Activo</span>
                        </div>
                        <?php endif; ?>
                        <?php if (hasFeature('expedia_monitoring', $features)): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Expedia:</span>
                            <span class="text-green-600">✓ Activo</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="mt-4 w-full bg-green-500 hover:bg-green-600 text-white py-2 px-4 rounded transition">
                        Ver OTAs
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-gray-100 rounded-lg shadow-lg overflow-hidden opacity-60">
                <div class="bg-gray-400 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="lock" class="h-5 w-5 mr-2"></i>
                        Plataformas OTA
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 mb-4">No disponible en tu plan actual.</p>
                    <button disabled class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded cursor-not-allowed">
                        Actualizar Plan
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Módulo Reseñas -->
            <?php if (hasModule('reseñas', $modules)): ?>
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="bg-purple-500 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="message-square" class="h-5 w-5 mr-2"></i>
                        Análisis de Reseñas
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-600 mb-4">
                        Análisis de sentimientos y respuestas inteligentes.
                    </p>
                    <div class="space-y-2">
                        <?php if (hasFeature('sentiment_analysis', $features)): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Análisis IA:</span>
                            <span class="text-green-600">✓ Incluido</span>
                        </div>
                        <?php endif; ?>
                        <?php if (hasFeature('ai_responses', $features)): ?>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Respuestas IA:</span>
                            <span class="text-green-600">✓ Incluido</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <button class="mt-4 w-full bg-purple-500 hover:bg-purple-600 text-white py-2 px-4 rounded transition">
                        Ver Reseñas
                    </button>
                </div>
            </div>
            <?php else: ?>
            <div class="bg-gray-100 rounded-lg shadow-lg overflow-hidden opacity-60">
                <div class="bg-gray-400 px-6 py-4">
                    <h3 class="text-lg font-semibold text-white flex items-center">
                        <i data-lucide="lock" class="h-5 w-5 mr-2"></i>
                        Análisis de Reseñas
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-500 mb-4">No disponible en tu plan actual.</p>
                    <button disabled class="w-full bg-gray-300 text-gray-500 py-2 px-4 rounded cursor-not-allowed">
                        Actualizar Plan
                    </button>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Hoteles del usuario -->
        <?php if (!empty($user['hotels'])): ?>
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gray-800 px-6 py-4">
                <h3 class="text-lg font-semibold text-white flex items-center">
                    <i data-lucide="building-2" class="h-5 w-5 mr-2"></i>
                    Mis Hoteles (<?php echo count($user['hotels']); ?>)
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($user['hotels'] as $hotel): ?>
                    <div class="border rounded-lg p-4 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-semibold text-gray-900">
                                <?php echo htmlspecialchars($hotel['nombre_hotel']); ?>
                            </h4>
                            <span class="text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded">
                                ID: <?php echo $hotel['id']; ?>
                            </span>
                        </div>
                        <div class="text-sm text-gray-600">
                            <?php 
                            $permissions = json_decode($hotel['permissions'], true);
                            $permissionCount = is_array($permissions) ? count(array_filter($permissions)) : 0;
                            ?>
                            <p>Permisos: <?php echo $permissionCount; ?> activos</p>
                        </div>
                        <button class="mt-3 w-full bg-gray-100 hover:bg-gray-200 text-gray-700 py-2 px-3 rounded text-sm transition">
                            Ver Dashboard
                        </button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
            <i data-lucide="alert-triangle" class="h-12 w-12 text-yellow-400 mx-auto mb-4"></i>
            <h3 class="text-lg font-semibold text-yellow-800 mb-2">No tienes hoteles asignados</h3>
            <p class="text-yellow-700">
                Contacta con nuestro equipo de soporte para configurar el acceso a tus hoteles.
            </p>
            <button class="mt-4 bg-yellow-500 hover:bg-yellow-600 text-white py-2 px-4 rounded transition">
                Contactar Soporte
            </button>
        </div>
        <?php endif; ?>

    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <div class="text-lg font-semibold mb-2">
                    <span class="text-blue-300">Fidelity</span>
                    <span class="text-cyan-300">Suite</span>
                </div>
                <p class="text-gray-400 text-sm">
                    Sistema de Gestión de Reputación Hotelera
                </p>
                <div class="mt-4 text-xs text-gray-500">
                    Plan: <?php echo htmlspecialchars($user['level_display']); ?> | 
                    Estado: <?php echo ucfirst($user['subscription_status']); ?> |
                    Conectado desde: <?php echo date('H:i', $user['login_time']); ?>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Inicializar iconos de Lucide
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    </script>
</body>
</html>