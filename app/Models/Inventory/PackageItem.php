<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;

class PackageItem extends Model
{
    protected $table = 'nx_package_items';

    protected $fillable = [
        'package_id',
        'item_type',
        'item_id',
        'quantity',
        'price',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function item()
    {
        return match ($this->item_type) {
            'product' => $this->belongsTo(Product::class, 'item_id'),
            'service' => $this->belongsTo(Service::class, 'item_id'),
            default   => null,
        };
    }
}
