<?php

namespace App\Models\AfterSales;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class VendorClaim extends Model
{
    protected $table = 'nx_vendor_claims';

    protected $guarded = [];

    protected $fillable = [
        'rma_id',
        'supplier_id',
        'purchase_order_id',
        'status',
        'resolution_type',
        'vendor_notes',
        'sent_at',
        'received_at',
        'created_by',
    ];

    protected $casts = [
        'sent_at'     => 'datetime',
        'received_at' => 'datetime',
    ];

    public const STATUS_SENT      = 'sent';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED  = 'rejected';

    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_SENT      => 'Dikirim ke Supplier',
            self::STATUS_COMPLETED => 'Klaim Selesai',
            self::STATUS_REJECTED  => 'Klaim Ditolak',
        ];
    }

    public function rma()
    {
        return $this->belongsTo(ReturnRequest::class, 'rma_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}