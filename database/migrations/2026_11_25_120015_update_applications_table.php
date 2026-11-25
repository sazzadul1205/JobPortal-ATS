<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('education_level')->nullable();
            $table->unsignedInteger('years_of_experience')->nullable();
            $table->enum('ats_calculation_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->unsignedTinyInteger('ats_attempt_count')->default(0);
            $table->timestamp('ats_last_attempted_at')->nullable();
            $table->text('facebook_link')->nullable();
            $table->text('linkedin_link')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'years_of_experience', 'ats_calculation_status', 'ats_attempt_count', 'ats_last_attempted_at', 'facebook_link', 'linkedin_link']);
        });
    }
};
