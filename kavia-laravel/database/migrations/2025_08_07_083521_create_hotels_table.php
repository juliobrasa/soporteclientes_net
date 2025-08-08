<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Esta migración verifica la existencia de la tabla 'hoteles' existente
     * y la prepara para trabajar con Laravel sin modificar datos.
     */
    public function up(): void
    {
        // Verificar que la tabla hoteles existe (del sistema actual)
        if (!Schema::hasTable('hoteles')) {
            // Si no existe, crearla con la estructura esperada
            Schema::create('hoteles', function (Blueprint $table) {
                $table->id();
                $table->string('nombre_hotel');
                $table->string('hoja_destino')->nullable();
                $table->string('url_booking')->nullable();
                $table->integer('max_reviews')->default(200);
                $table->boolean('activo')->default(true);
                $table->timestamps();
                
                // Índices para optimización
                $table->index('activo');
                $table->index(['activo', 'created_at']);
                $table->index('nombre_hotel');
            });
            
            echo "✅ Tabla hoteles creada.\n";
        } else {
            echo "✅ Tabla hoteles ya existe - usando estructura actual.\n";
            
            // Opcionalmente, agregar columnas que podrían faltar
            Schema::table('hoteles', function (Blueprint $table) {
                // Solo agregar si no existen
                if (!Schema::hasColumn('hoteles', 'created_at')) {
                    $table->timestamps();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // NO eliminamos la tabla porque tiene datos del sistema actual
        // Schema::dropIfExists('hoteles');
        
        echo "⚠️  Migración revertida - tabla hoteles conservada con datos existentes.\n";
    }
};
