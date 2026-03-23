<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class PurchaseOrder extends Model
{
    protected $table = 'nx_purchase_orders';

    protected $fillable = [
        'po_number',
        'supplier_id',
        'purchase_requisition_id', // DITAMBAHKAN: Foreign key ke PR
        'order_date',
        'expected_delivery_date',
        'status',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'grand_total',
        'notes',
        'created_by'
    ];

    protected $casts = [
        'order_date' => 'date',
        'expected_delivery_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // DITAMBAHKAN: Relasi ke tabel Purchase Requisition
    public function purchaseRequisition(): BelongsTo
    {
        return $this->belongsTo(PurchaseRequisition::class, 'purchase_requisition_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(\App\Models\Finance\PurchaseOrderPayment::class, 'purchase_order_id');
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }
        });

        // DITAMBAHKAN: Event listener setelah PO berhasil di-save ke database
        static::created(function ($model) {
            if ($model->purchase_requisition_id) {
                // Ubah status PR menjadi 'completed' agar tidak bisa ditarik jadi PO lagi
                PurchaseRequisition::where('id', $model->purchase_requisition_id)
                    ->update(['status' => 'completed']);
            }
        });
    }

    // FUNGSI AUTO GENERATE NOMOR PO
    public static function generatePONumber(): string
    {
        $romanMonths = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];

        $month = now()->month;
        $year = now()->year;
        $company = 'NEX';
        $code = 'PO';

        $prefix = "{$code}/{$company}/{$romanMonths[$month]}/{$year}";

        $lastPo = self::where('po_number', 'like', "%/{$prefix}")
            ->orderByDesc('id')
            ->first();

        $sequence = 1;
        if ($lastPo) {
            $parts = explode('/', $lastPo->po_number);
            $sequence = ((int) $parts[0]) + 1;
        }

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }
}
