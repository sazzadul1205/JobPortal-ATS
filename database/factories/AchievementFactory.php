<?php

namespace Database\Factories;

use App\Models\Achievement;
use App\Models\ApplicantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Achievement>
 */
class AchievementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_profile_id' => ApplicantProfile::factory(),
            'achievement_name' => fake()->randomElement([
                'Employee of the Month',
                'Best Project Award',
                'Certified Professional',
                'Excellence in Leadership',
                'Outstanding Performer',
            ]),
            'achievement_details' => fake()->paragraph(),
        ];
    }
}
