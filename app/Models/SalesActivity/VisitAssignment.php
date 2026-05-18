<?php

namespace App\Models\SalesActivity;

use App\Models\CRM\Deal;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VisitAssignment extends Model
{
    use SoftDeletes;

    protected $table = 'nx_visit_assignments';

    protected $fillable = [
        'nx_deal_id',
        'assigned_to_id',
        'assigned_to_type',
        'assigned_by',
        'visit_date',
        'visit_time',
        'deadline_date',
        'purpose',
        'notes',
        'status',
    ];

    protected $casts = [
        'visit_date' => 'date',
        'visit_time' => 'datetime:H:i',
        'deadline_date' => 'date',
    ];

    //----------------------------------------------------------------------
    // Constants
    //----------------------------------------------------------------------

    const PURPOSE_PRESENTATION = 'presentation';
    const PURPOSE_FOLLOW_UP = 'follow_up';
    const PURPOSE_SURVEY = 'survey';
    const PURPOSE_NEGOTIATION = 'negotiation';
    const PURPOSE_CLOSING = 'closing';
    const PURPOSE_OTHER = 'other';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public static function purposeOptions(): array
    {
        return [
            self::PURPOSE_PRESENTATION => 'Presentasi',
            self::PURPOSE_FOLLOW_UP => 'Follow Up',
            self::PURPOSE_SURVEY => 'Survei',
            self::PURPOSE_NEGOTIATION => 'Negosiasi',
            self::PURPOSE_CLOSING => 'Closing',
            self::PURPOSE_OTHER => 'Lainnya',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_PENDING => 'Menunggu',
            self::STATUS_IN_PROGRESS => 'Sedang Berjalan',
            self::STATUS_COMPLETED => 'Selesai',
            self::STATUS_CANCELLED => 'Dibatalkan',
        ];
    }

    //----------------------------------------------------------------------
    // Relations
    //----------------------------------------------------------------------

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class, 'nx_deal_id');
    }

    /**
     * Polymorphic — bisa Employee atau SalesPerson.
     * Pastikan morphMap didaftarkan di AppServiceProvider.
     */
    public function assignedTo(): MorphTo
    {
        return $this->morphTo('assigned_to');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assigned_by');
    }

    public function visitRecords(): HasMany
    {
        return $this->hasMany(VisitRecord::class, 'nx_visit_assignment_id')
            ->orderBy('visit_order');
    }

    public function latestVisitRecord(): HasMany
    {
        return $this->hasMany(VisitRecord::class, 'nx_visit_assignment_id')
            ->orderByDesc('visit_order')
            ->limit(1);
    }

    //----------------------------------------------------------------------
    // Helpers
    //----------------------------------------------------------------------

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isOverdue(): bool
    {
        return $this->deadline_date
            && $this->deadline_date->isPast()
            && !$this->isCompleted()
            && !$this->isCancelled();
    }

    public function visitCount(): int
    {
        return $this->visitRecords()->count();
    }

    /**
     * Generate visit_order berikutnya untuk assignment ini.
     * Dipanggil dari VisitRecord sebelum insert.
     */
    public function nextVisitOrder(): int
    {
        return ($this->visitRecords()->max('visit_order') ?? 0) + 1;
    }

    //----------------------------------------------------------------------
    // Lifecycle Hooks
    //----------------------------------------------------------------------

    protected static function booted(): void
    {
        // Otomatis set status in_progress saat visit record pertama ditambahkan
        static::updating(function (VisitAssignment $assignment) {
            // Status sync ditangani dari VisitRecord::booted() via event
        });

        static::deleting(function (VisitAssignment $assignment) {
            if ($assignment->isForceDeleting()) {
                $assignment->visitRecords()->withTrashed()->each->forceDelete();
            } else {
                $assignment->visitRecords()->each->delete();
            }
        });

        static::restored(function (VisitAssignment $assignment) {
            $assignment->visitRecords()->onlyTrashed()->each->restore();
        });
    }
}