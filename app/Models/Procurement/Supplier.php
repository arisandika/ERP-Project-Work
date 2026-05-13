<?php

namespace App\Models\Procurement;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'nx_suppliers';

    protected $fillable = [
        'supplier_code',
        'name',
        'is_company',
        'contact_person',
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

    protected $casts = [
        'is_company' => 'boolean',
    ];

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class, 'supplier_id');
    }

    protected static function booted()
    {
        static::creating(function ($supplier) {
            if (empty($supplier->supplier_code)) {
                // Format: SUP-2024-00001
                $prefix = 'SUP-' . date('Y') . '-';

                // Cari kode terakhir yang punya prefix yang sama
                $lastSupplier = self::where('supplier_code', 'like', $prefix . '%')
                    ->latest('id')
                    ->first();

                if (!$lastSupplier) {
                    $number = 1;
                } else {
                    // Ambil 5 angka terakhir dari kode terakhir, lalu tambah 1
                    $lastNumber = (int) substr($lastSupplier->supplier_code, -5);
                    $number = $lastNumber + 1;
                }

                $supplier->supplier_code = $prefix . str_pad($number, 5, '0', STR_PAD_LEFT);
            }
        });
    }
}
