<?php

namespace App\Models\CRM;

use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deal extends Model
{
    protected $table = 'nx_deals';

    protected $fillable = [
        'nx_lead_id',
        'nx_quotation_id',
        'deal_number',
        'deal_date',
        'amount',
        'status', // open, won, lost, converted
    ];

    protected $casts = [
        'deal_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'nx_lead_id');
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class, 'nx_quotation_id');
    }

    public function salesOrder(): HasOne
    {
        return $this->hasOne(SalesOrder::class, 'nx_deal_id');
    }
}
