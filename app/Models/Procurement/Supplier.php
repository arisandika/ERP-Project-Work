<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'nx_suppliers';

    protected $fillable = [
        'name',
        'contact_person',
        'phone',
        'email',
        'address',
    ];
}
