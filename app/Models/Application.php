<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $job_listing_id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string $resume_path
 * @property float|null $expected_salary
 * @property array|null $ats_score
 * @property array|null $matched_keywords
 * @property array|null $missing_keywords
 * @property string $status
 * @property string|null $employer_notes
 */
class Application extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'job_listing_id',
        'user_id',
        'name',
        'email',
        'phone',
        'resume_path',
        'expected_salary',
        'ats_score',
        'matched_keywords',
        'missing_keywords',
        'status',
        'employer_notes',
    ];

    protected $casts = [
        'ats_score' => 'array',
        'matched_keywords' => 'array',
        'missing_keywords' => 'array',
        'expected_salary' => 'decimal:2',
    ];

    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }

    public function statusTimelines()
    {
        return $this->hasMany(StatusTimeline::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function user()
    {
        return $this->applicant();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReviewed(): bool
    {
        return $this->status === 'reviewed';
    }

    public function isShortlisted(): bool
    {
        return $this->status === 'shortlisted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isHired(): bool
    {
        return $this->status === 'hired';
    }

    public function getAtsScorePercentage(): ?int
    {
        return $this->ats_score['total'] ?? null;
    }

    public function calculateATSScore(): void
    {
        // Will be implemented with ATSService
    }

    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed(Builder $query): Builder
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeShortlisted(Builder $query): Builder
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeForJob(Builder $query, int $jobListingId): Builder
    {
        return $query->where('job_listing_id', $jobListingId);
    }
}
