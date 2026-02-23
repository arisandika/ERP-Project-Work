<?php

namespace App\Models\CRM;

use App\Models\Sales\Invoice;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    protected $table = 'nx_customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',

        'customer_type',
        'nik',
        'npwp',
        'pic_name',
        'pic_position',
        'pic_phone',
        
        'source',
        'status',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'nx_customer_id');
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id');
    }
}
