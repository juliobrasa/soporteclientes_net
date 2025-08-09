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
        if (!Schema::hasTable('apify_extraction_runs')) {
            Schema::create('apify_extraction_runs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('job_id')->nullable()->index(); // vínculo con extraction_jobs
                $table->unsignedInteger('hotel_id')->index();
                $table->string('apify_run_id')->unique();
                $table->enum('status', ['pending','running','succeeded','failed','timeout','cancelled'])->default('pending')->index();
                $table->json('platforms_requested')->nullable();
                $table->integer('max_reviews_per_platform')->nullable();
                $table->decimal('cost_estimate', 10, 4)->nullable();
                $table->json('apify_response')->nullable();
                $table->timestamp('started_at')->nullable()->index(); // IMPORTANTE: para filtros por fecha
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apify_extraction_runs');
    }
};