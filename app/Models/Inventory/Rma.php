<?php

namespace App\Models\Inventory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rma extends Model
{
    protected $table = 'nx_rmas'; // Wajib!
    protected $guarded = [];

    public function serialNumber(): BelongsTo
    {
        return $this->belongsTo(SerialNumber::class, 'nx_serial_number_id');
    }
}
