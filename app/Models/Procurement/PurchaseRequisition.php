<?php

namespace App\Models\Procurement;

use App\Models\User;
use App\Traits\GeneratesDocumentNumber;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class PurchaseRequisition extends Model
{
    use SoftDeletes, GeneratesDocumentNumber;

    protected $table = 'nx_purchase_requisitions';

    protected $guarded = ['id'];

    protected $fillable = [
        'pr_number',
        'title',
        'request_date',
        'required_date',
        'purpose',
        'status',
        'priority',
        'requested_by',
        'submitted_by',
        'approved_by',
        'submitted_at',
        'approved_at',
        'rejection_note',
    ];

    public const PRIORITY_LOW = '1';
    public const PRIORITY_MEDIUM = '2';
    public const PRIORITY_HIGH = '3';

    public function getTotalEstimatedPriceAttribute()
    {
        return $this->items->sum(fn($item) => $item->quantity * $item->estimated_price);
    }

    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public function items()
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'purchase_requisition_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'purchase_requisition_id');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function submitForApproval(): void
    {
        if (! $this->isDraft()) {
            throw new RuntimeException('Hanya PR dengan status draft yang bisa diajukan.');
        }

        if ($this->items()->count() === 0) {
            throw new RuntimeException('Purchase Requisition harus memiliki minimal 1 item.');
        }

        $this->update([
            'status' => self::STATUS_PENDING,
            'submitted_by' => auth()->id(),
            'submitted_at' => now(),
            'rejection_note' => null,
        ]);
    }

    public function approve(int $userId): void
    {
        if (! $this->isPending()) {
            throw new RuntimeException('Hanya PR dengan status pending yang bisa disetujui.');
        }

        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_note' => null,
        ]);
    }

    public function reject(int $userId, string $reason): void
    {
        if (! $this->isPending()) {
            throw new RuntimeException('Hanya PR dengan status pending yang bisa ditolak.');
        }

        $this->update([
            'status' => self::STATUS_REJECTED,
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_note' => $reason,
        ]);
    }

    // Ubah function ini untuk memanggil Trait (Format: PR-2605-001)
    public static function generatePRNumber(): string
    {
        return self::generateDocNumber('PR', 'pr_number');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (blank($model->pr_number)) {
                $model->pr_number = self::generatePRNumber();
            }

            if (blank($model->requested_by) && auth()->check()) {
                $model->requested_by = auth()->id();
            }

            if (blank($model->status)) {
                $model->status = self::STATUS_DRAFT;
            }
        });
    }
}
