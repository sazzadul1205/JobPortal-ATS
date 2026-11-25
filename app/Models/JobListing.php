<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $requirements
 * @property int $location_id
 * @property string|null $salary
 * @property string $job_type
 * @property int $category_id
 * @property string $experience_level
 * @property string|null $education_requirement
 * @property array|null $benefits
 * @property array|null $skills
 * @property array|null $responsibilities
 * @property array|null $keywords
 * @property Carbon|null $application_deadline
 * @property string|null $schedule_start_date
 * @property bool $is_active
 * @property int $user_id
 * @property bool $show_linkedin
 * @property bool $show_facebook
 */
class JobListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'requirements',
        'location_id',
        'salary',
        'job_type',
        'category_id',
        'experience_level',
        'education_requirement',
        'benefits',
        'skills',
        'responsibilities',
        'keywords',
        'application_deadline',
        'schedule_start_date',
        'is_active',
        'user_id',
        'show_linkedin',
        'show_facebook',
    ];

    protected $casts = [
        'benefits' => 'array',
        'skills' => 'array',
        'responsibilities' => 'array',
        'keywords' => 'array',
        'application_deadline' => 'date',
        'schedule_start_date' => 'date',
        'is_active' => 'boolean',
        'show_linkedin' => 'boolean',
        'show_facebook' => 'boolean',
    ];

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    public function employer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function locations()
    {
        return $this->belongsToMany(Location::class, 'job_listing_location');
    }

    public function views()
    {
        return $this->hasMany(JobView::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('application_deadline', '>=', now());
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('job_type', $type);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByLocation(Builder $query, int $locationId): Builder
    {
        return $query->where('location_id', $locationId);
    }
}
