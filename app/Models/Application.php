<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $job_listing_id
 * @property int $user_id
 * @property int|null $applicant_profile_id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property string|null $education_level
 * @property int|null $years_of_experience
 * @property string|null $resume_path
 * @property float|null $expected_salary
 * @property array|null $ats_score
 * @property array|null $matched_keywords
 * @property array|null $missing_keywords
 * @property string $ats_calculation_status
 * @property int $ats_attempt_count
 * @property \Carbon\Carbon|null $ats_last_attempted_at
 * @property string $status
 * @property string|null $employer_notes
 * @property string|null $facebook_link
 * @property string|null $linkedin_link
 * @property bool $is_ats_completed
 */
class Application extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_SHORTLISTED = 'shortlisted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_HIRED = 'hired';

    public const ATS_PENDING = 'pending';
    public const ATS_PROCESSING = 'processing';
    public const ATS_COMPLETED = 'completed';
    public const ATS_FAILED = 'failed';

    protected $fillable = [
        'job_listing_id', 'user_id', 'applicant_profile_id', 'name', 'email', 'phone',
        'education_level', 'years_of_experience', 'resume_path', 'expected_salary',
        'ats_score', 'matched_keywords', 'missing_keywords', 'ats_last_attempted_at',
        'ats_attempt_count', 'ats_calculation_status', 'status', 'employer_notes',
        'facebook_link', 'linkedin_link',
    ];

    protected $casts = [
        'ats_score' => 'array',
        'matched_keywords' => 'array',
        'missing_keywords' => 'array',
        'expected_salary' => 'decimal:2',
        'years_of_experience' => 'integer',
        'ats_attempt_count' => 'integer',
        'ats_last_attempted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }

    public function statusTimelines()
    {
        return $this->hasMany(StatusTimeline::class);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeShortlisted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SHORTLISTED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeHired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_HIRED);
    }

    public function scopeByJob(Builder $query, int $jobListingId): Builder
    {
        return $query->where('job_listing_id', $jobListingId);
    }

    public function scopeByEmployer(Builder $query, int $employerId): Builder
    {
        return $query->whereHas('jobListing', fn($q) => $q->where('user_id', $employerId));
    }

    public function scopeMinAtsScore(Builder $query, float $score): Builder
    {
        return $query->whereRaw('JSON_EXTRACT(ats_score, "$.percentage") >= ?', [$score]);
    }

    public function scopeAtsScoreBetween(Builder $query, float $min, float $max): Builder
    {
        return $query->whereRaw('JSON_EXTRACT(ats_score, "$.percentage") BETWEEN ? AND ?', [$min, $max]);
    }

    /** Update status, write timeline row, send notification. */
    public function updateStatus(string $status, ?string $notes = null): void
    {
        $oldStatus = $this->status;

        $this->update([
            'status' => $status,
            'employer_notes' => $notes,
        ]);

        StatusTimeline::create([
            'application_id' => $this->id,
            'status' => $status,
            'notes' => $notes,
        ]);

        $this->user->notify(new \App\Notifications\ApplicationStatusUpdated($this, $oldStatus, $status));
    }

    /** Get actual resume path, falling back to primary CV. */
    public function getActualResumePath(): ?string
    {
        if ($this->resume_path) {
            return $this->resume_path;
        }

        $primaryCv = $this->applicantProfile?->primaryCv;

        return $primaryCv?->cv_path;
    }

    /** Calculate ATS score inline. */
    public function calculateATSScore(): void
    {
        $this->update([
            'ats_calculation_status' => self::ATS_PROCESSING,
            'ats_attempt_count' => $this->ats_attempt_count + 1,
            'ats_last_attempted_at' => now(),
        ]);

        try {
            $score = app(\App\Services\ATSService::class)->calculateScore($this, $this->jobListing);

            $this->update([
                'ats_score' => $score,
                'matched_keywords' => $score['matched_keywords'] ?? [],
                'missing_keywords' => $score['missing_keywords'] ?? [],
                'ats_calculation_status' => self::ATS_COMPLETED,
            ]);
        } catch (\Throwable $e) {
            $this->update([
                'ats_score' => ['error' => $e->getMessage()],
                'ats_calculation_status' => self::ATS_FAILED,
            ]);
        }
    }

    /** Reset ATS fields and recalculate. */
    public function recalculateAtsScoreInline(): void
    {
        $this->update([
            'ats_score' => null,
            'matched_keywords' => null,
            'missing_keywords' => null,
            'ats_calculation_status' => self::ATS_PENDING,
            'ats_attempt_count' => 0,
            'ats_last_attempted_at' => null,
        ]);

        $this->calculateATSScore();
    }

    public function isAtsCompleted(): bool
    {
        return $this->ats_calculation_status === self::ATS_COMPLETED;
    }

    public function isAtsCalculationStuck(int $minutes = 30): bool
    {
        if (! in_array($this->ats_calculation_status, [self::ATS_PENDING, self::ATS_PROCESSING])) {
            return false;
        }

        if (!$this->ats_last_attempted_at) {
            return true;
        }

        return $this->ats_last_attempted_at->lt(now()->subMinutes($minutes));
    }

    /** False for hired or rejected. */
    public function canBeUpdated(): bool
    {
        return ! in_array($this->status, [self::STATUS_HIRED, self::STATUS_REJECTED]);
    }
}
