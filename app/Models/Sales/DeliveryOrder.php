<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DeliveryOrder extends Model
{
    use SoftDeletes;

    protected $table = 'nx_delivery_orders';

    protected $fillable = [
        'nx_sales_order_id',
        'nx_customer_id',
        'nx_employee_id',
        'do_number',
        'do_date',
        'status',
        'notes',
        'proof_image',
        'proof_notes',
    ];

    protected $casts = [
        'do_date' => 'datetime',
    ];

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(
            SalesOrder::class,
            'nx_sales_order_id',
            'id'
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id')->withDefault();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nx_employee_id')->withDefault();
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class, 'nx_delivery_order_id');
    }

    protected static function booted(): void
    {
        static::creating(function (DeliveryOrder $deliveryOrder) {

            if ($deliveryOrder->do_date) {
                $deliveryOrder->do_date = Carbon::parse($deliveryOrder->do_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }
        });
    }

}
