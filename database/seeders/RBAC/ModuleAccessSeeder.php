<?php

namespace Database\Seeders\RBAC;

use App\Models\Role;
use App\Models\RoleModuleAccess;
use Illuminate\Database\Seeder;

class ModuleAccessSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::where('slug', 'super-admin')->first();
        $admin = Role::where('slug', 'admin')->first();
        $employer = Role::where('slug', 'employer')->first();
        $jobSeeker = Role::where('slug', 'job-seeker')->first();

        if (! $superAdmin || ! $admin || ! $employer || ! $jobSeeker) {
            $this->command->warn('Roles not found. Run RolesSeeder first.');
            return;
        }

        $modules = [
            'dashboard', 'job_listings', 'applications', 'categories', 'locations',
            'statistics', 'applicant_profiles', 'profiles', 'public_jobs',
            'notifications', 'employer_profile', 'users', 'roles',
        ];

        // Super Admin and Admin: manage on all modules
        foreach ([$superAdmin, $admin] as $role) {
            foreach ($modules as $module) {
                RoleModuleAccess::updateOrCreate(
                    ['role_id' => $role->id, 'module' => $module],
                    ['access_level' => RoleModuleAccess::ACCESS_MANAGE]
                );
            }
        }

        // Employer module access
        $employerWriteModules = [
            'dashboard', 'job_listings', 'applications', 'employer_profile', 'notifications',
        ];
        $employerReadModules = [
            'categories', 'locations', 'statistics', 'applicant_profiles', 'profiles', 'public_jobs',
        ];

        foreach ($modules as $module) {
            $access = RoleModuleAccess::ACCESS_NO_ACCESS;
            if (in_array($module, $employerWriteModules)) {
                $access = RoleModuleAccess::ACCESS_WRITE;
            } elseif (in_array($module, $employerReadModules)) {
                $access = RoleModuleAccess::ACCESS_READ;
            }

            RoleModuleAccess::updateOrCreate(
                ['role_id' => $employer->id, 'module' => $module],
                ['access_level' => $access]
            );
        }

        // Job Seeker: no_access on all modules
        foreach ($modules as $module) {
            RoleModuleAccess::updateOrCreate(
                ['role_id' => $jobSeeker->id, 'module' => $module],
                ['access_level' => RoleModuleAccess::ACCESS_NO_ACCESS]
            );
        }
    }
}
