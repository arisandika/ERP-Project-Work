<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use RuntimeException;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $table = 'nx_purchase_requisitions';

    protected $guarded = ['id'];

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

    public static function generatePRNumber(): string
    {
        $romanMonths = [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ];

        $month = now()->month;
        $year = now()->year;
        $prefix = 'PR/NEX/' . $romanMonths[$month] . '/' . $year;

        $lastPr = self::withTrashed()
            ->where('pr_number', 'like', '%/' . $prefix)
            ->orderByDesc('id')
            ->first();

        $sequence = 1;

        if ($lastPr?->pr_number) {
            $parts = explode('/', $lastPr->pr_number);
            $sequence = ((int) ($parts[0] ?? 0)) + 1;
        }

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . '/' . $prefix;
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
