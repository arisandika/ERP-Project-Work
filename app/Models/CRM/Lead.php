<?php

namespace App\Models\CRM;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

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

    protected static function booted(): void
    {
        // Logic Hapus (yang sudah ada)
        static::deleting(function (Lead $lead) {
            if ($lead->isForceDeleting()) {
                $lead->deals()->withTrashed()->get()->each(function ($deal) {
                    $deal->forceDelete();
                });
            } else {
                $lead->deals()->get()->each(function ($deal) {
                    $deal->delete();
                });
            }
        });

        // TAMBAHKAN LOGIC RESTORE DI SINI
        static::restored(function (Lead $lead) {

            $lead->deals()->onlyTrashed()->get()->each(function ($deal) {
                $deal->restore();
            });
        });
    }
}