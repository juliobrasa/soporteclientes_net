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

// ================================================================
// RUTAS PÚBLICAS (SIN AUTENTICACIÓN)
// ================================================================

// Ruta de prueba
Route::get('/test', function () {
    return response()->json([
        'message' => '🚀 Kavia Laravel API funcionando!',
        'version' => '1.0.0',
        'timestamp' => now()->format('Y-m-d H:i:s')
    ]);
});

// Rutas de autenticación
Route::post('/auth/login', [AuthController::class, 'login']);

// Rutas protegidas por autenticación
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
    // RUTAS PROTEGIDAS PARA ADMINISTRADORES
    // ================================================================
    Route::middleware('admin')->group(function () {
        
        // Grupo de rutas para hoteles
        Route::prefix('hotels')->group(function () {
            // CRUD básico
            Route::get('/', [HotelController::class, 'index']);           // GET /api/hotels
            Route::post('/', [HotelController::class, 'store']);          // POST /api/hotels
            Route::get('/{hotel}', [HotelController::class, 'show']);     // GET /api/hotels/{id}
            Route::put('/{hotel}', [HotelController::class, 'update']);   // PUT /api/hotels/{id}
            Route::delete('/{hotel}', [HotelController::class, 'destroy']); // DELETE /api/hotels/{id}
            
            // Rutas adicionales
            Route::post('/{hotel}/toggle-status', [HotelController::class, 'toggleStatus']); // POST /api/hotels/{id}/toggle-status
            Route::get('/stats/summary', [HotelController::class, 'stats']); // GET /api/hotels/stats/summary
        });

        // Grupo de rutas para proveedores de IA
        Route::prefix('ai-providers')->group(function () {
            // Rutas especiales primero (antes del resource)
            Route::get('/defaults', [AiProviderController::class, 'getDefaults']); // GET /api/ai-providers/defaults
            Route::get('/stats', [AiProviderController::class, 'stats']);         // GET /api/ai-providers/stats
            
            // CRUD básico
            Route::get('/', [AiProviderController::class, 'index']);              // GET /api/ai-providers
            Route::post('/', [AiProviderController::class, 'store']);             // POST /api/ai-providers
            Route::get('/{aiProvider}', [AiProviderController::class, 'show']);   // GET /api/ai-providers/{id}
            Route::put('/{aiProvider}', [AiProviderController::class, 'update']); // PUT /api/ai-providers/{id}
            Route::delete('/{aiProvider}', [AiProviderController::class, 'destroy']); // DELETE /api/ai-providers/{id}
            
            // Rutas adicionales
            Route::post('/{aiProvider}/toggle', [AiProviderController::class, 'toggle']); // POST /api/ai-providers/{id}/toggle
            Route::post('/{aiProvider}/test', [AiProviderController::class, 'test']);     // POST /api/ai-providers/{id}/test
        });

        // Grupo de rutas para prompts
        Route::prefix('prompts')->group(function () {
            // Rutas especiales primero (antes del resource)
            Route::get('/stats', [PromptController::class, 'getStats']);                     // GET /api/prompts/stats
            Route::get('/templates-library', [PromptController::class, 'getTemplatesLibrary']); // GET /api/prompts/templates-library
            Route::post('/import-template', [PromptController::class, 'importTemplate']);    // POST /api/prompts/import-template
            Route::get('/export', [PromptController::class, 'exportPrompts']);              // GET /api/prompts/export
            Route::get('/recommended/{category}', [PromptController::class, 'getRecommended']); // GET /api/prompts/recommended/{category}
            
            // CRUD básico
            Route::get('/', [PromptController::class, 'index']);                            // GET /api/prompts
            Route::post('/', [PromptController::class, 'store']);                           // POST /api/prompts
            Route::get('/{prompt}', [PromptController::class, 'show']);                     // GET /api/prompts/{id}
            Route::put('/{prompt}', [PromptController::class, 'update']);                   // PUT /api/prompts/{id}
            Route::delete('/{prompt}', [PromptController::class, 'destroy']);               // DELETE /api/prompts/{id}
            
            // Rutas adicionales
            Route::post('/{prompt}/duplicate', [PromptController::class, 'duplicate']);     // POST /api/prompts/{id}/duplicate
            Route::post('/{prompt}/test', [PromptController::class, 'testPrompt']);         // POST /api/prompts/{id}/test
        });

        // Grupo de rutas para APIs externas
        Route::prefix('external-apis')->group(function () {
            // Rutas especiales primero (antes del resource)
            Route::get('/defaults', [ExternalApiController::class, 'defaults']);           // GET /api/external-apis/defaults
            Route::get('/stats', [ExternalApiController::class, 'stats']);                // GET /api/external-apis/stats
            
            // CRUD básico
            Route::get('/', [ExternalApiController::class, 'index']);                      // GET /api/external-apis
            Route::post('/', [ExternalApiController::class, 'store']);                     // POST /api/external-apis
            Route::get('/{externalApi}', [ExternalApiController::class, 'show']);          // GET /api/external-apis/{id}
            Route::put('/{externalApi}', [ExternalApiController::class, 'update']);        // PUT /api/external-apis/{id}
            Route::delete('/{externalApi}', [ExternalApiController::class, 'destroy']);    // DELETE /api/external-apis/{id}
            
            // Rutas adicionales
            Route::post('/{externalApi}/toggle', [ExternalApiController::class, 'toggle']); // POST /api/external-apis/{id}/toggle
            Route::post('/{externalApi}/test', [ExternalApiController::class, 'test']);     // POST /api/external-apis/{id}/test
            Route::post('/{externalApi}/usage', [ExternalApiController::class, 'incrementUsage']); // POST /api/external-apis/{id}/usage
        });

        // Grupo de rutas para logs del sistema  
        Route::prefix('system-logs')->group(function () {
            // Rutas especiales primero (antes del resource)
            Route::get('/stats', [SystemLogController::class, 'stats']);             // GET /api/system-logs/stats
            Route::get('/timeline', [SystemLogController::class, 'timeline']);       // GET /api/system-logs/timeline
            Route::get('/config', [SystemLogController::class, 'config']);           // GET /api/system-logs/config
            Route::get('/export', [SystemLogController::class, 'export']);           // GET /api/system-logs/export
            Route::post('/cleanup', [SystemLogController::class, 'cleanup']);        // POST /api/system-logs/cleanup
            
            // CRUD básico
            Route::get('/', [SystemLogController::class, 'index']);                   // GET /api/system-logs
            Route::post('/', [SystemLogController::class, 'store']);                  // POST /api/system-logs
            Route::get('/{systemLog}', [SystemLogController::class, 'show']);         // GET /api/system-logs/{id}
            Route::delete('/{systemLog}', [SystemLogController::class, 'destroy']);   // DELETE /api/system-logs/{id}
            
            // Rutas adicionales
            Route::post('/{systemLog}/resolve', [SystemLogController::class, 'resolve']); // POST /api/system-logs/{id}/resolve
        });

        // Grupo de rutas para trabajos de extracción
        Route::prefix('extraction-jobs')->group(function () {
            // Rutas especiales primero (antes del resource)
            Route::get('/stats', [ExtractionController::class, 'stats']);              // GET /api/extraction-jobs/stats
            Route::get('/hotels', [ExtractionController::class, 'hotels']);            // GET /api/extraction-jobs/hotels
            
            // CRUD básico
            Route::get('/', [ExtractionController::class, 'index']);                   // GET /api/extraction-jobs
            Route::post('/', [ExtractionController::class, 'store']);                  // POST /api/extraction-jobs
            Route::get('/{extractionJob}', [ExtractionController::class, 'show']);     // GET /api/extraction-jobs/{id}
            Route::put('/{extractionJob}', [ExtractionController::class, 'update']);   // PUT /api/extraction-jobs/{id}
            Route::delete('/{extractionJob}', [ExtractionController::class, 'destroy']); // DELETE /api/extraction-jobs/{id}
            
            // Rutas de control de trabajo
            Route::post('/{extractionJob}/start', [ExtractionController::class, 'start']);   // POST /api/extraction-jobs/{id}/start
            Route::post('/{extractionJob}/pause', [ExtractionController::class, 'pause']);   // POST /api/extraction-jobs/{id}/pause
            Route::post('/{extractionJob}/cancel', [ExtractionController::class, 'cancel']); // POST /api/extraction-jobs/{id}/cancel
            Route::post('/{extractionJob}/retry', [ExtractionController::class, 'retry']);   // POST /api/extraction-jobs/{id}/retry
            Route::post('/{extractionJob}/clone', [ExtractionController::class, 'clone']);   // POST /api/extraction-jobs/{id}/clone
            
            // Rutas de información detallada
            Route::get('/{extractionJob}/runs', [ExtractionController::class, 'runs']);     // GET /api/extraction-jobs/{id}/runs
            Route::get('/{extractionJob}/logs', [ExtractionController::class, 'logs']);     // GET /api/extraction-jobs/{id}/logs
        });

        // Grupo de rutas para herramientas de sistema
        Route::prefix('tools')->group(function () {
            Route::get('/stats', [ToolsController::class, 'getStats']);                    // GET /api/tools/stats
            Route::get('/duplicates', [ToolsController::class, 'scanDuplicates']);         // GET /api/tools/duplicates
            Route::delete('/duplicates', [ToolsController::class, 'deleteDuplicates']);    // DELETE /api/tools/duplicates
            Route::post('/optimize', [ToolsController::class, 'optimizeTables']);          // POST /api/tools/optimize
            Route::get('/integrity', [ToolsController::class, 'checkIntegrity']);          // GET /api/tools/integrity
            Route::get('/system-info', [ToolsController::class, 'getSystemInfo']);        // GET /api/tools/system-info
        });
    });
});

// ================================================================
// RUTAS DE COMPATIBILIDAD CON SISTEMA ACTUAL (PÚBLICAS PARA MIGRACIÓN)
// ================================================================

// Rutas de compatibilidad para el sistema actual (sin autenticación para facilitar migración)

// HOTELES - Legacy routes
Route::get('/legacy/hotels', [HotelController::class, 'index']);
Route::post('/legacy/hotels', [HotelController::class, 'store']);
Route::get('/legacy/hotels/{hotel}', [HotelController::class, 'show']);
Route::put('/legacy/hotels/{hotel}', [HotelController::class, 'update']);
Route::delete('/legacy/hotels/{hotel}', [HotelController::class, 'destroy']);
Route::post('/legacy/hotels/{hotel}/toggle-status', [HotelController::class, 'toggleStatus']);

// AI PROVIDERS - Legacy routes
Route::get('/legacy/ai-providers', [AiProviderController::class, 'index']);
Route::post('/legacy/ai-providers', [AiProviderController::class, 'store']);
Route::get('/legacy/ai-providers/{aiProvider}', [AiProviderController::class, 'show']);
Route::put('/legacy/ai-providers/{aiProvider}', [AiProviderController::class, 'update']);
Route::delete('/legacy/ai-providers/{aiProvider}', [AiProviderController::class, 'destroy']);
Route::post('/legacy/ai-providers/{aiProvider}/toggle', [AiProviderController::class, 'toggle']);

// PROMPTS - Legacy routes
Route::get('/legacy/prompts', [PromptController::class, 'index']);
Route::post('/legacy/prompts', [PromptController::class, 'store']);
Route::get('/legacy/prompts/{prompt}', [PromptController::class, 'show']);
Route::put('/legacy/prompts/{prompt}', [PromptController::class, 'update']);
Route::delete('/legacy/prompts/{prompt}', [PromptController::class, 'destroy']);

// EXTERNAL APIS - Legacy routes  
Route::get('/legacy/external-apis', [ExternalApiController::class, 'index']);
Route::post('/legacy/external-apis', [ExternalApiController::class, 'store']);
Route::get('/legacy/external-apis/{externalApi}', [ExternalApiController::class, 'show']);
Route::put('/legacy/external-apis/{externalApi}', [ExternalApiController::class, 'update']);
Route::delete('/legacy/external-apis/{externalApi}', [ExternalApiController::class, 'destroy']);

// SYSTEM LOGS - Legacy routes
Route::get('/legacy/system-logs', [SystemLogController::class, 'index']);
Route::post('/legacy/system-logs', [SystemLogController::class, 'store']);

// EXTRACTION JOBS - Legacy routes
Route::get('/legacy/extraction-jobs', [ExtractionController::class, 'index']);
Route::post('/legacy/extraction-jobs', [ExtractionController::class, 'store']);
Route::get('/legacy/extraction-jobs/{extractionJob}', [ExtractionController::class, 'show']);

// TOOLS - Legacy routes
Route::get('/legacy/tools', [ToolsController::class, 'getStats']);
