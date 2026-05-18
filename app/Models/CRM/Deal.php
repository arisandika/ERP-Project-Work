<?php

namespace App\Models\CRM;

use App\Models\HR\Employee;
use App\Models\Sales\Quotation;
use App\Models\CRM\Customer;
use App\Models\SalesActivity\VisitAssignment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    protected $table = 'nx_deals';

    protected $fillable = [
        'nx_lead_id',
        'nx_customer_id',
        'nx_deal_stage_id',
        'title',
        'deal_number',
        'deal_date',
        'estimated_value',
        'status',
        'close_date',
        'created_by',
        'closed_at',
    ];

    protected $casts = [
        'deal_date' => 'date',
        'close_date' => 'date',
        'closed_at' => 'datetime',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const STATUS_OPEN = 'open';
    const STATUS_CLOSED_WON = 'won';
    const STATUS_CLOSED_LOST = 'lost';
    const STATUS_ON_HOLD = 'on_hold';

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'nx_lead_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'nx_customer_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'nx_deal_stage_id');
    }

    public function stageLogs(): HasMany
    {
        return $this->hasMany(DealStageLog::class, 'nx_deal_id');
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'nx_deal_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function isClosed(): bool
    {
        return in_array($this->status, [self::STATUS_CLOSED_WON, self::STATUS_CLOSED_LOST]);
    }

    /**
     * Validasi Rule: minimal ada 1 quotation accepted sebelum boleh closed_won.
     */
    public function canCloseWon(): bool
    {
        return $this->quotations()
            ->where('status', 'accepted')
            ->exists();
    }

    //----------------------------------------------------------------------
    // Actions
    //----------------------------------------------------------------------

    /**
     * Tutup deal sebagai Won.
     * Mengembalikan false jika tidak ada quotation yang accepted.
     */
    public function closeWon(): bool
    {
        if (!$this->canCloseWon()) {
            return false;
        }

        return $this->update([
            'status' => self::STATUS_CLOSED_WON,
            'closed_at' => now(),
        ]);
    }

    /**
     * Tutup deal sebagai Lost.
     */
    public function closeLost(): bool
    {
        return $this->update([
            'status' => self::STATUS_CLOSED_LOST,
            'closed_at' => now(),
        ]);
    }



    public function visitAssignments(): HasMany
    {
        return $this->hasMany(VisitAssignment::class, 'nx_deal_id');
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Catat perpindahan stage ke stage log (R-07)
        static::updating(function (Deal $deal) {
            if ($deal->isDirty('nx_deal_stage_id')) {
                $previousUpdatedAt = $deal->getOriginal('updated_at');

                $employeeId = Employee::where('user_id', auth()->id())->value('id');

                DealStageLog::create([
                    'nx_deal_id' => $deal->id,
                    'from_stage_id' => $deal->getOriginal('nx_deal_stage_id'),
                    'to_stage_id' => $deal->nx_deal_stage_id,
                    'changed_by' => $employeeId,
                    'time_in_stage_days' => $previousUpdatedAt
                        ? now()->diffInDays($previousUpdatedAt)
                        : null,
                ]);
            }
        });

        // Catat stage awal saat deal pertama kali dibuat
        static::created(function (Deal $deal) {
            if ($deal->nx_deal_stage_id) {

                $employeeId = Employee::where('user_id', auth()->id())->value('id');

                DealStageLog::create([
                    'nx_deal_id' => $deal->id,
                    'from_stage_id' => null,
                    'to_stage_id' => $deal->nx_deal_stage_id,
                    'changed_by' => $employeeId,
                    'time_in_stage_days' => null,
                ]);
            }
        });

        // Cascade soft delete ke quotations
        static::deleting(function (Deal $deal) {
            if ($deal->isForceDeleting()) {
                $deal->quotations()->withTrashed()->get()->each->forceDelete();
            } else {
                $deal->quotations()->get()->each->delete();
            }
        });

        static::restored(function (Deal $deal) {
            $deal->quotations()->onlyTrashed()->get()->each->restore();
        });
    }
}
