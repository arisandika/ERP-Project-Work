<?php

namespace App\Models\CRM;

use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deal extends Model
{
    protected $table = 'nx_deals';

    protected $fillable = [
        'nx_customer_id',
        'nx_lead_id',
        'nx_deal_stage_id',
        'deal_number',
        'deal_date',
        'estimated_value',
        'status',
        'close_date'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'nx_lead_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'nx_deal_stage_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'nx_deal_id');
    }
}