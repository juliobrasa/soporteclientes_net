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
        Schema::create('client_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('display_name', 200);
            $table->text('description')->nullable();
            $table->json('features')->nullable(); // JSON con las funcionalidades permitidas
            $table->json('modules')->nullable();  // JSON con los módulos permitidos
            $table->integer('max_hotels')->default(1); // Máximo número de hoteles
            $table->integer('max_reviews_per_month')->default(1000); // Límite de reseñas por mes
            $table->decimal('monthly_price', 10, 2)->default(0.00);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->unique('name');
        });

        // Insertar niveles por defecto
        DB::table('client_levels')->insert([
            [
                'name' => 'basic',
                'display_name' => 'Plan Básico',
                'description' => 'Acceso básico al dashboard con funcionalidades limitadas',
                'features' => json_encode([
                    'dashboard_view' => true,
                    'reviews_view' => true,
                    'basic_stats' => true,
                    'export_reports' => false,
                    'ai_responses' => false,
                    'advanced_analytics' => false,
                    'competitor_analysis' => false,
                    'custom_alerts' => false
                ]),
                'modules' => json_encode([
                    'resumen' => true,
                    'otas' => false,
                    'reseñas' => true
                ]),
                'max_hotels' => 1,
                'max_reviews_per_month' => 500,
                'monthly_price' => 29.99,
                'active' => true,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'professional',
                'display_name' => 'Plan Profesional',
                'description' => 'Acceso completo con funcionalidades avanzadas',
                'features' => json_encode([
                    'dashboard_view' => true,
                    'reviews_view' => true,
                    'basic_stats' => true,
                    'export_reports' => true,
                    'ai_responses' => true,
                    'advanced_analytics' => true,
                    'competitor_analysis' => false,
                    'custom_alerts' => true
                ]),
                'modules' => json_encode([
                    'resumen' => true,
                    'otas' => true,
                    'reseñas' => true
                ]),
                'max_hotels' => 3,
                'max_reviews_per_month' => 2000,
                'monthly_price' => 79.99,
                'active' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'enterprise',
                'display_name' => 'Plan Empresarial',
                'description' => 'Acceso completo sin restricciones',
                'features' => json_encode([
                    'dashboard_view' => true,
                    'reviews_view' => true,
                    'basic_stats' => true,
                    'export_reports' => true,
                    'ai_responses' => true,
                    'advanced_analytics' => true,
                    'competitor_analysis' => true,
                    'custom_alerts' => true
                ]),
                'modules' => json_encode([
                    'resumen' => true,
                    'otas' => true,
                    'reseñas' => true
                ]),
                'max_hotels' => 10,
                'max_reviews_per_month' => 10000,
                'monthly_price' => 199.99,
                'active' => true,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'demo',
                'display_name' => 'Demo/Prueba',
                'description' => 'Acceso de demostración con funcionalidades limitadas',
                'features' => json_encode([
                    'dashboard_view' => true,
                    'reviews_view' => false,
                    'basic_stats' => true,
                    'export_reports' => false,
                    'ai_responses' => false,
                    'advanced_analytics' => false,
                    'competitor_analysis' => false,
                    'custom_alerts' => false
                ]),
                'modules' => json_encode([
                    'resumen' => true,
                    'otas' => false,
                    'reseñas' => false
                ]),
                'max_hotels' => 1,
                'max_reviews_per_month' => 100,
                'monthly_price' => 0.00,
                'active' => true,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_levels');
    }
};