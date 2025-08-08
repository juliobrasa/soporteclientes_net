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
        // Solo crear si no existe
        if (!Schema::hasTable('ai_providers')) {
            Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre del proveedor de IA');
            $table->enum('provider_type', ['openai', 'claude', 'deepseek', 'gemini', 'local'])
                  ->comment('Tipo de proveedor de IA');
            $table->text('api_key')->nullable()->comment('Clave API encriptada');
            $table->string('api_url', 500)->nullable()->comment('URL base del API');
            $table->string('model_name')->nullable()->comment('Nombre del modelo a usar');
            $table->json('parameters')->nullable()->comment('Parámetros de configuración JSON');
            $table->boolean('is_active')->default(false)->comment('Estado activo/inactivo');
            $table->timestamps();
            
                // Índices para optimizar búsquedas
                $table->index(['provider_type', 'is_active']);
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_providers');
    }
};
