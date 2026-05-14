<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use App\Models\Procurement\Supplier;
use App\Models\Procurement\PurchaseOrder; // <-- Import relasi baru
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
        'purchase_order_id',
        'customer_id',
        'inbound_date',
        'outbound_date',
        'warranty_expired_at',
    ];

    protected $casts = [
        'inbound_date' => 'date',
        'outbound_date' => 'date',
        'warranty_expired_at' => 'date',
    ];

    // --- 1. DAFTAR KONSTANTA STATUS SN (STATE MACHINE) ---
    // Mencegah typo dan mempermudah pemanggilan di Service / Controller
    public const STATUS_AVAILABLE   = 'AVAILABLE';
    public const STATUS_RESERVED    = 'RESERVED';
    public const STATUS_ON_DELIVERY = 'ON_DELIVERY';
    public const STATUS_SOLD        = 'SOLD';
    public const STATUS_DEFECTIVE   = 'DEFECTIVE';
    public const STATUS_RETURNED    = 'RETURNED';
    public const STATUS_LOST        = 'LOST';

    /**
     * Helper untuk mendapatkan semua daftar label status (berguna untuk filter di Filament)
     */
    public static function getAllStatuses(): array
    {
        return [
            self::STATUS_AVAILABLE   => 'Tersedia di Gudang',
            self::STATUS_RESERVED    => 'Di-Booking (SO)',
            self::STATUS_ON_DELIVERY => 'Dalam Pengiriman',
            self::STATUS_SOLD        => 'Terjual',
            self::STATUS_DEFECTIVE   => 'Rusak / Cacat',
            self::STATUS_RETURNED    => 'Retur Klien',
            self::STATUS_LOST        => 'Hilang',
        ];
    }

    // --- 2. RELASI DATABASE ---

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

    // Relasi baru ke Dokumen PO (Traceability)
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
