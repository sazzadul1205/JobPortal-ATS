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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('description');
            $table->longText('requirements');
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('salary')->nullable();
            $table->string('job_type');
            $table->foreignId('category_id')->constrained('job_categories')->cascadeOnDelete();
            $table->string('experience_level');
            $table->string('education_requirement')->nullable();
            $table->json('benefits')->nullable();
            $table->json('skills')->nullable();
            $table->json('responsibilities')->nullable();
            $table->json('keywords')->nullable();
            $table->date('application_deadline');
            $table->date('schedule_start_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('show_linkedin')->default(false);
            $table->boolean('show_facebook')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'application_deadline']);
            $table->index('category_id');
            $table->index('location_id');
            $table->index('job_type');
            $table->index('slug');
            $table->index('schedule_start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};