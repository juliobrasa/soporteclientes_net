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
        Schema::create('client_hotel_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_user_id')->constrained('client_users')->onDelete('cascade');
            $table->foreignId('hotel_id')->constrained('hoteles')->onDelete('cascade');
            $table->boolean('active')->default(true);
            $table->json('permissions')->nullable(); // Permisos específicos para este hotel
            $table->timestamps();
            
            $table->unique(['client_user_id', 'hotel_id']);
        });

        // Asignar hoteles a usuarios de prueba
        DB::table('client_hotel_access')->insert([
            [
                'client_user_id' => 1, // demo@cliente.com
                'hotel_id' => 6, // Hotel Terracaribe (asumiendo que existe)
                'active' => true,
                'permissions' => json_encode([
                    'view_reviews' => false, // Demo tiene limitaciones
                    'export_reports' => false,
                    'view_analytics' => false
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'client_user_id' => 2, // admin@terracaribe.com
                'hotel_id' => 6, // Hotel Terracaribe
                'active' => true,
                'permissions' => json_encode([
                    'view_reviews' => true,
                    'export_reports' => true,
                    'view_analytics' => true,
                    'manage_responses' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'client_user_id' => 3, // admin@grupohotels.com (Enterprise)
                'hotel_id' => 6,
                'active' => true,
                'permissions' => json_encode([
                    'view_reviews' => true,
                    'export_reports' => true,
                    'view_analytics' => true,
                    'manage_responses' => true,
                    'competitor_analysis' => true
                ]),
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'client_user_id' => 3, // admin@grupohotels.com - Hotel adicional
                'hotel_id' => 7, // Asumiendo que existe otro hotel
                'active' => true,
                'permissions' => json_encode([
                    'view_reviews' => true,
                    'export_reports' => true,
                    'view_analytics' => true,
                    'manage_responses' => true,
                    'competitor_analysis' => true
                ]),
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
        Schema::dropIfExists('client_hotel_access');
    }
};