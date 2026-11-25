<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $applicant_profile_id
 * @property string $achievement_name
 * @property string|null $achievement_details
 */
class Achievement extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_ACHIEVEMENTS_PER_PROFILE = 3;

    protected $fillable = ['applicant_profile_id', 'achievement_name', 'achievement_details'];

    /** Belongs to an applicant profile. */
    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->latest();
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('achievement_name', 'like', "%{$search}%")
            ->orWhere('achievement_details', 'like', "%{$search}%");
    }

    public function getStats(): array
    {
        return [
            'total' => $this->applicantProfile->achievements()->count(),
            'recent' => $this->applicantProfile->achievements()->recent()->take(5)->get(),
        ];
    }
}
