<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Quotation extends Model
{
    use SoftDeletes;

    protected $table = 'nx_quotations';

    protected $fillable = [
        'nx_customer_id',
        'nx_employee_id',
        'created_by_user_id',
        'created_by_employee_id',
        'approved_by_user_id',
        'approved_by_employee_id',
        'approved_at',
        'quotation_number',
        'quotation_date',
        'valid_until',
        'status',
        'notes',
        'subtotal',
        'tax',
        'grand_total',
        'promo_code_id',
        'discount_amount',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'valid_until' => 'date',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(QuotationItem::class, 'nx_quotation_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id')->withDefault();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nx_employee_id')->withDefault();
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id')->withDefault();
    }

    public function createdByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id')->withDefault();
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id')->withDefault();
    }

    public function approvedByEmployee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by_employee_id')->withDefault();
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'nx_quotation_id', 'id');
    }

    public function promoCode(): BelongsTo
    {
        return $this->belongsTo(PromoCode::class, 'promo_code_id');
    }



}
