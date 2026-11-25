<?php

namespace App\Models;

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
}
