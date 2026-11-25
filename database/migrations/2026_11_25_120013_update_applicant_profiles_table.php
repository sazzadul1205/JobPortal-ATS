<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn(['email', 'cv_path']);
            $table->string('gender')->nullable()->after('birth_date');
            $table->string('blood_type')->nullable()->after('gender');
            $table->string('phone', 20)->nullable()->change();
            $table->text('address')->nullable()->after('phone');
            $table->string('photo_path')->nullable()->change();
            $table->json('social_links')->nullable()->after('address');
            $table->integer('experience_years')->nullable()->after('social_links');
            $table->string('current_job_title')->nullable()->after('experience_years');
        });
    }

    public function down(): void
    {
        Schema::table('applicant_profiles', function (Blueprint $table) {
            $table->dropColumn(['gender', 'blood_type', 'address', 'social_links', 'experience_years', 'current_job_title']);
            $table->string('email')->after('birth_date');
            $table->string('cv_path')->nullable();
            $table->string('phone')->nullable()->change();
            $table->string('photo_path')->nullable()->change();
        });
    }
};
