<?php

namespace Database\Factories;

use App\Models\JobCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobCategory>
 */
class JobCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Information Technology',
            'Engineering',
            'Healthcare',
            'Finance & Accounting',
            'Sales & Marketing',
            'Human Resources',
            'Administration',
            'Education & Training',
            'Customer Service',
            'Manufacturing',
            'Construction',
            'Logistics & Transportation',
            'Retail',
            'Hospitality & Tourism',
            'Media & Communications',
            'Legal',
            'Real Estate',
            'Agriculture',
            'Telecommunications',
            'Automotive',
            'Energy & Utilities',
            'Non-Profit & Social Services',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the category is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn () => [
            'is_active' => false,
        ]);
    }
}
