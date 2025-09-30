<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use SoftDeletes;

    protected $table = 'nx_offices';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'radius_meters',
    ];
}
