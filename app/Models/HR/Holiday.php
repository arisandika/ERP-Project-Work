<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    protected $table = 'nx_holidays';

    protected $fillable = [
        'name',
        'start_date', // Ubah dari date
        'end_date',   // Tambahan baru
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date'   => 'date',
    ];
}
