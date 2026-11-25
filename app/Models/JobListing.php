<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property string $title
 * @property string $slug
 * @property string $description
 * @property string $requirements
 * @property string|null $salary_min
 * @property string|null $salary_max
 * @property string $job_type
 * @property int $category_id
 * @property string $experience_level
 * @property string|null $education_requirement
 * @property string|null $education_details
 * @property array|null $benefits
 * @property array|null $skills
 * @property array|null $responsibilities
 * @property array|null $keywords
 * @property Carbon|null $application_deadline
 * @property Carbon|null $publish_at
 * @property int $views_count
 * @property bool $is_active
 * @property int $user_id
 * @property bool $required_facebook_link
 * @property bool $required_linkedin_link
 * @property string $salary_range
 * @property string $job_type_label
 * @property string $experience_level_label
 */
class JobListing extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'description', 'requirements', 'job_type', 'salary_min', 'salary_max',
        'is_salary_negotiable', 'as_per_companies_policy', 'category_id', 'experience_level',
        'education_requirement', 'education_details', 'benefits', 'skills', 'responsibilities',
        'keywords', 'application_deadline', 'publish_at', 'views_count', 'is_active', 'user_id',
        'required_facebook_link', 'required_linkedin_link',
    ];

    protected $casts = [
        'benefits' => 'array',
        'skills' => 'array',
        'responsibilities' => 'array',
        'keywords' => 'array',
        'application_deadline' => 'date',
        'publish_at' => 'date',
        'views_count' => 'integer',
        'is_active' => 'boolean',
        'is_salary_negotiable' => 'boolean',
        'as_per_companies_policy' => 'boolean',
        'required_facebook_link' => 'boolean',
        'required_linkedin_link' => 'boolean',
    ];

    public function employer()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(JobCategory::class, 'category_id');
    }

    /** @return BelongsToMany<int, Location> */
    public function locations(): BelongsToMany
    {
        return $this->belongsToMany(Location::class, 'job_listing_location')->withTimestamps();
    }

    public function views()
    {
        return $this->hasMany(JobView::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /** Generate slug from title on create; regenerate on title change with -1, -2 suffixes. */
    public static function boot(): void
    {
        parent::boot();

        static::creating(function (self $listing) {
            if (blank($listing->slug)) {
                $listing->slug = self::generateUniqueSlug($listing->title);
            }
        });

        static::updating(function (self $listing) {
            if ($listing->isDirty('title')) {
                $listing->slug = self::generateUniqueSlug($listing->title, $listing->id);
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('application_deadline', '>=', now());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('publish_at')
            ->where('publish_at', '<=', now());
    }

    public function scopeByEmployer(Builder $query, int $employerId): Builder
    {
        return $query->where('user_id', $employerId);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByJobType(Builder $query, string $type): Builder
    {
        return $query->where('job_type', $type);
    }

    public function scopeByLocation(Builder $query, int $locationId): Builder
    {
        return $query->whereHas('locations', fn($q) => $q->where('locations.id', $locationId));
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('title', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
    }

    public function canApply(): bool
    {
        return $this->is_active && $this->application_deadline >= now();
    }

    public function isExpired(): bool
    {
        return $this->application_deadline < now();
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function getSalaryRangeAttribute(): string
    {
        if ($this->as_per_companies_policy) {
            return 'As per company policy';
        }

        if ($this->is_salary_negotiable) {
            return 'Negotiable';
        }

        if ($this->salary_min && $this->salary_max) {
            return "{$this->salary_min} - {$this->salary_max}";
        }

        return 'Not specified';
    }

    public function getJobTypeLabelAttribute(): string
    {
        return ucfirst(str_replace('-', ' ', $this->job_type));
    }

    public function getExperienceLevelLabelAttribute(): string
    {
        return ucfirst($this->experience_level);
    }

    /** Route binding includes soft-deleted listings. */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        return self::withTrashed()->where($field ?? 'id', $value)->first();
    }

    protected static function generateUniqueSlug(string $title, ?int $excludeId = null): string
    {
        $baseSlug = \Illuminate\Support\Str::slug($title);
        $slug = $baseSlug;
        $counter = 1;

        while (self::where('slug', $slug)
            ->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))
            ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
