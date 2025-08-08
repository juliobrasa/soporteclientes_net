<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar datos anteriores si existen
        DB::table('client_users')->truncate();
        
        // Insertar usuarios de prueba correctamente
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
                    'notifications' => [
                        'email' => true,
                        'push' => false
                    ]
                ]),
                'custom_max_hotels' => null,
                'custom_max_reviews_per_month' => null,
                'subscription_start' => now(),
                'subscription_end' => now()->addDays(30),
                'subscription_status' => 'trial',
                'metadata' => json_encode([
                    'source' => 'demo',
                    'utm_campaign' => 'test'
                ]),
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
                    'notifications' => [
                        'email' => true,
                        'push' => true
                    ]
                ]),
                'custom_max_hotels' => null,
                'custom_max_reviews_per_month' => null,
                'subscription_start' => now()->subDays(30),
                'subscription_end' => now()->addDays(335),
                'subscription_status' => 'active',
                'metadata' => json_encode([
                    'source' => 'sales',
                    'sales_rep' => 'Juan Pérez'
                ]),
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
                    'notifications' => [
                        'email' => true,
                        'push' => true
                    ]
                ]),
                'custom_max_hotels' => 25, // Límite personalizado más alto
                'custom_max_reviews_per_month' => 50000,
                'subscription_start' => now()->subDays(90),
                'subscription_end' => now()->addDays(275),
                'subscription_status' => 'active',
                'metadata' => json_encode([
                    'source' => 'enterprise_sales',
                    'contract_number' => 'ENT-2024-001'
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
        DB::table('client_users')->truncate();
    }
};