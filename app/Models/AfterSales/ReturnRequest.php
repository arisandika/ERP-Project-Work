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
        'issue_type',
        'status',
        'issue_description',
        'evidence_files',
        'vendor_notes',
        'internal_notes',
        'inspection_result',
        'warranty_decision',
        'resolution_type',
        'new_serial_number_id',
        'procurement_claim_id',
        'received_date',
        'sent_to_vendor_date',
        'back_from_vendor_date',
        'returned_to_client_date',
        'refund_status',
        'created_by',
        'source',
    ];

    // Issue types for customer-facing problem selection
    public const ISSUE_DAMAGED       = 'damaged';
    public const ISSUE_NOT_WORKING   = 'not_working';
    public const ISSUE_WRONG_ITEM    = 'wrong_item';
    public const ISSUE_INCOMPLETE    = 'incomplete';
    public const ISSUE_SHIPPING_DAMAGE = 'shipping_damage';
    public const ISSUE_OTHER         = 'other';

    public static function getIssueTypeLabels(): array
    {
        return [
            self::ISSUE_DAMAGED         => 'Barang rusak / cacat',
            self::ISSUE_NOT_WORKING     => 'Barang tidak berfungsi',
            self::ISSUE_WRONG_ITEM      => 'Barang yang diterima tidak sesuai',
            self::ISSUE_INCOMPLETE      => 'Barang kurang / tidak lengkap',
            self::ISSUE_SHIPPING_DAMAGE => 'Kerusakan saat pengiriman',
            self::ISSUE_OTHER           => 'Lainnya',
        ];
    }

    protected $casts = [
        'sent_to_vendor_date'     => 'datetime',
        'back_from_vendor_date'   => 'datetime',
        'returned_to_client_date' => 'datetime',
        'evidence_files'          => 'array',
    ];

    public const SOURCE_INTERNAL        = 'internal';
    public const SOURCE_CUSTOMER_PORTAL = 'customer_portal';

    // Ganti Enum dengan Class Constants
    public const STATUS_SUBMITTED          = 'submitted';
    public const STATUS_UNDER_REVIEW       = 'under_review';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_WAITING_FOR_RETURN = 'waiting_for_return';
    public const STATUS_RECEIVED           = 'received';
    public const STATUS_SENT_TO_VENDOR     = 'sent_to_vendor';
    public const STATUS_INTERNAL_REPAIR    = 'internal_repair';
    public const STATUS_READY_FOR_RETURN   = 'ready_for_return';
    public const STATUS_RETURNED_TO_CLIENT = 'returned_to_client';
    public const STATUS_REJECTED           = 'rejected';
    public const STATUS_WARRANTY_REJECTED  = 'warranty_rejected';
    public const STATUS_REFUND_PENDING     = 'refund_pending';

    // ==== INSPECTION RESULT (hasil pemeriksaan teknisi) ====
    public const INSPECTION_DAMAGED        = 'damaged';
    public const INSPECTION_NO_FAULT_FOUND = 'no_fault_found';
    public const INSPECTION_USER_ERROR     = 'user_error';
    public const INSPECTION_PHYSICAL_DAMAGE = 'physical_damage';

    public static function getInspectionResultLabels(): array
    {
        return [
            self::INSPECTION_DAMAGED        => 'Rusak / Cacat',
            self::INSPECTION_NO_FAULT_FOUND => 'Tidak Ditemukan Kerusakan',
            self::INSPECTION_USER_ERROR     => 'Kesalahan Penggunaan',
            self::INSPECTION_PHYSICAL_DAMAGE => 'Kerusakan Fisik',
        ];
    }

    // ==== WARRANTY DECISION (keputusan garansi, terpisah dari inspection) ====
    public const WARRANTY_PENDING  = 'pending';
    public const WARRANTY_APPROVED = 'approved';
    public const WARRANTY_REJECTED = 'rejected';
    public const WARRANTY_NA       = 'not_applicable';

    public static function getWarrantyDecisionLabels(): array
    {
        return [
            self::WARRANTY_PENDING  => 'Belum Ditentukan',
            self::WARRANTY_APPROVED => 'Disetujui',
            self::WARRANTY_REJECTED => 'Ditolak',
            self::WARRANTY_NA       => 'Garansi Tidak Berlaku',
        ];
    }

    // ==== REFUND STATUS (lifecycle refund di ReturnRequest) ====
    public const REFUND_PENDING   = 'pending';
    public const REFUND_COMPLETED = 'completed';

    // ==== RESOLUTION TYPE (penyelesaian final) ====
    public const RESOLUTION_REPAIR_AND_RETURN = 'repair_and_return';
    public const RESOLUTION_REPLACEMENT       = 'replacement';
    public const RESOLUTION_REFUND            = 'refund';
    public const RESOLUTION_NO_FAULT_FOUND    = 'no_fault_found';

    public static function getResolutionTypeLabels(): array
    {
        return [
            self::RESOLUTION_REPAIR_AND_RETURN => 'Perbaikan & Kembalikan',
            self::RESOLUTION_REPLACEMENT       => 'Penggantian Unit',
            self::RESOLUTION_REFUND            => 'Pengembalian Dana',
            self::RESOLUTION_NO_FAULT_FOUND    => 'Tidak Ditemukan Kerusakan',
        ];
    }

    // Helper untuk label status di Filament
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED          => 'Pengajuan Diterima',
            self::STATUS_UNDER_REVIEW       => 'Sedang Ditinjau',
            self::STATUS_APPROVED           => 'Pengajuan Disetujui',
            self::STATUS_WAITING_FOR_RETURN => 'Menunggu Barang Dikirim',
            self::STATUS_RECEIVED           => 'Barang Diterima',
            self::STATUS_SENT_TO_VENDOR     => 'Di Vendor',
            self::STATUS_INTERNAL_REPAIR    => 'Proses Internal',
            self::STATUS_READY_FOR_RETURN   => 'Siap Dikembalikan',
            self::STATUS_RETURNED_TO_CLIENT => 'Dikembalikan ke Klien',
            self::STATUS_REJECTED           => 'Ditolak',
            self::STATUS_WARRANTY_REJECTED  => 'Garansi Ditolak',
            self::STATUS_REFUND_PENDING     => 'Menunggu Refund Diproses',
        ];
    }

    /**
     * Label status untuk Customer Portal.
     * Bahasa/hal yang ditampilkan ke customer berbeda dari label internal:
     * di sini status 'returned_to_client' ditampilkan sebagai 'Selesai'.
     */
    public static function getCustomerStatusLabels(): array
    {
        return [
            self::STATUS_SUBMITTED          => 'Dalam Antrian',
            self::STATUS_UNDER_REVIEW       => 'Sedang Ditinjau',
            self::STATUS_APPROVED           => 'Pengajuan Disetujui',
            self::STATUS_WAITING_FOR_RETURN => 'Menunggu Konfirmasi Pengiriman',
            self::STATUS_RECEIVED           => 'Barang Diterima',
            self::STATUS_SENT_TO_VENDOR     => 'Sedang Ditangani Supplier',
            self::STATUS_INTERNAL_REPAIR    => 'Sedang Diproses',
            self::STATUS_READY_FOR_RETURN   => 'Siap Dikembalikan',
            self::STATUS_RETURNED_TO_CLIENT => 'Selesai',
            self::STATUS_REJECTED           => 'Ditolak',
            self::STATUS_WARRANTY_REJECTED  => 'Garansi Ditolak',
            self::STATUS_REFUND_PENDING     => 'Refund Diproses',
        ];
    }

    /**
     * Warna badge status untuk Customer Portal (Tailwind classes + hex dot).
     */
    public static function getCustomerStatusBadge(string $status): array
    {
        return match ($status) {
            self::STATUS_SUBMITTED          => ['bg' => 'bg-slate-100 dark:bg-slate-900/20', 'text' => 'text-slate-600 dark:text-slate-400', 'dot' => '#64748b'],
            self::STATUS_UNDER_REVIEW       => ['bg' => 'bg-amber-100 dark:bg-amber-900/20', 'text' => 'text-amber-600 dark:text-amber-400', 'dot' => '#d97706'],
            self::STATUS_APPROVED           => ['bg' => 'bg-sky-100 dark:bg-sky-900/20', 'text' => 'text-sky-600 dark:text-sky-400', 'dot' => '#0284c7'],
            self::STATUS_WAITING_FOR_RETURN => ['bg' => 'bg-indigo-100 dark:bg-indigo-900/20', 'text' => 'text-indigo-600 dark:text-indigo-400', 'dot' => '#4f46e5'],
            self::STATUS_RECEIVED           => ['bg' => 'bg-blue-100 dark:bg-blue-900/20', 'text' => 'text-blue-600 dark:text-blue-400', 'dot' => '#2563eb'],
            self::STATUS_SENT_TO_VENDOR     => ['bg' => 'bg-purple-100 dark:bg-purple-900/20', 'text' => 'text-purple-600 dark:text-purple-400', 'dot' => '#9333ea'],
            self::STATUS_INTERNAL_REPAIR    => ['bg' => 'bg-yellow-100 dark:bg-yellow-900/20', 'text' => 'text-yellow-600 dark:text-yellow-400', 'dot' => '#ca8a04'],
            self::STATUS_READY_FOR_RETURN   => ['bg' => 'bg-cyan-100 dark:bg-cyan-900/20', 'text' => 'text-cyan-600 dark:text-cyan-400', 'dot' => '#0891b2'],
            self::STATUS_RETURNED_TO_CLIENT => ['bg' => 'bg-green-100 dark:bg-green-900/20', 'text' => 'text-green-600 dark:text-green-400', 'dot' => '#16a34a'],
            self::STATUS_REJECTED           => ['bg' => 'bg-red-100 dark:bg-red-900/20', 'text' => 'text-red-600 dark:text-red-400', 'dot' => '#dc2626'],
            self::STATUS_WARRANTY_REJECTED  => ['bg' => 'bg-rose-100 dark:bg-rose-900/20', 'text' => 'text-rose-600 dark:text-rose-400', 'dot' => '#e11d48'],
            self::STATUS_REFUND_PENDING     => ['bg' => 'bg-teal-100 dark:bg-teal-900/20', 'text' => 'text-teal-600 dark:text-teal-400', 'dot' => '#0d9488'],
            default                         => ['bg' => 'bg-gray-100 dark:bg-gray-900/20', 'text' => 'text-gray-600 dark:text-gray-400', 'dot' => '#6b7280'],
        };
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

    public function internalRepair()
    {
        return $this->hasOne(InternalRepair::class, 'rma_id');
    }

    public function vendorClaim()
    {
        return $this->hasOne(VendorClaim::class, 'rma_id');
    }

    /**
     * Auto-determine warranty_type based on serial number's supplier.
     * If the sold serial number has a supplier_id (came from a supplier/distributor PO),
     * the claim route is 'supplier' (vendor warranty).
     * Otherwise it falls to 'store' (internal service).
     *
     * @param SerialNumber|null $serial
     * @return string 'supplier' | 'store'
     */
    public static function resolveWarrantyType(?SerialNumber $serial): string
    {
        if ($serial && $serial->supplier_id) {
            return 'supplier';
        }
        return 'store';
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
