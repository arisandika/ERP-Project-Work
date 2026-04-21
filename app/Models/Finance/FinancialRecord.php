<?php

namespace App\Models\Finance;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class FinancialRecord extends Model
{
    use SoftDeletes;

    protected $table = 'nx_financial_records';

    protected $fillable = [
        'transaction_code',
        'created_by',
        'transaction_date',
        'description',
        'type',
        'amount',
        'category',
        'reimburse_id',
        'receipt',
        'reference_number',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'created_by' => 'integer',
        'reimburse_id' => 'integer',
        'reference_id' => 'integer',
        'transaction_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (FinancialRecord $model) {
            if (blank($model->transaction_code)) {
                $model->transaction_code = self::generateTransactionCode((string) $model->type);
            }

            $model->amount = round((float) ($model->amount ?? 0), 2);
        });

        static::updating(function (FinancialRecord $model) {
            $model->amount = round((float) ($model->amount ?? 0), 2);
        });
    }

    private static function generateTransactionCode(string $type): string
    {
        return DB::transaction(function () use ($type) {
            $year = now()->format('Y');
            $month = (int) now()->format('n');
            $romanMonth = self::getRomanMonth($month);
            $companyCode = 'NEX';

            $prefix = match (strtolower($type)) {
                'pemasukan' => 'FIN-IN',
                'pengeluaran' => 'FIN-OUT',
                'hutang' => 'FIN-AP',
                'piutang' => 'FIN-AR',
                default => 'FIN-UNK',
            };

            $searchPattern = "%/{$prefix}/{$companyCode}/{$romanMonth}/{$year}";

            $lastTrx = static::where('transaction_code', 'like', $searchPattern)
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            $sequence = 1;

            if ($lastTrx) {
                $parts = explode('/', $lastTrx->transaction_code);
                $sequence = ((int) ($parts[0] ?? 0)) + 1;
            }

            return sprintf('%04d/%s/%s/%s/%s', $sequence, $prefix, $companyCode, $romanMonth, $year);
        });
    }

    private static function getRomanMonth(int $month): string
    {
        $map = [
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

        return $map[$month] ?? 'I';
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function reimbursement(): BelongsTo
    {
        return $this->belongsTo(ReimbursementRequest::class, 'reimburse_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
