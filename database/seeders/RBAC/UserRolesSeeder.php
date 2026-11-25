<?php

namespace Database\Seeders\RBAC;

use App\Models\Role;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;

class UserRolesSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $adminRole = Role::where('slug', 'admin')->first();
        $employerRole = Role::where('slug', 'employer')->first();
        $jobSeekerRole = Role::where('slug', 'job-seeker')->first();

        if (! $superAdminRole || ! $adminRole || ! $employerRole || ! $jobSeekerRole) {
            $this->command->warn('Roles not found. Run RolesSeeder first.');

            return;
        }

        // Assign super-admin to the super admin user
        $superAdmin = User::where('email', 'superadmin@jobportal.com')->first();
        if ($superAdmin) {
            UserRole::updateOrCreate(
                ['user_id' => $superAdmin->id, 'role_id' => $superAdminRole->id],
                ['assigned_at' => now(), 'is_active' => true]
            );
        }

        // Assign admin to the admin user
        $admin = User::where('email', 'admin@jobportal.com')->first();
        if ($admin) {
            UserRole::updateOrCreate(
                ['user_id' => $admin->id, 'role_id' => $adminRole->id],
                ['assigned_at' => now(), 'is_active' => true]
            );
        }

        // Assign employer role to all employer users
        $employers = User::whereNotNull('id')->get();
        // We'll assign roles based on user count - first user after admin is employer
        // Actually, let's assign based on what we know from DatabaseSeeder

        // Assign job-seeker as default to users without roles
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();
        foreach ($usersWithoutRoles as $user) {
            UserRole::updateOrCreate(
                ['user_id' => $user->id, 'role_id' => $jobSeekerRole->id],
                ['assigned_at' => now(), 'is_active' => true]
            );
        }
    }
}
