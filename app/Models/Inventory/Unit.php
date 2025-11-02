<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'nx_units';
    protected $guarded = ['id']; // Gunakan ID standar

    // Relasi: Unit memiliki banyak Products
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'unit_id');
    }
}