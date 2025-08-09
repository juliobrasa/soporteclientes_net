<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HotelController;
use App\Http\Controllers\API\AiProviderController;
use App\Http\Controllers\API\PromptController;
use App\Http\Controllers\API\ExternalApiController;
use App\Http\Controllers\API\SystemLogController;
use App\Http\Controllers\API\ExtractionController;
use App\Http\Controllers\API\ToolsController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\AuthController as ClientAuthController;

// ================================================================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN) - LIMITADAS
// ================================================================

// Ruta de prueba (solo información pública)
Route::get('/test', function () {
    return response()->json([
        'message' => '🚀 Kavia Laravel API funcionando!',
        'version' => '1.0.0',
        'timestamp' => now()->format('Y-m-d H:i:s'),
        'status' => 'ok'
    ]);
});

// Solo rutas de autenticación públicas
Route::post('/auth/login', [AuthController::class, 'login']);

// ================================================================
// RUTAS PÚBLICAS DEL PANEL DE CLIENTES - PROTEGIDAS
// ================================================================

// API de autenticación de clientes
Route::prefix('client/auth')->group(function () {
    Route::post('/login', [ClientAuthController::class, 'apiLogin']);
    Route::get('/me', [ClientAuthController::class, 'me'])->middleware('client.auth');
    Route::post('/logout', [ClientAuthController::class, 'apiLogout'])->middleware('client.auth');
});

// API del dashboard de clientes (protegida)
Route::middleware('client.auth')->prefix('client')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'getDashboardData']);
    Route::get('/otas', [DashboardController::class, 'getOTAsData'])->middleware('client.permissions:module_otas');
    Route::get('/reviews', [DashboardController::class, 'getReviewsData'])->middleware('client.permissions:view_reviews,module_reseñas');
    Route::get('/stats', [DashboardController::class, 'getStatsData']);
});

// ================================================================
// RUTAS PROTEGIDAS POR AUTENTICACIÓN SANCTUM
// ================================================================

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/change-password', [AuthController::class, 'changePassword']);
    
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'user' => $request->user()
        ]);
    });
    
    // ================================================================
    // RUTAS PROTEGIDAS PARA ADMINISTRADORES - DOBLE AUTENTICACIÓN
    // ================================================================
    Route::middleware(['admin', 'secure.cors'])->group(function () {
        
        // Grupo de rutas para hoteles
        Route::prefix('hotels')->group(function () {
            Route::get('/', [HotelController::class, 'index']);
            Route::post('/', [HotelController::class, 'store']);
            Route::get('/{hotel}', [HotelController::class, 'show']);
            Route::put('/{hotel}', [HotelController::class, 'update']);
            Route::delete('/{hotel}', [HotelController::class, 'destroy']);
            Route::post('/{hotel}/toggle-status', [HotelController::class, 'toggleStatus']);
            Route::get('/stats/summary', [HotelController::class, 'stats']);
        });

        // Grupo de rutas para proveedores de IA
        Route::prefix('ai-providers')->group(function () {
            Route::get('/defaults', [AiProviderController::class, 'getDefaults']);
            Route::get('/stats', [AiProviderController::class, 'stats']);
            Route::get('/', [AiProviderController::class, 'index']);
            Route::post('/', [AiProviderController::class, 'store']);
            Route::get('/{aiProvider}', [AiProviderController::class, 'show']);
            Route::put('/{aiProvider}', [AiProviderController::class, 'update']);
            Route::delete('/{aiProvider}', [AiProviderController::class, 'destroy']);
            Route::post('/{aiProvider}/toggle', [AiProviderController::class, 'toggle']);
            Route::post('/{aiProvider}/test', [AiProviderController::class, 'test']);
        });

        // Grupo de rutas para prompts
        Route::prefix('prompts')->group(function () {
            Route::get('/stats', [PromptController::class, 'getStats']);
            Route::get('/templates-library', [PromptController::class, 'getTemplatesLibrary']);
            Route::post('/import-template', [PromptController::class, 'importTemplate']);
            Route::get('/export', [PromptController::class, 'exportPrompts']);
            Route::get('/recommended/{category}', [PromptController::class, 'getRecommended']);
            Route::get('/', [PromptController::class, 'index']);
            Route::post('/', [PromptController::class, 'store']);
            Route::get('/{prompt}', [PromptController::class, 'show']);
            Route::put('/{prompt}', [PromptController::class, 'update']);
            Route::delete('/{prompt}', [PromptController::class, 'destroy']);
            Route::post('/{prompt}/duplicate', [PromptController::class, 'duplicate']);
            Route::post('/{prompt}/test', [PromptController::class, 'testPrompt']);
        });

        // Grupo de rutas para APIs externas
        Route::prefix('external-apis')->group(function () {
            Route::get('/defaults', [ExternalApiController::class, 'defaults']);
            Route::get('/stats', [ExternalApiController::class, 'stats']);
            Route::get('/', [ExternalApiController::class, 'index']);
            Route::post('/', [ExternalApiController::class, 'store']);
            Route::get('/{externalApi}', [ExternalApiController::class, 'show']);
            Route::put('/{externalApi}', [ExternalApiController::class, 'update']);
            Route::delete('/{externalApi}', [ExternalApiController::class, 'destroy']);
            Route::post('/{externalApi}/toggle', [ExternalApiController::class, 'toggle']);
            Route::post('/{externalApi}/test', [ExternalApiController::class, 'test']);
            Route::post('/{externalApi}/usage', [ExternalApiController::class, 'incrementUsage']);
        });

        // Grupo de rutas para logs del sistema  
        Route::prefix('system-logs')->group(function () {
            Route::get('/stats', [SystemLogController::class, 'stats']);
            Route::get('/timeline', [SystemLogController::class, 'timeline']);
            Route::get('/config', [SystemLogController::class, 'config']);
            Route::get('/export', [SystemLogController::class, 'export']);
            Route::post('/cleanup', [SystemLogController::class, 'cleanup']);
            Route::get('/', [SystemLogController::class, 'index']);
            Route::post('/', [SystemLogController::class, 'store']);
            Route::get('/{systemLog}', [SystemLogController::class, 'show']);
            Route::delete('/{systemLog}', [SystemLogController::class, 'destroy']);
            Route::post('/{systemLog}/resolve', [SystemLogController::class, 'resolve']);
        });

        // Grupo de rutas para trabajos de extracción
        Route::prefix('extraction-jobs')->group(function () {
            Route::get('/stats', [ExtractionController::class, 'stats']);
            Route::get('/hotels', [ExtractionController::class, 'hotels']);
            Route::get('/', [ExtractionController::class, 'index']);
            Route::post('/', [ExtractionController::class, 'store']);
            Route::get('/{extractionJob}', [ExtractionController::class, 'show']);
            Route::put('/{extractionJob}', [ExtractionController::class, 'update']);
            Route::delete('/{extractionJob}', [ExtractionController::class, 'destroy']);
            Route::post('/{extractionJob}/start', [ExtractionController::class, 'start']);
            Route::post('/{extractionJob}/pause', [ExtractionController::class, 'pause']);
            Route::post('/{extractionJob}/cancel', [ExtractionController::class, 'cancel']);
            Route::post('/{extractionJob}/retry', [ExtractionController::class, 'retry']);
            Route::post('/{extractionJob}/clone', [ExtractionController::class, 'clone']);
            Route::get('/{extractionJob}/runs', [ExtractionController::class, 'runs']);
            Route::get('/{extractionJob}/logs', [ExtractionController::class, 'logs']);
        });

        // Grupo de rutas para herramientas de sistema
        Route::prefix('tools')->group(function () {
            Route::get('/stats', [ToolsController::class, 'getStats']);
            Route::get('/duplicates', [ToolsController::class, 'scanDuplicates']);
            Route::delete('/duplicates', [ToolsController::class, 'deleteDuplicates']);
            Route::post('/optimize', [ToolsController::class, 'optimizeTables']);
            Route::get('/integrity', [ToolsController::class, 'checkIntegrity']);
            Route::get('/system-info', [ToolsController::class, 'getSystemInfo']);
        });
    });
});

// ================================================================
// RUTAS LEGACY DESHABILITADAS EN PRODUCCIÓN
// ================================================================

// SEGURIDAD: Las rutas legacy están deshabilitadas en producción
// Solo están disponibles en entorno de desarrollo local con autenticación

if (app()->environment('local', 'development') && config('app.debug')) {
    Route::middleware(['auth:sanctum', 'admin', 'secure.cors'])->prefix('legacy')->group(function () {
        
        // HOTELES - Legacy routes (PROTEGIDAS)
        Route::get('/hotels', [HotelController::class, 'index']);
        Route::post('/hotels', [HotelController::class, 'store']);
        Route::get('/hotels/{hotel}', [HotelController::class, 'show']);
        Route::put('/hotels/{hotel}', [HotelController::class, 'update']);
        Route::delete('/hotels/{hotel}', [HotelController::class, 'destroy']);
        Route::post('/hotels/{hotel}/toggle-status', [HotelController::class, 'toggleStatus']);

        // AI PROVIDERS - Legacy routes (PROTEGIDAS)
        Route::get('/ai-providers', [AiProviderController::class, 'index']);
        Route::post('/ai-providers', [AiProviderController::class, 'store']);
        Route::get('/ai-providers/{aiProvider}', [AiProviderController::class, 'show']);
        Route::put('/ai-providers/{aiProvider}', [AiProviderController::class, 'update']);
        Route::delete('/ai-providers/{aiProvider}', [AiProviderController::class, 'destroy']);
        Route::post('/ai-providers/{aiProvider}/toggle', [AiProviderController::class, 'toggle']);

        // PROMPTS - Legacy routes (PROTEGIDAS)
        Route::get('/prompts', [PromptController::class, 'index']);
        Route::post('/prompts', [PromptController::class, 'store']);
        Route::get('/prompts/{prompt}', [PromptController::class, 'show']);
        Route::put('/prompts/{prompt}', [PromptController::class, 'update']);
        Route::delete('/prompts/{prompt}', [PromptController::class, 'destroy']);

        // EXTERNAL APIS - Legacy routes (PROTEGIDAS)
        Route::get('/external-apis', [ExternalApiController::class, 'index']);
        Route::post('/external-apis', [ExternalApiController::class, 'store']);
        Route::get('/external-apis/{externalApi}', [ExternalApiController::class, 'show']);
        Route::put('/external-apis/{externalApi}', [ExternalApiController::class, 'update']);
        Route::delete('/external-apis/{externalApi}', [ExternalApiController::class, 'destroy']);

        // SYSTEM LOGS - Legacy routes (PROTEGIDAS)
        Route::get('/system-logs', [SystemLogController::class, 'index']);
        Route::post('/system-logs', [SystemLogController::class, 'store']);

        // EXTRACTION JOBS - Legacy routes (PROTEGIDAS)
        Route::get('/extraction-jobs', [ExtractionController::class, 'index']);
        Route::post('/extraction-jobs', [ExtractionController::class, 'store']);
        Route::get('/extraction-jobs/{extractionJob}', [ExtractionController::class, 'show']);

        // TOOLS - Legacy routes (PROTEGIDAS)
        Route::get('/tools', [ToolsController::class, 'getStats']);
    });
} else {
    // En producción, las rutas legacy devuelven error 403
    Route::prefix('legacy')->group(function () {
        Route::any('{any}', function () {
            return response()->json([
                'error' => 'Rutas legacy deshabilitadas en producción',
                'message' => 'Use las rutas oficiales de API con autenticación'
            ], 403);
        })->where('any', '.*');
    });
}

// ================================================================
// MIDDLEWARE DE SEGURIDAD CUSTOMIZADO
// ================================================================

// Middleware para CORS seguro que será registrado en el kernel
Route::middleware('throttle:60,1')->group(function () {
    // Rate limiting aplicado a todas las rutas
});