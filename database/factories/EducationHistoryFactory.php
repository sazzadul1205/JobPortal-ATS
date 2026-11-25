<?php

namespace Database\Factories;

use App\Models\ApplicantProfile;
use App\Models\EducationHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EducationHistory>
 */
class EducationHistoryFactory extends Factory
{
    public function definition(): array
    {
        $degree = fake()->randomElement([
            'Bachelor of Science in Computer Science',
            'Bachelor of Arts in Business Administration',
            'Master of Business Administration',
            'Master of Science in Software Engineering',
            'Diploma in Information Technology',
            'High School Diploma',
        ]);

        return [
            'applicant_profile_id' => ApplicantProfile::factory(),
            'institution_name' => fake()->company(),
            'degree' => $degree,
            'passing_year' => fake()->numberBetween(2015, 2024),
        ];
    }
}
