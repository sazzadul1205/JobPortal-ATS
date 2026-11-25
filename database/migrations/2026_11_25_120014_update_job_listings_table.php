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
            $table->dropIndex('job_listings_location_id_index');
            $table->dropIndex('job_listings_schedule_start_date_index');
            $table->dropColumn(['location_id', 'salary', 'schedule_start_date', 'show_linkedin', 'show_facebook']);
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->boolean('is_salary_negotiable')->default(false);
            $table->boolean('as_per_companies_policy')->default(false);
            $table->string('education_details')->nullable();
            $table->date('publish_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('required_facebook_link')->default(false);
            $table->boolean('required_linkedin_link')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('job_listings', function (Blueprint $table) {
            $table->dropColumn(['salary_min', 'salary_max', 'is_salary_negotiable', 'as_per_companies_policy', 'education_details', 'publish_at', 'views_count', 'required_facebook_link', 'required_linkedin_link']);
            $table->foreignId('location_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('salary')->nullable();
            $table->date('schedule_start_date')->nullable();
            $table->boolean('show_linkedin')->default(false);
            $table->boolean('show_facebook')->default(false);
        });
    }
};
