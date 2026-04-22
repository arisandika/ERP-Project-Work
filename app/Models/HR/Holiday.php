<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Holiday extends Model
{
    use SoftDeletes;
    
    protected $table = 'nx_holidays';

    protected $fillable = [
        'name',
        'date',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
