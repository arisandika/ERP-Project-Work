<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
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
    ];



}
