<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\StockTransaction;
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

    // 1. DAFTAR KONSTANTA STATUS SN (STATE MACHINE)
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

    // 2. RELASI DATABASE

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

    /**
     * Semua transaksi stok yang melibatkan SN ini — traceability chain.
     */
    public function transactions()
    {
        return $this->hasMany(StockTransaction::class, 'serial_number_id')
            ->orderBy('transaction_date', 'desc');
    }

    /**
     * Central transition guard untuk status Serial Number.
     *
     * @param  self  $sn
     * @param  string  $newStatus
     * @param  array|null  $extra  Additional fields to update (customer_id, outbound_date, warehouse_id, etc.)
     * @throws \InvalidArgumentException  When transition is not in whitelist
     * @return void
     */
    public static function transitionTo(self $sn, string $newStatus, ?array $extra = []): void
    {
        $from = strtoupper($sn->status);
        $to   = strtoupper($newStatus);

        $allowed = [
            self::STATUS_AVAILABLE   => [
                self::STATUS_RESERVED,
                self::STATUS_ON_DELIVERY,
                self::STATUS_SOLD,
                self::STATUS_DEFECTIVE,
                self::STATUS_LOST,
            ],
            self::STATUS_RESERVED    => [
                self::STATUS_SOLD,
                self::STATUS_ON_DELIVERY,
            ],
            self::STATUS_ON_DELIVERY => [
                self::STATUS_SOLD,
            ],
            self::STATUS_SOLD        => [
                self::STATUS_DEFECTIVE,
                self::STATUS_RETURNED,
            ],
            self::STATUS_DEFECTIVE   => [
                self::STATUS_AVAILABLE,
                self::STATUS_SOLD,       // reject/claim ditolak — unit tetap milik customer
            ],
            self::STATUS_RETURNED    => [
                self::STATUS_SOLD,       // reject/claim ditolak — unit kembali ke customer
            ],
        ];

        if (! isset($allowed[$from])) {
            throw new \InvalidArgumentException("Status asal '$from' tidak memiliki transisi keluar yang valid.");
        }

        if (! in_array($to, $allowed[$from], true)) {
            throw new \InvalidArgumentException("Transisi status SN tidak valid: '$from' → '$to'.");
        }

        $sn->update(array_merge(['status' => $to], $extra ?? []));
    }
}
