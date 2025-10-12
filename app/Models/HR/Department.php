<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $table = 'nx_departments';

    protected $fillable = [
        'name',
        'code',
    ];

    // Relationships
    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class, 'department_id');
    }
}
