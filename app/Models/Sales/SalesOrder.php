<?php

namespace App\Models\Sales;

use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesOrder extends Model
{
    use SoftDeletes;

    protected $table = 'nx_sales_orders';

    protected $fillable = [
        'nx_quotation_id', // Foreign key ke quotation
        'nx_customer_id',
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

    // Mengubah tipe data kolom tertentu secara otomatis
    protected $casts = [
        'order_date' => 'date',
    ];

    // Relasi ke SalesOrderItem (ini juga perlu dibuat)
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class, 'nx_sales_order_id');
    }

    // Relasi ke Customer (sama seperti di Quotation)
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id')->withDefault();
    }

    // Relasi ke Employee/Sales (sama seperti di Quotation)
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'nx_employee_id')->withDefault();
    }

    // Relasi kembali ke Quotation asalnya
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'nx_quotation_id')->withDefault();
    }


}
