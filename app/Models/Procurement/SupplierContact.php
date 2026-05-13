<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierContact extends Model
{
    protected $table = 'nx_supplier_contacts';

    protected $fillable = [
        'supplier_id',
        'type', // contact, delivery, invoice
        'name',
        'job_position',
        'email',
        'phone',
        'notes',
    ];

    /**
     * Relasi balik ke Supplier pusat
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}
