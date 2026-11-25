<?php

namespace Database\Factories;

use App\Models\ApplicantCv;
use App\Models\ApplicantProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicantCv>
 */
class ApplicantCvFactory extends Factory
{
    public function definition(): array
    {
        return [
            'applicant_profile_id' => ApplicantProfile::factory(),
            'cv_path' => 'cvs/' . fake()->uuid() . '.pdf',
            'original_name' => fake()->fileName(),
            'status' => 'active',
            'order_position' => 1,
            'is_primary' => false,
        ];
    }

    public function primary(): static
    {
        return $this->state(['is_primary' => true]);
    }

    public function pending(): static
    {
        return $this->state(['status' => 'pending']);
    }
}
