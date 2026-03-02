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
    ];

    protected $casts = [
        'date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
        'employee_id' => 'integer',
    ];

    // Pengaju
    public function employee()
    {
        return $this->belongsTo(
            Employee::class,
            'employee_id'
        );
    }

    // Approver
    public function approver()
    {
        return $this->belongsTo(
            Employee::class,
            'approved_by'
        );
    }

    // Financial record hasil reimburse
    public function financialRecord()
    {
        return $this->hasOne(
            FinancialRecord::class,
            'reimburse_id'
        );
    }
}