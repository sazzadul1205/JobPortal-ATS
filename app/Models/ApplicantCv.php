<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $applicant_profile_id
 * @property string $cv_path
 * @property string|null $original_name
 * @property int $order_position
 * @property bool $is_primary
 * @property string $status
 * @property string $url
 */
class ApplicantCv extends Model
{
    use HasFactory;

    public const MAX_CVS_PER_PROFILE = 3;

    protected $fillable = ['applicant_profile_id', 'cv_path', 'original_name', 'order_position', 'is_primary', 'status'];

    protected $casts = ['is_primary' => 'boolean'];

    /** Belongs to an applicant profile. */
    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }

    /** Creating hook: enforce limit, assign order_position, clear other primaries. */
    protected static function booted(): void
    {
        static::creating(function (self $cv) {
            if ($cv->applicantProfile->cvs()->where('status', 'active')->count() >= self::MAX_CVS_PER_PROFILE) {
                throw new \Illuminate\Validation\ValidationException(
                    \Illuminate\Support\MessageBag::make(['cv_path' => 'Maximum of 3 active CVs allowed per profile.'])
                );
            }

            if (blank($cv->order_position)) {
                $cv->order_position = $cv->applicantProfile->cvs()->max('order_position') + 1;
            }

            if ($cv->is_primary) {
                $cv->applicantProfile->cvs()->where('is_primary', true)->update(['is_primary' => false]);
            }
        });

        static::updating(function (self $cv) {
            if ($cv->is_primary) {
                $cv->applicantProfile->cvs()->where('id', '!=', $cv->id)->where('is_primary', true)->update(['is_primary' => false]);
            }
        });

        static::deleting(function (self $cv) {
            if ($cv->is_primary) {
                $replacement = $cv->applicantProfile->cvs()->where('id', '!=', $cv->id)->where('status', 'active')->orderBy('order_position')->first();
                if ($replacement) {
                    $replacement->update(['is_primary' => true]);
                }
            }
        });

        static::deleted(function (self $cv) {
            $cv->applicantProfile->cvs()->where('status', 'active')->orderBy('order_position')->each(function ($c, $index) {
                $c->update(['order_position' => $index + 1]);
            });
        });
    }

    public function setAsPrimary(): void
    {
        $this->applicantProfile->cvs()->where('is_primary', true)->update(['is_primary' => false]);
        $this->update(['is_primary' => true]);
    }

    public function swapPosition(int $position1, int $position2): void
    {
        $cv1 = $this->applicantProfile->cvs()->where('order_position', $position1)->first();
        $cv2 = $this->applicantProfile->cvs()->where('order_position', $position2)->first();

        if ($cv1 && $cv2) {
            $cv1->update(['order_position' => $position2]);
            $cv2->update(['order_position' => $position1]);
        }
    }

    public function reorderCvs(): void
    {
        $this->applicantProfile->cvs()->where('status', 'active')->orderBy('order_position')->each(function ($cv, $index) {
            $cv->update(['order_position' => $index + 1]);
        });
    }

    public function getPrimaryCv(): ?self
    {
        return $this->applicantProfile->cvs()->where('is_primary', true)->first();
    }

    public function hasReachedMaxEntries(): bool
    {
        return $this->applicantProfile->cvs()->where('status', 'active')->count() >= self::MAX_CVS_PER_PROFILE;
    }

    public function getRemainingSlots(): int
    {
        return self::MAX_CVS_PER_PROFILE - $this->applicantProfile->cvs()->where('status', 'active')->count();
    }

    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->cv_path);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePrimary(Builder $query): Builder
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_position');
    }
}
