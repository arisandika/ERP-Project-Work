<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $table = 'nx_packages';

    protected $fillable = [
        'package_name',
        'description',
        'total_price',
        'is_active',
    ];

    public function items()
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }
}
