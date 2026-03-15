<?php

namespace App\Models\Finance;

use App\Models\Finance\ReimbursementRequest;
use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Model;
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
    ];

    // --- LOGIKA AUTO-NUMBERING ---
    protected static function booted(): void
    {
        static::creating(function (FinancialRecord $model) {
            if (empty($model->transaction_code)) {
                $model->transaction_code = self::generateTransactionCode($model->type);
            }
        });
    }

    private static function generateTransactionCode(string $type): string
    {
        return DB::transaction(function () use ($type) {
            $year = now()->format('Y');
            $month = now()->format('n');
            $romanMonth = self::getRomanMonth((int) $month);
            $companyCode = 'NEX'; // Ganti jika inisial perusahaan beda

            // Prefix berdasarkan tipe pemasukan/pengeluaran
            $prefix = match (strtolower($type)) {
                'pemasukan'  => 'FIN-IN',
                'pengeluaran' => 'FIN-OUT',
                default => 'FIN-UNK'
            };

            // Format pencarian: %/FIN-IN/NEX/III/2026
            $searchPattern = "%/{$prefix}/{$companyCode}/{$romanMonth}/{$year}";

            // Gunakan lockForUpdate agar aman saat banyak kasir input bersamaan
            $lastTrx = static::where('transaction_code', 'like', $searchPattern)
                ->lockForUpdate()
                ->orderBy('id', 'desc')
                ->first();

            $sequence = 1;
            if ($lastTrx) {
                // Ambil angka paling depan
                $parts = explode('/', $lastTrx->transaction_code);
                $sequence = (int) $parts[0] + 1;
            }

            // Output akhir: 0001/FIN-IN/NEX/III/2026
            return sprintf("%04d/%s/%s/%s/%s", $sequence, $prefix, $companyCode, $romanMonth, $year);
        });
    }

    private static function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }
    // --- AKHIR LOGIKA AUTO-NUMBERING ---

    public function reference()
    {
        return $this->morphTo();
    }

    public function reimbursement()
    {
        return $this->belongsTo(ReimbursementRequest::class, 'reimburse_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'created_by');
    }
}
