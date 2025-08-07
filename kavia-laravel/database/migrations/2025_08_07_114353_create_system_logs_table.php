<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('level', ['debug', 'info', 'warning', 'error', 'critical'])->default('info'); // Nivel del log
            $table->string('module', 50)->index(); // Módulo que generó el log (auth, hotels, apis, etc.)
            $table->string('action', 100)->nullable(); // Acción específica (create, update, delete, etc.)
            $table->text('message'); // Mensaje principal del log
            $table->longText('context')->nullable(); // Contexto adicional en JSON
            $table->string('user_id')->nullable()->index(); // ID del usuario (puede ser string para legacy)
            $table->string('session_id', 100)->nullable()->index(); // ID de sesión
            $table->string('ip_address', 45)->nullable()->index(); // IP del usuario
            $table->string('user_agent')->nullable(); // User agent del navegador
            $table->string('request_id', 36)->nullable()->index(); // ID único de request
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->integer('duration_ms')->nullable(); // Duración en milisegundos (para requests)
            $table->integer('memory_mb')->nullable(); // Uso de memoria en MB
            $table->string('trace_id', 36)->nullable()->index(); // ID de traza para debugging
            $table->boolean('is_resolved')->default(false); // Si el error/warning fue resuelto
            $table->timestamp('resolved_at')->nullable(); // Cuándo se resolvió
            $table->string('resolved_by')->nullable(); // Quién lo resolvió
            $table->text('resolution_notes')->nullable(); // Notas de resolución
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['level', 'created_at']); // Búsqueda por nivel y tiempo
            $table->index(['module', 'created_at']); // Búsqueda por módulo y tiempo
            $table->index(['created_at', 'level']); // Timeline ordenado por fecha
            $table->index('is_resolved'); // Filtro de logs resueltos/pendientes
            $table->index(['user_id', 'created_at']); // Logs por usuario
            
            // Índice compuesto para búsquedas comunes
            $table->index(['level', 'module', 'created_at'], 'logs_level_module_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};
