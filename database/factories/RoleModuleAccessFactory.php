<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\RoleModuleAccess;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoleModuleAccess>
 */
class RoleModuleAccessFactory extends Factory
{
    public function definition(): array
    {
        return [
            'role_id' => Role::factory(),
            'module' => fake()->randomElement([
                'dashboard', 'job_listings', 'applications', 'categories', 'locations',
                'statistics', 'applicant_profiles', 'profiles', 'public_jobs', 'notifications',
                'employer_profile',
            ]),
            'access_level' => fake()->randomElement([
                RoleModuleAccess::ACCESS_NO_ACCESS,
                RoleModuleAccess::ACCESS_READ,
                RoleModuleAccess::ACCESS_WRITE,
                RoleModuleAccess::ACCESS_MANAGE,
            ]),
        ];
    }
}
