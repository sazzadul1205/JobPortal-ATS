<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        $module = fake()->randomElement([
            'dashboard', 'job_listings', 'applications', 'categories', 'locations',
            'statistics', 'applicant_profiles', 'profiles', 'public_jobs', 'notifications',
            'employer_profile', 'users', 'roles',
        ]);

        $action = fake()->randomElement(['view', 'create', 'store', 'show', 'edit', 'update', 'destroy', 'toggle_active', 'restore', 'force_delete', 'bulk_activate', 'bulk_deactivate', 'bulk_delete', 'bulk_status_update', 'bulk_download_resumes', 'bulk_email_send', 'email_send', 'download_resume', 'recalculate_ats', 'export', 'export_single', 'stats_view', 'ats', 'jobs', 'get_active']);

        return [
            'name' => "{$module}.{$action}",
            'slug' => "{$module}.{$action}",
            'module' => $module,
            'action' => $action,
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
