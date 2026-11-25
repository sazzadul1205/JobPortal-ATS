<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $role_id
 * @property string $module
 * @property string $access_level
 */
class RoleModuleAccess extends Model
{
    use HasFactory;

    protected $table = 'role_module_access';

    public const ACCESS_NO_ACCESS = 'no_access';

    public const ACCESS_READ = 'read';

    public const ACCESS_WRITE = 'write';

    public const ACCESS_MANAGE = 'manage';

    protected $fillable = ['role_id', 'module', 'access_level'];

    /** Check if the access level is at least the given level. */
    public function isAtLeast(string $level): bool
    {
        $hierarchy = [
            self::ACCESS_NO_ACCESS => 0,
            self::ACCESS_READ => 1,
            self::ACCESS_WRITE => 2,
            self::ACCESS_MANAGE => 3,
        ];

        return ($hierarchy[$this->access_level] ?? 0) >= ($hierarchy[$level] ?? 0);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}
