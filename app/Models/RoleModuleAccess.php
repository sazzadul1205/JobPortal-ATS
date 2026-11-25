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

    public const ACCESS_NO_ACCESS = 'no_access';

    public const ACCESS_READ = 'read';

    public const ACCESS_WRITE = 'write';

    public const ACCESS_MANAGE = 'manage';

    protected $fillable = ['role_id', 'module', 'access_level'];
}
