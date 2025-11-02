<?php

namespace App\Models\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo; // Untuk Polymorphic

class PackageItem extends Model
{
    use HasFactory;

    protected $table = 'nx_package_items';
    protected $guarded = ['id'];

    // Relasi BelongsTo: Package
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    // Relasi Polymorphic: Item (bisa Product atau Service)
    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}