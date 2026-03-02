<?php

namespace App\Models\Finance;


use App\Models\Finance\ReimbursementRequest;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialRecord extends Model
{
    use SoftDeletes;

    protected $table = 'nx_financial_records';

    protected $fillable = [
        'created_by',
        'transaction_date',
        'description',
        'type',
        'amount',
        'category',
        'reimburse_id',
        'receipt',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'reimburse_id' => 'integer',
    ];

    // Dari reimburse
    public function reimbursement()
    {
        return $this->belongsTo(
            ReimbursementRequest::class,
            'reimburse_id'
        );
    }

    // Dibuat oleh employee
    public function employee()
    {
        return $this->belongsTo(
            Employee::class,
            'created_by'
        );
    }
}
