<?php

namespace App\Models\Procurement;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseRequisition extends Model
{
    use SoftDeletes;

    protected $table = 'nx_purchase_requisitions';
    protected $guarded = ['id'];
    protected $casts = [
        'request_date' => 'date',
        'required_date' => 'date',
        'approved_at' => 'datetime',
    ];

    // --- RELATIONS ---
    public function items()
    {
        return $this->hasMany(PurchaseRequisitionItem::class, 'purchase_requisition_id');
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // --- BUSINESS LOGIC (State Machine) ---
    public function submitForApproval(): void
    {
        $this->update(['status' => 'pending']);
    }

    public function approve(int $userId): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);
    }

    public function reject(int $userId, string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $userId,
            'approved_at' => now(),
            'rejection_note' => $reason,
        ]);
    }

    // --- AUTO NUMBERING ---
    public static function generatePRNumber(): string
    {
        $romanMonths = [1=>'I', 2=>'II', 3=>'III', 4=>'IV', 5=>'V', 6=>'VI', 7=>'VII', 8=>'VIII', 9=>'IX', 10=>'X', 11=>'XI', 12=>'XII'];
        $month = now()->month;
        $year = now()->year;
        $prefix = "PR/NEX/{$romanMonths[$month]}/{$year}";

        $lastPr = self::withTrashed()->where('pr_number', 'like', "%/{$prefix}")->orderByDesc('id')->first();
        $sequence = $lastPr ? ((int) explode('/', $lastPr->pr_number)[0]) + 1 : 1;

        return str_pad((string) $sequence, 3, '0', STR_PAD_LEFT) . "/{$prefix}";
    }

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->pr_number)) {
                $model->pr_number = self::generatePRNumber();
            }
            if (empty($model->requested_by)) {
                $model->requested_by = auth()->id() ?? 1;
            }
        });
    }
}
