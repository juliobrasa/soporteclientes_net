<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HotelController;
use App\Http\Controllers\API\AiProviderController;
use App\Http\Controllers\API\PromptController;
use App\Http\Controllers\API\ExternalApiController;

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

// ================================================================
// RUTAS DE HOTELES (PÚBLICAS TEMPORALMENTE PARA TESTING)
// ================================================================

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

// ================================================================
// RUTAS DE AI PROVIDERS (PÚBLICAS TEMPORALMENTE PARA TESTING)
// ================================================================

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

// ================================================================
// RUTAS DE PROMPTS (PÚBLICAS TEMPORALMENTE PARA TESTING)
// ================================================================

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

// ================================================================
// RUTAS DE EXTERNAL APIS (PÚBLICAS TEMPORALMENTE PARA TESTING)
// ================================================================

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

// ================================================================
// RUTAS DE COMPATIBILIDAD CON SISTEMA ACTUAL
// ================================================================

// Rutas de compatibilidad para el sistema actual
Route::get('/legacy/hotels', [HotelController::class, 'index']);
Route::get('/legacy/ai-providers', [AiProviderController::class, 'index']);
Route::get('/legacy/prompts', [PromptController::class, 'index']);
Route::get('/legacy/external-apis', [ExternalApiController::class, 'index']);

// ================================================================
// RUTAS PROTEGIDAS (COMENTADAS TEMPORALMENTE)
// ================================================================

// TODO: Descomentar cuando implementemos autenticación
/*
Route::middleware('auth:sanctum')->group(function () {
    // Usuario autenticado
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    
    // Hoteles protegidos
    Route::apiResource('hotels', HotelController::class);
    Route::post('hotels/{hotel}/toggle-status', [HotelController::class, 'toggleStatus']);
    Route::get('hotels/stats/summary', [HotelController::class, 'stats']);
});
*/
