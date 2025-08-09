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
        if (!Schema::hasTable('prompts')) {
            Schema::create('prompts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('Nombre descriptivo del prompt');
            $table->enum('category', ['sentiment', 'extraction', 'translation', 'classification', 'summary', 'custom'])
                  ->comment('Categoría del prompt');
            $table->string('language', 10)->default('es')->comment('Código de idioma');
            $table->text('description')->nullable()->comment('Descripción del propósito');
            $table->longText('content')->comment('Contenido del prompt con variables');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft')->comment('Estado del prompt');
            $table->string('version', 20)->default('1.0')->comment('Versión del prompt');
            $table->json('tags')->nullable()->comment('Etiquetas para búsqueda');
            $table->json('custom_variables')->nullable()->comment('Variables personalizadas');
            $table->json('config')->nullable()->comment('Configuración de IA');
            $table->integer('usage_count')->default(0)->comment('Contador de usos');
            $table->timestamp('last_used')->nullable()->comment('Última vez usado');
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['status', 'category']);
            $table->index(['category', 'language']);
            $table->index('status');
            $table->index('usage_count');
            
                // Índice full-text para búsqueda
                $table->fullText(['name', 'description', 'content'], 'prompts_fulltext_search');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prompts');
    }
};
