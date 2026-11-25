<?php

namespace Database\Factories;

use App\Models\ApplicantProfile;
use App\Models\JobHistory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobHistory>
 */
class JobHistoryFactory extends Factory
{
    public function definition(): array
    {
        $startYear = fake()->numberBetween(2015, 2023);
        $isCurrent = fake()->boolean(20);

        return [
            'applicant_profile_id' => ApplicantProfile::factory(),
            'company_name' => fake()->company(),
            'position' => fake()->jobTitle(),
            'starting_year' => $startYear,
            'ending_year' => $isCurrent ? null : fake()->numberBetween($startYear, 2024),
            'is_current' => $isCurrent,
        ];
    }

    public function current(): static
    {
        return $this->state(['is_current' => true, 'ending_year' => null]);
    }
}
