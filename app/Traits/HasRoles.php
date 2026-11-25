<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use App\Models\UserRole;
use Illuminate\Support\Collection;

trait HasRoles
{
    /** Cache key prefix for permission lookups. */
    protected string $permissionCachePrefix = 'user_permissions_';

    /** Cache TTL in seconds (300 seconds = 5 minutes). */
    protected int $permissionCacheTtl = 300;

    /**
     * Get all roles assigned to the user.
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot(['assigned_by', 'assigned_at', 'is_active', 'expires_at'])
            ->withTimestamps();
    }

    /**
     * Get active role assignments.
     */
    public function activeRoles()
    {
        return $this->roles()->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('user_roles.expires_at')
                    ->orWhere('user_roles.expires_at', '>', now());
            });
    }

    /**
     * Determine if the user has a given role.
     */
    public function hasRole(string|array $roles): bool
    {
        $roleSlugs = is_array($roles) ? $roles : [$roles];

        return $this->activeRoles()->whereIn('slug', $roleSlugs)->exists();
    }

    /**
     * Determine if the user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->hasRole($roles);
    }

    /**
     * Determine if the user has all of the given roles.
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (! $this->hasRole($role)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Assign a role to the user.
     */
    public function assignRole(string|array $roles): self
    {
        $roleSlugs = is_array($roles) ? $roles : [$roles];

        foreach ($roleSlugs as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                $this->roles()->syncWithoutDetaching([$role->id => [
                    'assigned_at' => now(),
                    'is_active' => true,
                ]]);
            }
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Assign multiple roles to the user.
     */
    public function assignRoles(array $roles): self
    {
        return $this->assignRole($roles);
    }

    /**
     * Sync roles for the user (replaces all existing assignments).
     */
    public function syncRoles(array $roles): self
    {
        $roleIds = [];

        foreach ($roles as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                $roleIds[$role->id] = [
                    'assigned_at' => now(),
                    'is_active' => true,
                ];
            }
        }

        $this->roles()->sync($roleIds);
        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Remove a role from the user.
     */
    public function removeRole(string|array $roles): self
    {
        $roleSlugs = is_array($roles) ? $roles : [$roles];

        foreach ($roleSlugs as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                $this->roles()->detach($role->id);
            }
        }

        $this->clearPermissionCache();

        return $this;
    }

    /**
     * Get the user's primary role (highest level active role).
     */
    public function getPrimaryRole(): ?Role
    {
        return $this->activeRoles()->orderByDesc('roles.level')->first();
    }

    /**
     * Get all permissions for the user through their roles.
     */
    public function getAllPermissions(): Collection
    {
        $cacheKey = $this->permissionCachePrefix . $this->id;

        return cache()->remember($cacheKey, $this->permissionCacheTtl, function () {
            $superAdmin = $this->hasRole('super-admin');

            if ($superAdmin) {
                return Permission::active()->get();
            }

            return Permission::active()
                ->join('role_permissions', 'permissions.id', '=', 'role_permissions.permission_id')
                ->join('user_roles', 'role_permissions.role_id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $this->id)
                ->where('user_roles.is_active', true)
                ->where(function ($query) {
                    $query->whereNull('user_roles.expires_at')
                        ->orWhere('user_roles.expires_at', '>', now());
                })
                ->where('role_permissions.granted', true)
                ->where('roles.is_active', true)
                ->join('roles', 'role_permissions.role_id', '=', 'roles.id')
                ->select('permissions.*')
                ->distinct()
                ->get();
        });
    }

    /**
     * Determine if the user has a given permission.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->hasRole('super-admin')) {
            return true;
        }

        $permissions = $this->getAllPermissions();

        return $permissions->contains('slug', $permission) ||
            $permissions->contains('name', $permission);
    }

    /**
     * Determine if the user has any of the given permissions.
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the user has all of the given permissions.
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (! $this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get module access level for the user.
     */
    public function getModuleAccess(string $module): string
    {
        $cacheKey = $this->permissionCachePrefix . 'module_' . $this->id . '_' . $module;

        return cache()->remember($cacheKey, $this->permissionCacheTtl, function () use ($module) {
            if ($this->hasRole('super-admin')) {
                return RoleModuleAccess::ACCESS_MANAGE;
            }

            $access = RoleModuleAccess::join('user_roles', 'role_module_access.role_id', '=', 'user_roles.role_id')
                ->where('user_roles.user_id', $this->id)
                ->where('user_roles.is_active', true)
                ->where(function ($query) {
                    $query->whereNull('user_roles.expires_at')
                        ->orWhere('user_roles.expires_at', '>', now());
                })
                ->where('role_module_access.module', $module)
                ->join('roles', 'role_module_access.role_id', '=', 'roles.id')
                ->where('roles.is_active', true)
                ->orderByDesc('roles.level')
                ->value('role_module_access.access_level');

            return $access ?? RoleModuleAccess::ACCESS_NO_ACCESS;
        });
    }

    /**
     * Determine if the user can access a module at the given level.
     */
    public function canAccess(string $module, string $level = RoleModuleAccess::ACCESS_READ): bool
    {
        $userLevel = $this->getModuleAccess($module);

        return $this->accessLevelSatisfies($userLevel, $level);
    }

    /**
     * Clear the permission cache for the user.
     */
    public function clearPermissionCache(): void
    {
        cache()->forget($this->permissionCachePrefix . $this->id);

        $modules = ['dashboard', 'job_listings', 'applications', 'categories', 'locations', 'statistics', 'applicant_profiles', 'profiles', 'public_jobs', 'notifications', 'employer_profile'];

        foreach ($modules as $module) {
            cache()->forget($this->permissionCachePrefix . 'module_' . $this->id . '_' . $module);
        }
    }

    /**
     * Get roles list attribute.
     */
    public function getRolesListAttribute(): array
    {
        return $this->activeRoles()->pluck('name')->toArray();
    }

    /**
     * Get permissions list attribute.
     */
    public function getPermissionsListAttribute(): array
    {
        return $this->getAllPermissions()->pluck('slug')->toArray();
    }

    /**
     * Check if access level satisfies required level.
     */
    protected function accessLevelSatisfies(string $userLevel, string $requiredLevel): bool
    {
        $hierarchy = [
            RoleModuleAccess::ACCESS_NO_ACCESS => 0,
            RoleModuleAccess::ACCESS_READ => 1,
            RoleModuleAccess::ACCESS_WRITE => 2,
            RoleModuleAccess::ACCESS_MANAGE => 3,
        ];

        return ($hierarchy[$userLevel] ?? 0) >= ($hierarchy[$requiredLevel] ?? 0);
    }
}
