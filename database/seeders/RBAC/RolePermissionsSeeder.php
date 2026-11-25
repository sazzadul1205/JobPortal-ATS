<?php

namespace Database\Seeders\RBAC;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionsSeeder extends Seeder
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

        $allPermissions = Permission::pluck('id')->toArray();

        $employerPermissions = [
            'dashboard.view', 'dashboard.stats.view', 'dashboard.employer',
            'job_listings.view', 'job_listings.create', 'job_listings.store',
            'job_listings.edit', 'job_listings.update', 'job_listings.show',
            'job_listings.destroy', 'job_listings.toggle_active',
            'job_listings.applications', 'job_listings.bulk_activate',
            'job_listings.bulk_deactivate', 'job_listings.bulk_delete',
            'job_listings.statistics',
            'applications.view', 'applications.view.for_own_jobs',
            'applications.show', 'applications.status.update',
            'applications.bulk_status.update', 'applications.download_resume',
            'applications.bulk_download_resumes', 'applications.email.send',
            'applications.bulk_email.send', 'applications.recalculate_ats',
            'applications.export', 'applications.export_single',
            'categories.view', 'categories.get_active',
            'locations.view', 'locations.get_active',
            'profiles.view', 'profiles.edit', 'profiles.update',
            'profiles.update_password', 'notifications.view',
            'statistics.view', 'statistics.ats', 'statistics.jobs',
            'statistics.dashboard', 'applicant_profiles.view',
            'applicant_profiles.view.any', 'applicant_profiles.show',
            'public_jobs.view',
        ];

        // Super Admin: all permissions
        $superAdmin->permissions()->sync(
            array_combine($allPermissions, array_fill(0, count($allPermissions), ['granted' => true]))
        );

        // Admin: all permissions
        $admin->permissions()->sync(
            array_combine($allPermissions, array_fill(0, count($allPermissions), ['granted' => true]))
        );

        // Employer: specific permissions
        $employerPermissionIds = Permission::whereIn('slug', $employerPermissions)->pluck('id')->toArray();
        $employer->permissions()->sync(
            array_combine($employerPermissionIds, array_fill(0, count($employerPermissionIds), ['granted' => true]))
        );

        // Job Seeker: no permissions
        $jobSeeker->permissions()->sync([]);
    }
}
