<?php
namespace App\Models\AfterSales;

use App\Models\Crm\Customer;
use App\Models\Inventory\SerialNumber;
use Illuminate\Database\Eloquent\Model;

class ReturnRequest extends Model
{
    protected $table = 'nx_rma_requests';

    protected $guarded = [];

    protected $fillable = [
        'rma_number',
        'customer_id',
        'invoice_id',
        'invoice_item_id',
        'serial_number_id',
        'product_id',
        'qty',
        'warranty_type',
        'status',
        'issue_description',
        'evidence_files',
        'vendor_notes',
        'internal_notes',
        'resolution_type',
        'new_serial_number_id',
        'procurement_claim_id',
        'received_date',
        'sent_to_vendor_date',
        'back_from_vendor_date',
        'returned_to_client_date',
        'created_by',
        'source',
    ];

    protected $casts = [
        'sent_to_vendor_date'     => 'datetime',
        'back_from_vendor_date'   => 'datetime',
        'returned_to_client_date' => 'datetime',
        'evidence_files'          => 'array',
    ];

    public const SOURCE_INTERNAL        = 'internal';
    public const SOURCE_CUSTOMER_PORTAL = 'customer_portal';

    // Ganti Enum dengan Class Constants
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_SENT_TO_VENDOR     = 'sent_to_vendor';
    public const STATUS_INTERNAL_REPAIR    = 'internal_repair';
    public const STATUS_READY_FOR_RETURN   = 'ready_for_return';
    public const STATUS_RETURNED_TO_CLIENT = 'returned_to_client';
    public const STATUS_REJECTED           = 'rejected';

    // Helper untuk label status di Filament
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_RECEIVED           => 'Di Gudang',
            self::STATUS_SENT_TO_VENDOR     => 'Di Vendor',
            self::STATUS_INTERNAL_REPAIR    => 'Proses Internal',
            self::STATUS_READY_FOR_RETURN   => 'Siap Diambil Klien',
            self::STATUS_RETURNED_TO_CLIENT => 'Selesai',
            self::STATUS_REJECTED           => 'Ditolak',
        ];
    }

    public function serialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'serial_number_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice()
    {
        return $this->belongsTo(\App\Models\Sales\Invoice::class, 'invoice_id');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(\App\Models\Sales\InvoiceItem::class, 'invoice_item_id');
    }

    public function product()
    {
        return $this->belongsTo(\App\Models\Inventory\Product::class, 'product_id');
    }

    public function newSerialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'new_serial_number_id');
    }

    public function purchaseReturn()
    {
        return $this->belongsTo(\App\Models\Procurement\PurchaseReturn::class, 'purchase_return_id');
    }

    public function procurementClaim()
    {
        return $this->belongsTo(\App\Models\Procurement\PurchaseOrder::class, 'procurement_claim_id');
    }

    protected static function booted(): void
    {
        static::updated(function (self $model) {
            if (! $model->wasChanged('status')) {
                return;
            }

            // Notifikasi email ke customer hanya untuk RMA dari customer portal
            // atau internal (customer_id tetap terhubung)
            if ($model->customer_id && $model->customer?->email) {
                try {
                    \Mail::to($model->customer->email)
                        ->queue(new \App\Mail\RmaStatusUpdated($model));
                } catch (\Throwable $e) {
                    \Log::warning('Gagal kirim email update RMA status: ' . $e->getMessage());
                }
            }
        });
    }

    public static function generateRmaNumber(): string
    {
        // Format awalan: RMA-YYMM- (contoh bulan Mei 2026: RMA-2605-)
        $prefix = 'RMA-' . date('ym') . '-';

        // Cari dokumen terakhir di bulan ini
        $lastRma = self::where('rma_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        // Ambil 3 digit terakhir, lalu tambah 1
        $sequence = $lastRma ? ((int) substr($lastRma->rma_number, -3)) + 1 : 1;

        // Format jadinya: RMA-2605-001
        return $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
    }
}
