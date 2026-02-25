<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;
    
    protected $table = 'nx_customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'customer_type',
        'nik',
        'npwp',
        'pic_name',
        'pic_position',
        'pic_phone',
        'source',
        'status'
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_customer_id');
    }
}