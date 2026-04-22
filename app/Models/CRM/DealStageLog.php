<?php

namespace App\Models\CRM;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealStageLog extends Model
{
    protected $table = 'nx_deal_stage_logs';

    protected $fillable = [
        'nx_deal_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by',
        'time_in_stage_days',
        'note',
    ];

    protected $casts = [
        'time_in_stage_days' => 'integer',
    ];

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(DealStage::class, 'to_stage_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'changed_by');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Apakah ini log pertama (deal baru dibuat)?
     */
    public function isInitialStage(): bool
    {
        return is_null($this->from_stage_id);
    }
}