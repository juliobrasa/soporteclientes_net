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
        Schema::create('external_apis', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre descriptivo del API
            $table->string('provider_type'); // booking, tripadvisor, expedia, etc.
            $table->string('base_url')->nullable(); // URL base del API
            $table->json('credentials'); // Credenciales encriptadas (API key, username, password, etc.)
            $table->json('configuration')->nullable(); // Configuración adicional específica del proveedor
            $table->boolean('is_active')->default(true); // Estado activo/inactivo
            $table->integer('rate_limit')->nullable(); // Límite de requests por minuto
            $table->string('version')->nullable(); // Versión del API
            $table->text('description')->nullable(); // Descripción del API
            $table->json('endpoints')->nullable(); // Endpoints disponibles
            $table->timestamp('last_tested_at')->nullable(); // Última vez que se probó la conexión
            $table->json('last_test_result')->nullable(); // Resultado del último test
            $table->integer('usage_count')->default(0); // Contador de uso
            $table->timestamp('last_used_at')->nullable(); // Última vez usado
            $table->timestamps();
            
            // Índices
            $table->index('provider_type');
            $table->index('is_active');
            $table->index('last_used_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('external_apis');
    }
};
