<?php

namespace App\Models\Sales;

use App\Models\CRM\Deal;
use App\Models\HR\Employee;
use App\Models\Marketing\PromoCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Quotation extends Model
{
    use SoftDeletes;

    protected $table = 'nx_quotations';

    protected $fillable = [
        'nx_deal_id',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'status',
        'is_primary',
        'rejected_reason',
        // 'accepted_at',
        'subtotal',
        'tax',
        'discount_amount',
        'grand_total',
        'promo_code_id',
        'notes',
        'internal_pic_id',
        'field_staff_pic_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'quotation_date' => 'date',
        'valid_until' => 'date',
        // 'accepted_at' => 'datetime',
        'approved_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'grand_total' => 'decimal:2',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const STATUS_NEW = 'new';
    const STATUS_SENT = 'sent';
    const STATUS_NEGOTIATION = 'negotiation';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_EXPIRED = 'expired';

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'nx_quotation_id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'nx_quotation_id');
    }

    public function internalPic(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'internal_pic_id');
    }

    public function fieldStaffPic(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class, 'field_staff_pic_id');
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    //----------------------------------------------------------------------
    // Actions
    //----------------------------------------------------------------------

    /**
     * Tandai quotation ini sebagai accepted.
     * Otomatis set is_primary = true dan isi accepted_at.
     */
    public function markAsAccepted(): bool
    {
        return $this->update([
            'status' => self::STATUS_ACCEPTED,
            'accepted_at' => now(),
            'is_primary' => true,
        ]);
    }

    /**
     * Tandai quotation ini sebagai rejected.
     */
    public function markAsRejected(string $reason = null): bool
    {
        return $this->update([
            'status' => self::STATUS_REJECTED,
            'rejected_reason' => $reason,
            'is_primary' => false,
        ]);
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Rules: Enforce hanya 1 is_primary per deal
        // Saat quotation ini di-set is_primary = true,
        // semua quotation lain dalam deal yang sama di-unset
        static::saving(function (Quotation $quotation) {
            if ($quotation->is_primary) {
                static::where('nx_deal_id', $quotation->nx_deal_id)
                    ->where('id', '!=', $quotation->id ?? 0)
                    ->update(['is_primary' => false]);
            }
        });
    }
}
