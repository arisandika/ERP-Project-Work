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
        'approved_at'
    ];

    protected $casts = [
        'quotation_date' => 'datetime',
        'valid_until' => 'datetime',
        'approved_at' => 'datetime'
    ];

    // --- Relations ---

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id')->withTrashed()->withDefault();
    }

    public function internalPic(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class, 'internal_pic_id')->withDefault();
    }

    public function fieldStaffPic(): BelongsTo
    {
        return $this->belongsTo(SalesPerson::class, 'field_staff_pic_id')->withDefault();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by')->withDefault();
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by')->withDefault();
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

    // --- Boot Logic ---

    protected static function booted(): void
    {
        static::creating(function (Quotation $q) {
            if ($q->quotation_date) {
                $q->quotation_date = Carbon::parse($q->quotation_date)->setTimeFromTimeString(now()->format('H:i:s'));
            }

            if ($q->valid_until) {
                $q->valid_until = Carbon::parse($q->valid_until)->setTimeFromTimeString(now()->format('H:i:s'));
            }

            if (empty($q->created_by) && auth()->check()) {
                $q->created_by = auth()->user()->employee?->id;
            }
        });
    }
}
