<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property string $name
 * @property string|null $address
 * @property bool $is_active
 * @property string $formatted_address
 */
class Location extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'address', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    /** @return BelongsToMany<int, JobListing> */
    public function jobListings()
    {
        return $this->belongsToMany(JobListing::class, 'job_listing_location')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getFormattedAddressAttribute(): string
    {
        return $this->address ?? $this->name;
    }
}
