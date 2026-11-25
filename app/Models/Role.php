<?php

namespace App\Models;

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
 */
class Role extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'level', 'is_default', 'is_active', 'created_by', 'updated_by'];

    /** Users assigned to this role. */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_roles');
    }

    /** Permissions granted to this role. */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }
}
