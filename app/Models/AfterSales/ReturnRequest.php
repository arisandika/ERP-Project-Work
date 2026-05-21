<?php

namespace App\Models\AfterSales;

use Illuminate\Database\Eloquent\Model;
use App\Models\Inventory\SerialNumber;
use App\Models\Crm\Customer;

class ReturnRequest extends Model
{
    protected $table = 'nx_rma_requests';

    protected $guarded = [];

    protected $fillable = [
        'rma_number',
        'customer_id',
        'serial_number_id',
        'warranty_type',
        'status',
        'issue_description',
        'vendor_notes',
        'internal_notes',
        'resolution_type',
        'new_serial_number_id',
        'received_date',
        'sent_to_vendor_date',
        'back_from_vendor_date',
        'returned_to_client_date',
        'created_by',
    ];

    protected $casts = [
        'sent_to_vendor_date' => 'datetime',
        'back_from_vendor_date' => 'datetime',
        'returned_to_client_date' => 'datetime',
    ];

    // Ganti Enum dengan Class Constants
    public const STATUS_RECEIVED = 'received';
    public const STATUS_SENT_TO_VENDOR = 'sent_to_vendor';
    public const STATUS_INTERNAL_REPAIR = 'internal_repair';
    public const STATUS_READY_FOR_RETURN = 'ready_for_return';
    public const STATUS_RETURNED_TO_CLIENT = 'returned_to_client';

    // Helper untuk label status di Filament
    public static function getStatusLabels(): array
    {
        return [
            self::STATUS_RECEIVED => 'Di Gudang',
            self::STATUS_SENT_TO_VENDOR => 'Di Vendor',
            self::STATUS_INTERNAL_REPAIR => 'Proses Internal',
            self::STATUS_READY_FOR_RETURN => 'Siap Diambil Klien',
            self::STATUS_RETURNED_TO_CLIENT => 'Selesai',
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

    // 3. TAMBAHKAN RELASI BARU
    public function newSerialNumber()
    {
        return $this->belongsTo(SerialNumber::class, 'new_serial_number_id');
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
