<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Shift extends Model
{
    use SoftDeletes;

    protected $table = 'nx_shifts';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'tolerance_minutes',
    ];

    // Relationships
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'shift_id');
    }
}
