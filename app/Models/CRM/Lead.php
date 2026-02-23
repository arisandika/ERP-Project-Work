<?php

namespace App\Models\CRM;

use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $table = 'nx_leads';

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
        'status', // new, contacted, qualified, converted, lost
        'notes',
    ];

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'nx_lead_id');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'nx_lead_id');
    }
}
