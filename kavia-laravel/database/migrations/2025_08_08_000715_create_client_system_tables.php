<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear tabla temporal
        Schema::create('temp_client_setup', function (Blueprint $table) {
            $table->id();
            $table->timestamp('setup_at')->default(now());
        });

        // Verificar y crear client_users si no existe o recrearla
        if (!Schema::hasTable('client_users')) {
            Schema::create('client_users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->string('phone')->nullable();
                $table->string('company_name')->nullable();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                
                // Relación con nivel de cliente
                $table->foreignId('client_level_id')->constrained('client_levels')->onDelete('restrict');
                
                // Estado del usuario
                $table->boolean('active')->default(true);
                $table->timestamp('last_login_at')->nullable();
                $table->json('preferences')->nullable();
                
                // Límites específicos del usuario
                $table->integer('custom_max_hotels')->nullable();
                $table->integer('custom_max_reviews_per_month')->nullable();
                
                // Información de suscripción
                $table->date('subscription_start')->nullable();
                $table->date('subscription_end')->nullable();
                $table->enum('subscription_status', ['active', 'expired', 'canceled', 'trial'])->default('trial');
                
                // Metadatos
                $table->json('metadata')->nullable();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Crear tabla client_hotel_access si no existe
        if (!Schema::hasTable('client_hotel_access')) {
            Schema::create('client_hotel_access', function (Blueprint $table) {
                $table->id();
                $table->foreignId('client_user_id')->constrained('client_users')->onDelete('cascade');
                $table->foreignId('hotel_id')->constrained('hoteles')->onDelete('cascade');
                $table->boolean('active')->default(true);
                $table->json('permissions')->nullable();
                $table->timestamps();
                
                $table->unique(['client_user_id', 'hotel_id']);
            });
        }

        // Insertar usuarios de prueba si no existen
        if (DB::table('client_users')->count() === 0) {
            DB::table('client_users')->insert([
                [
                    'name' => 'Cliente Demo',
                    'email' => 'demo@cliente.com',
                    'phone' => '+52 998 123 4567',
                    'company_name' => 'Hotel Demo',
                    'email_verified_at' => now(),
                    'password' => Hash::make('demo123'),
                    'client_level_id' => 4, // demo
                    'active' => true,
                    'last_login_at' => null,
                    'preferences' => json_encode([
                        'language' => 'es',
                        'timezone' => 'America/Cancun',
                        'notifications' => ['email' => true, 'push' => false]
                    ]),
                    'custom_max_hotels' => null,
                    'custom_max_reviews_per_month' => null,
                    'subscription_start' => now(),
                    'subscription_end' => now()->addDays(30),
                    'subscription_status' => 'trial',
                    'metadata' => json_encode(['source' => 'demo', 'utm_campaign' => 'test']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Hotel Terracaribe',
                    'email' => 'admin@terracaribe.com',
                    'phone' => '+52 998 987 6543',
                    'company_name' => 'Hotel Terracaribe Cancun',
                    'email_verified_at' => now(),
                    'password' => Hash::make('terracaribe2025'),
                    'client_level_id' => 2, // professional
                    'active' => true,
                    'last_login_at' => null,
                    'preferences' => json_encode([
                        'language' => 'es',
                        'timezone' => 'America/Cancun',
                        'notifications' => ['email' => true, 'push' => true]
                    ]),
                    'custom_max_hotels' => null,
                    'custom_max_reviews_per_month' => null,
                    'subscription_start' => now()->subDays(30),
                    'subscription_end' => now()->addDays(335),
                    'subscription_status' => 'active',
                    'metadata' => json_encode(['source' => 'sales', 'sales_rep' => 'Juan Pérez']),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'name' => 'Grupo Hotelero Premium',
                    'email' => 'admin@grupohotels.com',
                    'phone' => '+52 998 555 0123',
                    'company_name' => 'Grupo Premium Hotels',
                    'email_verified_at' => now(),
                    'password' => Hash::make('premium2025'),
                    'client_level_id' => 3, // enterprise
                    'active' => true,
                    'last_login_at' => null,
                    'preferences' => json_encode([
                        'language' => 'es',
                        'timezone' => 'America/Mexico_City',
                        'notifications' => ['email' => true, 'push' => true]
                    ]),
                    'custom_max_hotels' => 25,
                    'custom_max_reviews_per_month' => 50000,
                    'subscription_start' => now()->subDays(90),
                    'subscription_end' => now()->addDays(275),
                    'subscription_status' => 'active',
                    'metadata' => json_encode(['source' => 'enterprise_sales', 'contract_number' => 'ENT-2024-001']),
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ]);
        }

        // Insertar accesos a hoteles si no existen
        if (DB::table('client_hotel_access')->count() === 0) {
            DB::table('client_hotel_access')->insert([
                [
                    'client_user_id' => 1, // demo@cliente.com
                    'hotel_id' => 6,
                    'active' => true,
                    'permissions' => json_encode([
                        'view_reviews' => false,
                        'export_reports' => false,
                        'view_analytics' => false
                    ]),
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'client_user_id' => 2, // admin@terracaribe.com
                    'hotel_id' => 6,
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
                    'client_user_id' => 3, // admin@grupohotels.com
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
                ]
            ]);
        }

        // Limpiar tabla temporal
        Schema::drop('temp_client_setup');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_hotel_access');
        Schema::dropIfExists('client_users');
        Schema::dropIfExists('temp_client_setup');
    }
};