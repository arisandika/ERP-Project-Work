<?php

namespace App\Models\Sales;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesPerson extends Model
{
    use SoftDeletes;

    protected $table = 'nx_sales_people';

    protected $fillable = [
        'type',
        'full_name',
        'email',
        'phone',
        'status',
    ];

    protected $casts = [
        'sales_target' => 'decimal:2',
        'commission_rate' => 'decimal:2',
    ];

    public function internalQuotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'internal_pic_id');
    }

    public function fieldQuotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'field_staff_pic_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'nx_salesperson_id');
    }
}
