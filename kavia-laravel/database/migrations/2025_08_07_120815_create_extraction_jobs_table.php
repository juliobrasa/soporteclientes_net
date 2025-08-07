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
        if (!Schema::hasTable('extraction_jobs')) {
            Schema::create('extraction_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled'])->default('pending');
                $table->enum('mode', ['active', 'all', 'selected'])->default('active');
                $table->enum('priority', ['normal', 'high', 'critical'])->default('normal');
                
                $table->unsignedBigInteger('api_provider_id')->nullable();
                $table->string('api_provider_name')->nullable();
                $table->string('api_provider_type', 50)->nullable();
                
                $table->integer('hotel_count')->default(0);
                $table->integer('max_reviews_per_hotel')->default(200);
                $table->json('selected_hotels')->nullable();
                
                $table->decimal('progress', 5, 2)->default(0);
                $table->integer('completed_hotels')->default(0);
                $table->integer('reviews_extracted')->default(0);
                $table->integer('estimated_reviews')->default(0);
                $table->decimal('total_cost', 10, 2)->default(0);
                
                $table->json('options')->nullable();
                $table->enum('execution_mode', ['immediate', 'schedule', 'draft'])->default('immediate');
                $table->timestamp('scheduled_datetime')->nullable();
                
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('estimated_completion')->nullable();
                $table->integer('running_time')->default(0);
                
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                // Foreign key
                $table->foreign('api_provider_id')->references('id')->on('external_apis')->onDelete('set null');
                
                // Indexes
                $table->index('status');
                $table->index('created_at');
                $table->index('api_provider_id');
            });
        }
        
        if (!Schema::hasTable('extraction_runs')) {
            Schema::create('extraction_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id');
                $table->unsignedInteger('hotel_id');
                $table->string('hotel_name')->nullable();
                $table->enum('status', ['pending', 'running', 'completed', 'failed', 'skipped'])->default('pending');
                
                $table->decimal('progress', 5, 2)->default(0);
                $table->integer('reviews_extracted')->default(0);
                $table->integer('reviews_target')->default(0);
                
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->integer('duration')->default(0);
                
                $table->text('error_message')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('job_id')->references('id')->on('extraction_jobs')->onDelete('cascade');
                $table->foreign('hotel_id')->references('id')->on('hoteles')->onDelete('cascade');
                
                // Indexes
                $table->index(['job_id', 'status']);
                $table->index('hotel_id');
            });
        }
        
        if (!Schema::hasTable('extraction_logs')) {
            Schema::create('extraction_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('job_id')->nullable();
                $table->unsignedBigInteger('run_id')->nullable();
                $table->enum('level', ['info', 'warning', 'error'])->default('info');
                $table->text('message');
                $table->json('data')->nullable();
                $table->timestamps();
                
                // Foreign keys
                $table->foreign('job_id')->references('id')->on('extraction_jobs')->onDelete('cascade');
                $table->foreign('run_id')->references('id')->on('extraction_runs')->onDelete('cascade');
                
                // Indexes
                $table->index(['job_id', 'level']);
                $table->index('run_id');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('extraction_logs');
        Schema::dropIfExists('extraction_runs');
        Schema::dropIfExists('extraction_jobs');
    }
};
