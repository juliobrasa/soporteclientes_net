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
        if (Schema::hasTable('extraction_jobs')) {
            Schema::table('extraction_jobs', function (Blueprint $table) {
                // Agregar campos requeridos por legacy
                if (!Schema::hasColumn('extraction_jobs', 'hotel_id')) {
                    $table->unsignedInteger('hotel_id')->nullable()->index()->after('id');
                }
                if (!Schema::hasColumn('extraction_jobs', 'progress')) {
                    $table->integer('progress')->default(0)->after('status');
                }
                if (!Schema::hasColumn('extraction_jobs', 'reviews_extracted')) {
                    $table->integer('reviews_extracted')->default(0)->after('progress');
                }
                if (!Schema::hasColumn('extraction_jobs', 'completed_at')) {
                    $table->timestamp('completed_at')->nullable()->after('updated_at');
                }
                if (!Schema::hasColumn('extraction_jobs', 'platforms')) {
                    $table->json('platforms')->nullable()->after('hotel_id');
                }
                
                // Hacer name nullable para compatibilidad con legacy
                if (Schema::hasColumn('extraction_jobs', 'name')) {
                    $table->string('name')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('extraction_jobs')) {
            Schema::table('extraction_jobs', function (Blueprint $table) {
                // No eliminar columnas para no romper legacy
                // Opcional: descomentar si quieres rollback completo
                // $table->dropColumn(['hotel_id', 'progress', 'reviews_extracted', 'completed_at', 'platforms']);
            });
        }
    }
};