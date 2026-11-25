<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $job_listing_id
 * @property int|null $user_id
 * @property string|null $ip_address
 */
class JobView extends Model
{
    use HasFactory;

    protected $fillable = ['job_listing_id', 'user_id', 'ip_address'];

    /** Belongs to a job listing. */
    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    /** Belongs to a user. */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
