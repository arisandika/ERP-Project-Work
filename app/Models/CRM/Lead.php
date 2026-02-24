<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lead extends Model
{
    protected $table = 'nx_leads';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'customer_type',
        'source',
        'status',
        'notes',
        'converted_customer_id'
    ];

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_lead_id');
    }

    public function convertedCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_customer_id');
    }
}