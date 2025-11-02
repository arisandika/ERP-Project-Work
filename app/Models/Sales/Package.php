<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Package extends Model
{
    use HasFactory;

    protected $table = 'nx_packages';
    protected $primaryKey = 'id_package';
    protected $guarded = ['id_package'];

    // Relasi: Package memiliki banyak Package Items
    public function items(): HasMany
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }

    // (Opsional) Jika Anda menggunakan Model Customer:
    // public function customer(): BelongsTo
    // {
    //     return $this->belongsTo(Customer::class, 'customer_id');
    // }
}