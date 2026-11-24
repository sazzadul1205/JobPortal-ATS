<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function applicant()
    {
        return $this->belongsTo(User::class, 'user_id');
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

    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    public function scopeShortlisted($query)
    {
        return $query->where('status', 'shortlisted');
    }

    public function scopeForJob($query, $jobListingId)
    {
        return $query->where('job_listing_id', $jobListingId);
    }
}