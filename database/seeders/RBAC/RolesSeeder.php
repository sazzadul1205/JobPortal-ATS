<?php

namespace Database\Seeders\RBAC;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Super administrator with full access to all modules and permissions.',
                'level' => 100,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Admin',
                'slug' => 'admin',
                'description' => 'System administrator with full access to all modules.',
                'level' => 90,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Employer',
                'slug' => 'employer',
                'description' => 'Employer with job listing and application management access.',
                'level' => 50,
                'is_default' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Job Seeker',
                'slug' => 'job-seeker',
                'description' => 'Job seeker with profile and application access.',
                'level' => 20,
                'is_default' => true,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
