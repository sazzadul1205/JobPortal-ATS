<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $role_id
 * @property int|null $assigned_by
 * @property Carbon|null $assigned_at
 * @property bool $is_active
 * @property Carbon|null $expires_at
 */
class UserRole extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'role_id', 'assigned_by', 'assigned_at', 'is_active', 'expires_at'];

    protected $casts = ['is_active' => 'boolean', 'assigned_at' => 'datetime', 'expires_at' => 'datetime'];
}
