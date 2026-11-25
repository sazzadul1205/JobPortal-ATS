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

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Record a job view. */
    public static function recordView(int $jobId, ?int $userId, ?string $ip): void
    {
        self::create([
            'job_listing_id' => $jobId,
            'user_id' => $userId,
            'ip_address' => $ip,
        ]);
    }
}
