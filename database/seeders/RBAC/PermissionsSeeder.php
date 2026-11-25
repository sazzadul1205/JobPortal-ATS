<?php

namespace Database\Seeders\RBAC;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = $this->getPermissions();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug']],
                $permission
            );
        }
    }

    private function getPermissions(): array
    {
        $permissions = [];

        $modules = [
            'dashboard' => ['view', 'stats.view', 'employer'],
            'job_listings' => [
                'view', 'create', 'store', 'edit', 'update', 'show', 'destroy',
                'toggle_active', 'applications', 'bulk_activate', 'bulk_deactivate',
                'bulk_delete', 'restore', 'force_delete', 'statistics',
            ],
            'applications' => [
                'view', 'view.for_own_jobs', 'show', 'status.update', 'bulk_status.update',
                'destroy', 'bulk_delete', 'download_resume', 'bulk_download_resumes',
                'email.send', 'bulk_email.send', 'recalculate_ats', 'export', 'export_single',
            ],
            'categories' => ['view', 'get_active', 'create', 'edit', 'update', 'destroy', 'toggle_active', 'restore', 'force_delete'],
            'locations' => ['view', 'get_active', 'create', 'edit', 'update', 'destroy', 'toggle_active', 'restore', 'force_delete'],
            'statistics' => ['view', 'ats', 'jobs', 'dashboard'],
            'applicant_profiles' => ['view', 'view.any', 'show'],
            'profiles' => ['view', 'edit', 'update', 'update_password'],
            'notifications' => ['view'],
            'public_jobs' => ['view'],
            'users' => ['view', 'create', 'edit', 'update', 'destroy'],
            'roles' => ['view', 'create', 'edit', 'update', 'destroy'],
        ];

        foreach ($modules as $module => $actions) {
            foreach ($actions as $action) {
                $slug = "{$module}.{$action}";
                $permissions[] = [
                    'name' => ucfirst(str_replace('.', ' ', $slug)),
                    'slug' => $slug,
                    'module' => $module,
                    'action' => $action,
                    'description' => "Allows {$action} on {$module}.",
                    'is_active' => true,
                ];
            }
        }

        return $permissions;
    }
}
