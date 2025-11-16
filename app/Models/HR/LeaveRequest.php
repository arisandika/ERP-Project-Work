<?php

namespace App\Models\HR;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use SoftDeletes;

    protected $table = 'nx_leave_requests';

    protected $fillable = [
        'employee_id',
        'leave_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'approved_at' => 'datetime',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }

    public function leave(): BelongsTo
    {
        return $this->belongsTo(Leave::class, 'leave_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    public function remainingLeave(): int
    {
        $used = LeaveRequest::where('employee_id', $this->employee_id)
            ->where('leave_id', $this->leave_id)
            ->where('status', 'approved')
            ->sum('total_days');

        $quota = $this->leave->days_count;

        return max($quota - $used, 0);
    }
}
