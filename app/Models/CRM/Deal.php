<?php

namespace App\Models\CRM;

use App\Models\Sales\Quotation;
use App\Models\CRM\Customer;
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

                $employeeId = auth()->user()?->employee?->id;


                $deal->quotations->each(function ($quotation) use ($deal, $employeeId) {
                    if ($deal->status === 'won') {
                        $quotation->update([
                            'status' => 'accepted',
                            'approved_by' => $employeeId,
                            'approved_at' => now(),
                        ]);
                    } elseif ($deal->status === 'lost') {
                        $quotation->update([
                            'status' => 'rejected',
                            'approved_by' => null,
                            'approved_at' => null,
                        ]);
                    } elseif ($deal->status === 'open') {
                        $quotation->update([
                            'status' => 'negotiation',
                            'approved_by' => null,
                            'approved_at' => null,
                        ]);
                    }
                });
            }
        });
    }
}
