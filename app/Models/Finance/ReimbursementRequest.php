<?php

namespace App\Models\Finance;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReimbursementRequest extends Model
{
    use SoftDeletes, HasFactory;

    protected $table = 'nx_reimbursements';

    protected $fillable = [
        'employee_id',
        'date',
        'type',
        'amount',
        'description',
        'receipt',
        'status',
        'approved_by',
        'approved_at',
        'financial_record_id',
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    // Pengaju Reimburse
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    // Yang menyetujui
    public function approver()
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function financialRecord()
    {
        return $this->hasOne(FinancialRecord::class);
    }

    public function canBeCancelledBy($employeeId): bool
    {
        return $this->employee_id === $employeeId
            && in_array($this->status, ['pending', 'approved']);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled'
        ]);
    }
}
