<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'slug' => fake()->unique()->slug(),
            'description' => fake()->sentence(),
            'level' => fake()->numberBetween(1, 100),
            'is_default' => false,
            'is_active' => true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function superAdmin(): static
    {
        return $this->state([
            'name' => 'Super Admin',
            'slug' => 'super-admin',
            'level' => 100,
            'is_default' => false,
            'is_active' => true,
            'description' => 'Super administrator with full access',
        ]);
    }

    public function admin(): static
    {
        return $this->state([
            'name' => 'Admin',
            'slug' => 'admin',
            'level' => 90,
            'is_default' => false,
            'is_active' => true,
            'description' => 'System administrator with full access',
        ]);
    }

    public function employer(): static
    {
        return $this->state([
            'name' => 'Employer',
            'slug' => 'employer',
            'level' => 50,
            'is_default' => false,
            'is_active' => true,
            'description' => 'Employer with job listing and application management access',
        ]);
    }

    public function jobSeeker(): static
    {
        return $this->state([
            'name' => 'Job Seeker',
            'slug' => 'job-seeker',
            'level' => 20,
            'is_default' => true,
            'is_active' => true,
            'description' => 'Job seeker with profile and application access',
        ]);
    }
}
