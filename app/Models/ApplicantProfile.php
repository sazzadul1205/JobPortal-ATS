<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $birth_date
 * @property string|null $gender
 * @property string|null $blood_type
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $photo_path
 * @property array|null $social_links
 * @property int|null $experience_years
 * @property string|null $current_job_title
 * @property string $full_name
 */
class ApplicantProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id', 'first_name', 'last_name', 'birth_date', 'gender', 'blood_type',
        'phone', 'address', 'photo_path', 'social_links', 'experience_years', 'current_job_title',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'social_links' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function cvs()
    {
        return $this->hasMany(ApplicantCv::class);
    }

    public function primaryCv()
    {
        return $this->hasOne(ApplicantCv::class)->where('is_primary', true);
    }

    public function jobHistories()
    {
        return $this->hasMany(JobHistory::class);
    }

    public function currentJob()
    {
        return $this->hasOne(JobHistory::class)->where('is_current', true);
    }

    public function educationHistories()
    {
        return $this->hasMany(EducationHistory::class);
    }

    public function achievements()
    {
        return $this->hasMany(Achievement::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }

    /** Required fields for "complete": first_name, last_name, phone. */
    public function isComplete(): bool
    {
        return ! blank($this->first_name)
            && ! blank($this->last_name)
            && ! blank($this->phone);
    }

    /** Completion percentage across 7 fields. */
    public function completionPercentage(): int
    {
        $fields = [
            'first_name', 'last_name', 'phone', 'birth_date', 'gender',
            'experience_years', 'current_job_title',
        ];

        $filled = 0;
        foreach ($fields as $field) {
            if (! blank($this->{$field})) {
                $filled++;
            }
        }

        return (int) round(($filled / \count($fields)) * 100);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function scopeComplete(Builder $query): Builder
    {
        return $query->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->whereNotNull('phone');
    }
}
