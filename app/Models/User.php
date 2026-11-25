<?php

namespace App\Models;

use App\Traits\HasRoles;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $password
 * @property string|null $google_id
 * @property string|null $google_avatar
 * @property string|null $email_verified_at
 * @property array $roles_list
 * @property array $permissions_list
 *
 * @method \App\Models\Role|null getPrimaryRole()
 * @method bool hasRole(string|array $roles)
 * @method \Illuminate\Support\Collection getAllPermissions()
 * @method bool hasPermission(string $permission)
 * @method string getModuleAccess(string $module)
 * @method bool canAccess(string $module, string $level)
 *
 * @mixin HasRoles
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'google_avatar',
        'email_verified_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    protected $appends = ['roles_list', 'permissions_list'];

    public function applicantProfile()
    {
        return $this->hasOne(ApplicantProfile::class);
    }

    public function jobListings()
    {
        return $this->hasMany(JobListing::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function jobViews()
    {
        return $this->hasMany(JobView::class);
    }

    public function receivedApplications(): HasManyThrough
    {
        return $this->hasManyThrough(Application::class, JobListing::class, 'user_id', 'job_listing_id');
    }
}
