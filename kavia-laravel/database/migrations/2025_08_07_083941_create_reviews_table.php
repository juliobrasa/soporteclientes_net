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
        // Crear tabla reviews unificada compatible con legacy y nuevo sistema
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->id();
                $table->string('unique_id')->unique();
                $table->unsignedInteger('hotel_id')->index();
                $table->string('hotel_name')->nullable();
                $table->string('hotel_destination')->nullable();
                
                // NOMBRES UNIFICADOS - usar siempre estos
                $table->string('user_name')->nullable();           // legacy usa esto
                $table->text('review_text')->nullable();           // apify-data-processor usa esto
                $table->text('liked_text')->nullable();            // legacy usa esto (texto positivo)
                $table->text('disliked_text')->nullable();         // legacy usa esto (texto negativo)
                $table->string('source_platform', 50)->nullable()->index(); // UNIFICADO: siempre usar este
                $table->text('property_response')->nullable();     // UNIFICADO: siempre usar este
                
                // CAMPOS COMUNES
                $table->date('review_date')->nullable()->index();
                $table->decimal('rating', 3, 1)->nullable()->index(); // escala 1-5 normalizada
                $table->string('review_title', 500)->nullable();
                $table->string('platform_review_id')->nullable();
                $table->string('extraction_run_id')->nullable();
                $table->string('extraction_status', 50)->default('completed');
                $table->timestamp('scraped_at')->useCurrent();
                
                // CAMPOS LEGACY ADICIONALES
                $table->integer('helpful_votes')->default(0);
                $table->string('review_language', 10)->default('auto');
                $table->string('traveler_type_spanish', 100)->nullable();
                $table->boolean('was_translated')->default(false);
                $table->integer('number_of_nights')->nullable();
                
                // CAMPOS NUEVOS APIFY
                $table->string('reviewer_location')->nullable();
                $table->string('stay_date', 50)->nullable();
                $table->string('room_type')->nullable();
                $table->decimal('original_rating', 3, 1)->nullable(); // rating original antes de normalizar
                
                $table->timestamps();
                
                // ÍNDICES PARA RENDIMIENTO
                $table->index(['hotel_id', 'source_platform']);
                $table->index(['hotel_id', 'review_date']);
                $table->index(['scraped_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
