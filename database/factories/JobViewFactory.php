<?php

namespace Database\Factories;

use App\Models\JobListing;
use App\Models\JobView;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobView>
 */
class JobViewFactory extends Factory
{
    public function definition(): array
    {
        return [
            'job_listing_id' => JobListing::factory(),
            'user_id' => fake()->boolean(70) ? User::factory() : null,
            'ip_address' => fake()->ipv4(),
        ];
    }
}
