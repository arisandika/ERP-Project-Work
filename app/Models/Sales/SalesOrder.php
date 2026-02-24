<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\CRM\Deal;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $table = 'nx_sales_orders';

    protected $fillable = [
        'nx_deal_id',
        'nx_quotation_id',
        'nx_employee_id',
        'order_number',
        'order_date',
        'status',
        'notes',
        'subtotal',
        'discount_amount',
        'tax',
        'grand_total',
        'promo_code_id',
    ];

    protected $casts = [
        'order_date' => 'datetime',
    ];

    // Relasi ke SalesOrderItem
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'nx_sales_order_id');
    }

    // Relasi ke Customer
    // public function customer(): BelongsTo
    // {
    //     return $this->belongsTo(Customer::class, 'nx_customer_id')->withDefault();
    // }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id');
    }

    // Relasi ke Employee/Sales
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nx_employee_id')->withDefault();
    }

    // Relasi kembali ke Quotation asalnya
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'nx_quotation_id')->withDefault();
    }

    // App\Models\Sales\SalesOrder.php
    public function promoCode()
    {
        return $this->belongsTo(\App\Models\Sales\PromoCode::class, 'promo_code_id');
    }

    // Relasi ke DeliveryOrder
    public function deliveryOrders()
    {
        return $this->hasMany(\App\Models\Sales\DeliveryOrder::class, 'nx_sales_order_id');
    }


    protected static function booted(): void
    {
        static::creating(function (SalesOrder $salesOrder) {

            if ($salesOrder->order_date) {
                $salesOrder->order_date = Carbon::parse($salesOrder->order_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }
        });
    }
}
