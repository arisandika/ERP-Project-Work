<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'nx_suppliers';

    protected $fillable = [
        'supplier_code',
        'name',
        'category', // company, individual, marketplace
        'contact_person',
        'pic_position',
        'phone',
        'email',
        'website',
        'tax_id',
        'address',
        'status',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'payment_term',
        'currency',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }

}
