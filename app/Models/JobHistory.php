<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $applicant_profile_id
 * @property string $company_name
 * @property string $position
 * @property int $starting_year
 * @property int|null $ending_year
 * @property bool $is_current
 */
class JobHistory extends Model
{
    use HasFactory, SoftDeletes;

    public const MAX_ENTRIES_PER_PROFILE = 3;

    protected $fillable = ['applicant_profile_id', 'company_name', 'position', 'starting_year', 'ending_year', 'is_current'];

    protected $casts = ['is_current' => 'boolean'];

    /** Belongs to an applicant profile. */
    public function applicantProfile()
    {
        return $this->belongsTo(ApplicantProfile::class);
    }
}
