<?php

namespace Database\Factories;

use App\Models\Application;
use App\Models\StatusTimeline;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StatusTimeline>
 */
class StatusTimelineFactory extends Factory
{
    public function definition(): array
    {
        return [
            'application_id' => Application::factory(),
            'status' => fake()->randomElement(['pending', 'shortlisted', 'rejected', 'hired']),
            'notes' => fake()->optional(0.7)->paragraph(),
        ];
    }
}
