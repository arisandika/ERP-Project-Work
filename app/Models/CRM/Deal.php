<?php

namespace App\Models\CRM;

use App\Enums\CRM\DealStatus;
use App\Models\CRM\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Deal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'nx_deals';
    protected $fillable = [
        'customer_id',
        'title',
        'status',
        'value',
        'notes',
        'next_follow_up_at',
        'attachments',
        'lost_reason',
    ];

    protected $casts = [
        'status'            => DealStatus::class,
        'next_follow_up_at' => 'datetime',
        'value'             => 'decimal:2',
        'attachments'       => 'array',
    ];

    /**
     * Relasi ke data Pelanggan.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Filter penawaran yang statusnya masih berjalan (belum deal/ditutup).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNotIn('status', [
            DealStatus::Deal->value,
            DealStatus::Closed->value
        ]);
    }

    /**
     * Filter penawaran yang melewati jadwal follow-up.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('next_follow_up_at', '<', now());
    }

    public function quotation(): HasOne
    {
        return $this->hasOne(\App\Models\Sales\Quotation::class, 'nx_deal_id', 'id');
    }
    }
