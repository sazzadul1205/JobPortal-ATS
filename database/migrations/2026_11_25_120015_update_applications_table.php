<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['resume_path', 'cover_letter']);
            $table->string('phone')->nullable()->change();
            $table->string('education_level')->nullable()->after('phone');
            $table->unsignedInteger('years_of_experience')->nullable()->after('education_level');
            $table->string('resume_path')->nullable()->after('years_of_experience');
            $table->decimal('expected_salary', 12, 2)->nullable()->change();
            $table->json('ats_score')->nullable()->change();
            $table->json('matched_keywords')->nullable()->change();
            $table->json('missing_keywords')->nullable()->change();
            $table->enum('ats_calculation_status', ['pending', 'processing', 'completed', 'failed'])->default('pending')->after('missing_keywords');
            $table->unsignedTinyInteger('ats_attempt_count')->default(0)->after('ats_calculation_status');
            $table->timestamp('ats_last_attempted_at')->nullable()->after('ats_attempt_count');
            $table->enum('status', ['pending', 'shortlisted', 'rejected', 'hired'])->default('pending')->change();
            $table->text('employer_notes')->nullable()->change();
            $table->text('facebook_link')->nullable()->after('employer_notes');
            $table->text('linkedin_link')->nullable()->after('facebook_link');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn(['education_level', 'years_of_experience', 'ats_calculation_status', 'ats_attempt_count', 'ats_last_attempted_at', 'facebook_link', 'linkedin_link']);
            $table->string('phone')->nullable()->change();
            $table->string('resume_path')->change();
            $table->decimal('expected_salary', 12, 2)->nullable()->change();
            $table->json('ats_score')->nullable()->change();
            $table->json('matched_keywords')->nullable()->change();
            $table->json('missing_keywords')->nullable()->change();
            $table->enum('status', ['pending', 'reviewed', 'shortlisted', 'rejected', 'hired'])->default('pending')->change();
            $table->text('employer_notes')->nullable()->change();
        });
    }
};
