<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Office extends Model
{
    use SoftDeletes;

    protected $table = 'nx_offices';

    protected $fillable = [
        'name',
        'latitude',
        'longitude',
        'address',
        'phone_number',
        'radius_meters',
    ];

    // Relationships
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'office_id');
    }
}
