<?php

namespace Database\Factories;

use App\Models\ApplicantProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicantProfile>
 */
class ApplicantProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->optional(0.7)->dateTimeBetween('-50 years', '-18 years')?->format('Y-m-d'),
            'gender' => fake()->optional(0.7)->randomElement(['male', 'female', 'other']),
            'blood_type' => fake()->optional(0.5)->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'phone' => fake()->optional(0.8)->phoneNumber(),
            'address' => fake()->optional(0.7)->address(),
            'photo_path' => fake()->optional(0.3)->imageUrl(400, 400, 'people'),
            'social_links' => fake()->optional(0.4)->json(),
            'experience_years' => fake()->optional(0.7)->numberBetween(0, 20),
            'current_job_title' => fake()->optional(0.7)->jobTitle(),
        ];
    }
}
