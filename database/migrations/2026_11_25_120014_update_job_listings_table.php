<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropForeign(['location_id']);
            $table->dropIndex('location_id');
            $table->dropColumn(['location_id', 'salary', 'schedule_start_date', 'show_linkedin', 'show_facebook']);
            $table->string('job_type')->change();
            $table->decimal('salary_min', 10, 2)->nullable()->after('job_type');
            $table->decimal('salary_max', 10, 2)->nullable()->after('salary_min');
            $table->boolean('is_salary_negotiable')->default(false)->after('salary_max');
            $table->boolean('as_per_companies_policy')->default(false)->after('is_salary_negotiable');
            $table->string('experience_level')->change();
            $table->string('education_requirement')->nullable()->change();
            $table->string('education_details')->nullable()->after('education_requirement');
            $table->json('benefits')->nullable()->change();
            $table->json('skills')->nullable()->change();
            $table->json('responsibilities')->nullable()->change();
            $table->json('keywords')->nullable()->change();
            $table->date('application_deadline')->change();
            $table->date('publish_at')->nullable()->after('application_deadline');
            $table->unsignedInteger('views_count')->default(0)->after('publish_at');
            $table->boolean('is_active')->default(true)->change();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->change();
            $table->boolean('required_facebook_link')->default(false)->after('user_id');
            $table->boolean('required_linkedin_link')->default(false)->after('required_facebook_link');
        });

        Schema::create('job_listing_location', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['job_listing_id', 'location_id']);
            $table->index('job_listing_id');
            $table->index('location_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_listing_location');

        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max', 'is_salary_negotiable', 'as_per_companies_policy', 'education_details', 'publish_at', 'views_count', 'required_facebook_link', 'required_linkedin_link']);
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('salary')->nullable();
            $table->date('schedule_start_date')->nullable();
            $table->boolean('show_linkedin')->default(false);
            $table->boolean('show_facebook')->default(false);
        });
    }
};
