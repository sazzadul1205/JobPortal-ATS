<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $module
 * @property string $action
 * @property string|null $description
 * @property bool $is_active
 */
class Permission extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'module', 'action', 'description', 'is_active'];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('permissions.is_active', true);
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function scopeByAction(Builder $query, string $action): Builder
    {
        return $query->where('action', $action);
    }
}
