<?php
namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SickRequest extends Model
{
    use SoftDeletes;

    protected $table = 'nx_sick_requests';

    protected $fillable = [
        'employee_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
        'approval_note',
        'sick_proof',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
        'employee_id' => 'integer',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function canBeCancelledBy($employeeId): bool
    {
        return $this->employee_id === $employeeId
        && in_array($this->status, ['pending', 'approved']);
    }

    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
        ]);

        Attendance::where('employee_id', $this->employee_id)
            ->where('status', 'sakit')
            ->whereBetween('date', [$this->start_date, $this->end_date])
            ->delete();
    }
}
