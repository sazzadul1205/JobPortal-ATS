<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property string $slug
 * @property bool $is_active
 * @property int $job_count
 */
class JobCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function jobListings()
    {
        return $this->hasMany(JobListing::class, 'category_id');
    }

    public function activeJobListings()
    {
        return $this->jobListings()->active();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getJobCountAttribute(): int
    {
        return $this->jobListings()->active()->count();
    }
}
