<?php

namespace App\Models\CRM;

use App\Models\Sales\Quotation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

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

    protected static function booted(): void
    {
        static::deleting(function (Deal $deal) {
            if ($deal->isForceDeleting()) {
                $deal->quotations()->forceDelete();
            } else {
                $deal->quotations()->delete();
            }
        });

        static::restoring(function (Deal $deal) {
            $deal->quotations()->restore();
        });

        static::updated(function (Deal $deal) {
            if ($deal->wasChanged('status')) {

                // Ambil ID employee dari user yang sedang login saat ini
                $employeeId = auth()->user()?->employee?->id;

                if ($deal->status === 'won') {
                    $deal->quotations()->update([
                        'status' => 'accepted',
                        'approved_by' => $employeeId,
                        'approved_at' => now(), // Mengisi jam saat ini secara otomatis
                    ]);
                } elseif ($deal->status === 'lost') {
                    $deal->quotations()->update([
                        'status' => 'rejected',
                        'approved_by' => null, // Reset/kosongkan jika batal
                        'approved_at' => null,
                    ]);
                } elseif ($deal->status === 'open') {
                    $deal->quotations()->update([
                        'status' => 'negotiation',
                        'approved_by' => null, // Reset/kosongkan jika batal
                        'approved_at' => null,
                    ]);
                }
            }
        });
    }
}