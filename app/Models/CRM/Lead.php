<?php

namespace App\Models\CRM;

use App\Models\CRM\Deal;
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
        'pic_name',
        'pic_phone',
        'pic_email',
        'pic_position',
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
        static::deleting(function (Lead $lead) {
            if ($lead->isForceDeleting()) {
                $lead->deals()->withTrashed()->get()->each->forceDelete();
            } else {
                $lead->deals()->get()->each->delete();
            }
        });

        static::restored(function (Lead $lead) {
            $lead->deals()->onlyTrashed()->get()->each->restore();
        });
    }

    /**
     * Konversi Lead ke tabel nx_customers
     */
    public function convertToCustomer()
    {
        // 1. Cek duplikasi
        if ($this->converted_customer_id) {
            return \App\Models\CRM\Customer::find($this->converted_customer_id);
        }

        // 2. Gunakan Transaction untuk keamanan data skala Enterprise
        return \Illuminate\Support\Facades\DB::transaction(function () {
            $customer = \App\Models\CRM\Customer::create([
                'name'          => $this->name,
                'email'         => $this->email,
                'phone'         => $this->phone,
                'address'       => $this->address,
                'customer_type' => $this->customer_type,
                'source'        => $this->source,
                'pic_name'      => $this->pic_name,
                'pic_phone'     => $this->pic_phone,
                'status'        => 'active',
            ]);

            // 3. Update Lead: simpan ID customer DAN ubah status lead
            $this->update([
                'converted_customer_id' => $customer->id,
                'status' => 'converted' // agar Lead tidak dianggap prospek baru lagi
            ]);

            return $customer;
        });
    }
}
