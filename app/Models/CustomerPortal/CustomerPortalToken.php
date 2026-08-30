<?php

namespace App\Models\CustomerPortal;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPortalToken extends Model
{
    protected $table = 'nx_customer_portal_tokens';

    protected $fillable = [
        'invoice_id',
        'customer_id',
        'token',
        'expires_at',
        'used_at',
        'max_uses',
        'usage_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Sales\Invoice::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\CRM\Customer::class);
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture()
            && $this->usage_count < $this->max_uses;
    }

    public function consume(): void
    {
        $this->increment('usage_count');
        if ($this->usage_count >= $this->max_uses) {
            $this->forceFill(['used_at' => now()])->save();
        }
    }
}
