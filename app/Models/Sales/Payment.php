<?php

namespace App\Models\Sales;

use App\Models\Finance\FinancialRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Payment extends Model
{
    use SoftDeletes;

    protected $table = 'nx_payments';

    protected $fillable = [
        'nx_invoice_id',
        'payment_number',
        'payment_date',
        'amount',
        'payment_method',
        'status',
        'gateway_provider',
        'gateway_transaction_id',
        'gateway_reference',
        'notes',
        'created_by',
        'paid_by_name',
        'paid_by_email',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'amount' => 'decimal:2',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'nx_invoice_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getIsSettledAttribute(): bool
    {
        return in_array($this->status, ['paid', 'settlement', 'success'], true);
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'transfer' => 'Transfer Bank',
            'cash' => 'Tunai',
            'credit_card' => 'Kartu Kredit',
            'qris' => 'QRIS',
            'midtrans', 'xendit' => strtoupper($this->payment_method),
            default => ucfirst(str_replace('_', ' ', (string) $this->payment_method)),
        };
    }

    public function syncFinancialRecord(): void
    {
        if (!$this->is_settled || !$this->invoice) {
            return;
        }

        $employeeId = $this->creator?->employee?->id
            ?? auth()->user()?->employee?->id
            ?? null;

        FinancialRecord::updateOrCreate(
            [
                'reference_type' => self::class,
                'reference_id' => $this->id,
            ],
            [
                'transaction_date' => $this->payment_date ?? now(),
                'type' => 'pemasukan',
                'amount' => round((float) $this->amount, 2),
                'category' => 'Sales Revenue',
                'description' => 'Pembayaran Invoice dari Klien: '
                    . ($this->invoice->customer->name ?? '-')
                    . ' via '
                    . strtoupper($this->payment_method ?? 'UNKNOWN'),
                'reference_number' => $this->payment_number,
                'created_by' => $employeeId,
            ]
        );
    }

    public function deleteFinancialRecord(): void
    {
        FinancialRecord::query()
            ->where('reference_type', self::class)
            ->where('reference_id', $this->id)
            ->delete();
    }

    protected static function booted(): void
    {
        static::creating(function (Payment $payment) {
            if (blank($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber(
                    $payment->payment_date ? Carbon::parse($payment->payment_date) : now()
                );
            }

            if ($payment->payment_date) {
                $payment->payment_date = Carbon::parse($payment->payment_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }

            $payment->amount = round((float) ($payment->amount ?? 0), 2);

            if (blank($payment->status)) {
                $payment->status = 'paid';
            }
        });

        static::updating(function (Payment $payment) {
            if ($payment->payment_date) {
                $payment->payment_date = Carbon::parse($payment->payment_date)
                    ->setTimeFromTimeString(now()->format('H:i:s'));
            }

            $payment->amount = round((float) ($payment->amount ?? 0), 2);

            if (blank($payment->status)) {
                $payment->status = 'paid';
            }
        });

        static::created(function (Payment $payment) {
            $payment->invoice?->refresh()->recalculateStatus();

            if ($payment->is_settled) {
                $payment->syncFinancialRecord();
            }
        });

        static::updated(function (Payment $payment) {
            $payment->invoice?->refresh()->recalculateStatus();

            if ($payment->is_settled) {
                $payment->syncFinancialRecord();
            } else {
                $payment->deleteFinancialRecord();
            }
        });

        static::deleted(function (Payment $payment) {
            $payment->deleteFinancialRecord();
            $payment->invoice?->refresh()->recalculateStatus();
        });

        static::restored(function (Payment $payment) {
            $payment->invoice?->refresh()->recalculateStatus();

            if ($payment->is_settled) {
                $payment->syncFinancialRecord();
            }
        });
    }

    public static function generatePaymentNumber(?Carbon $date = null): string
    {
        $date ??= now();

        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][$date->month - 1];
        $year = $date->year;
        $company = 'NEX';
        $code = 'PAY';

        $prefixLike = "%/{$code}/{$company}/{$roman}/{$year}";

        $last = self::withTrashed()
            ->where('payment_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('payment_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) ($parts[0] ?? 0)) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
