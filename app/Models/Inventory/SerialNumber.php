<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use App\Models\Procurement\Supplier;
use App\Models\CRM\Customer;

class SerialNumber extends Model
{
    protected $table = 'nx_serial_number';

    protected $fillable = [
        'product_id',
        'warehouse_id',
        'serial_number',
        'status',
        'supplier_id',
        'client_id',
        'inbound_date',
        'warranty_expired_at',
    ];

    // 3. Relasi ke Model Product (Satu SN milik Satu Produk)
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }


    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

}
