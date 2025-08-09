<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Client\DashboardController;
use App\Http\Controllers\Client\AuthController;

// Ruta principal - redireccionar al admin
Route::get('/', function () {
    return redirect()->route('admin.dashboard');
});

// Rutas de autenticación (Laravel Breeze/UI)
Auth::routes();

// Rutas públicas del panel de clientes (autenticación)
Route::prefix('client')->name('client.')->group(function () {
    // Rutas de autenticación (sin middleware)
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/subscription-expired', [AuthController::class, 'subscriptionExpired'])->name('subscription.expired');
    
    // Rutas protegidas con autenticación de cliente
    Route::middleware(['client.auth'])->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/', [DashboardController::class, 'index']); // Alias
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    });
});

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
