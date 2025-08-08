<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            $table->json('preferences')->nullable(); // JSON con preferencias del usuario
            
            // Límites específicos del usuario (pueden sobrescribir los del nivel)
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

        // Los datos se insertan en la migración fix_client_users_data
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_users');
    }
};