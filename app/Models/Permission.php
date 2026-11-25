<?php

namespace App\Models;

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
}
