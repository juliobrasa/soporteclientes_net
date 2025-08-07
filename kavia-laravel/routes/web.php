<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;

// Ruta principal - redireccionar al admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Rutas de autenticación (Laravel Breeze/UI)
Auth::routes();

// Grupo de rutas admin con middleware de autenticación y admin
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard principal
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/', [AdminController::class, 'dashboard']); // Alias
    
    // Gestión de hoteles
    Route::get('/hotels', [AdminController::class, 'hotels'])->name('hotels');
    
    // Gestión de proveedores IA
    Route::get('/ai-providers', [AdminController::class, 'aiProviders'])->name('ai-providers');
    
    // Gestión de prompts
    Route::get('/prompts', [AdminController::class, 'prompts'])->name('prompts');
    
    // Gestión de APIs externas
    Route::get('/external-apis', [AdminController::class, 'externalApis'])->name('external-apis');
    
    // Logs del sistema
    Route::get('/system-logs', [AdminController::class, 'systemLogs'])->name('system-logs');
    
    // Trabajos de extracción
    Route::get('/extraction-jobs', [AdminController::class, 'extractionJobs'])->name('extraction-jobs');
    
    // Herramientas del sistema
    Route::get('/tools', [AdminController::class, 'tools'])->name('tools');
});
