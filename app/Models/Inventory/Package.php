<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Package extends Model
{
    use SoftDeletes;

    protected $table = 'nx_packages';

    protected $fillable = [
        'package_code',
        'package_name',
        'description',
        'total_price',
        'is_active',
    ];

    public function items()
    {
        return $this->hasMany(PackageItem::class, 'package_id');
    }

    protected static function booted(): void
    {
        static::created(function (Package $package) {
            if (empty($package->package_code)) {
                $package->updateQuietly([
                    'package_code' => 'PKT-' . str_pad($package->id, 6, '0', STR_PAD_LEFT),
                ]);
            }
        });
    }

}
