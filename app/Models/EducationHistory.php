<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $applicant_profile_id
 * @property string $institution_name
 * @property string $degree
 * @property int $passing_year
 */
class EducationHistory extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_ENTRIES_PER_PROFILE = 3;

    protected $fillable = ['applicant_profile_id', 'institution_name', 'degree', 'passing_year'];

    /** Belongs to an applicant profile. */
    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }
}
