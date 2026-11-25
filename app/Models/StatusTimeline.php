<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $application_id
 * @property string $status
 * @property string|null $notes
 */
class StatusTimeline extends Model
{
    use HasFactory;

    protected $fillable = ['application_id', 'status', 'notes'];

    public function application()
    {
        return $this->belongsTo(Application::class);
    }
}
