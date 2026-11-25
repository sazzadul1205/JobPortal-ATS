<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property int $level
 * @property bool $is_default
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_default', 'is_active', 'created_by', 'updated_by'];

    /** Users assigned to this role. */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles')->withPivot(['assigned_by', 'assigned_at', 'is_active', 'expires_at']);
    }

    /** Active users assigned to this role. */
    public function activeUsers()
    {
        return $this->users()->wherePivot('is_active', true)
            ->where(function ($query) {
                $query->whereNull('user_roles.expires_at')
                    ->orWhere('user_roles.expires_at', '>', now());
            });
    }

    /** Permissions granted to this role. */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions')->withPivot('granted');
    }

    /** Granted permissions only. */
    public function grantedPermissions()
    {
        return $this->permissions()->wherePivot('granted', true);
    }

    /** Module access levels for this role. */
    public function moduleAccess()
    {
        return $this->hasMany(RoleModuleAccess::class);
    }

    /** Grant a permission to this role. */
    public function grantPermission(Permission $permission): void
    {
        $this->permissions()->syncWithoutDetaching([$permission->id => ['granted' => true]]);
    }

    /** Revoke a permission from this role. */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions()->updateExistingPivot($permission->id, ['granted' => false]);
    }

    /** Set module access level. */
    public function setModuleAccess(string $module, string $accessLevel): void
    {
        RoleModuleAccess::updateOrCreate(
            ['role_id' => $this->id, 'module' => $module],
            ['access_level' => $accessLevel]
        );
    }

    /** Check if role can access a module at the given level. */
    public function canAccessModule(string $module, string $level = RoleModuleAccess::ACCESS_READ): bool
    {
        $access = $this->moduleAccess()->where('module', $module)->value('access_level');

        if ($access === null) {
            return false;
        }

        $hierarchy = [
            RoleModuleAccess::ACCESS_NO_ACCESS => 0,
            RoleModuleAccess::ACCESS_READ => 1,
            RoleModuleAccess::ACCESS_WRITE => 2,
            RoleModuleAccess::ACCESS_MANAGE => 3,
        ];

        return ($hierarchy[$access] ?? 0) >= ($hierarchy[$level] ?? 0);
    }

    /** Get permissions grouped by module. */
    public function getPermissionsGroupedByModule(): array
    {
        return $this->grantedPermissions()
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->select('permissions.*')
            ->get()
            ->groupBy('module');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
