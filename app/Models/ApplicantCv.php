<?php

namespace App\Models;

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
}
