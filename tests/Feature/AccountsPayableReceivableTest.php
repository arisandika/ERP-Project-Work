<?php

use App\Models\Finance\FinancialRecord;
use App\Models\HR\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// =====================================================================
// FIX-ARAP — Accounts Receivable & Payable manual payment
// Test jalur posting (create FinancialRecord) secara langsung:
//   1. Idempotency: double-execution identik tidak menambah jurnal ganda.
//   2. Partial payment berbeda nominal menumpuk (bukan overwrite).
//   3. reference_type+reference_id konsisten (menunjuk ke row piutang/hutang source).
//   4. created_by = employee id (bukan user id).
// =====================================================================

function arapEmployee(array $attrs = []): array
{
    $user = User::factory()->create();
    $employee = Employee::create(array_merge([
        'user_id'      => $user->id,
        'full_name'    => 'Staff Finance ' . uniqid(),
        'email'        => 'emp-' . uniqid() . '@test.local',
        'phone_number' => '0812' . random_int(10000000, 99999999),
        'position'     => 'Staff Finance',
    ], $attrs));

    return [$user, $employee];
}

function arapCreatePiutang(array $overrides = []): FinancialRecord
{
    return FinancialRecord::create(array_merge([
        'transaction_date' => now()->toDateString(),
        'type'             => 'piutang',
        'amount'           => 1000000,
        'category'         => 'Accounts Receivable',
        'description'      => 'Piutang customer dari invoice TEST-INV',
        'reference_number' => '001/INV/NEX/VI/2026',
        'reference_type'   => \App\Models\Sales\Invoice::class,
        'reference_id'     => 123,
    ], $overrides));
}

function arapCreateHutang(array $overrides = []): FinancialRecord
{
    return FinancialRecord::create(array_merge([
        'transaction_date' => now()->toDateString(),
        'type'             => 'hutang',
        'amount'           => 2000000,
        'category'         => 'Supplier Invoice',
        'description'      => 'Hutang supplier dari invoice TEST-PI',
        'reference_number' => 'PI-2026-001',
        'reference_type'   => \App\Models\Procurement\PurchaseInvoice::class,
        'reference_id'     => 456,
    ], $overrides));
}

/**
 * Simulasi jalur AccountsReceivable::receivePayment (Filament Page action).
 * Reproduksi logika posting + idempotent guard secara langsung.
 */
function simulateARPayment(FinancialRecord $receivable, array $data, Employee $employee): FinancialRecord|null
{
    return DB::transaction(function () use ($receivable, $data, $employee) {
        $alreadyRecorded = FinancialRecord::query()
            ->where('reference_type', FinancialRecord::class)
            ->where('reference_id', $receivable->id)
            ->where('amount', $data['amount'])
            ->whereDate('transaction_date', $data['transaction_date'])
            ->where('type', 'pemasukan')
            ->exists();

        if ($alreadyRecorded) {
            return null;
        }

        return FinancialRecord::create([
            'transaction_date' => $data['transaction_date'],
            'type'             => 'pemasukan',
            'amount'           => $data['amount'],
            'category'         => 'Accounts Receivable',
            'description'      => $data['description'] ?? 'Penerimaan pembayaran customer',
            'reference_number' => $receivable->reference_number,
            'reference_type'   => FinancialRecord::class,
            'reference_id'     => $receivable->id,
            'created_by'       => $employee->id,
        ]);
    });
}

/**
 * Simulasi jalur AccountsPayable::payDebt (Filament Page action).
 */
function simulateAPPayment(FinancialRecord $debt, array $data, Employee $employee): FinancialRecord|null
{
    return DB::transaction(function () use ($debt, $data, $employee) {
        $alreadyRecorded = FinancialRecord::query()
            ->where('reference_type', FinancialRecord::class)
            ->where('reference_id', $debt->id)
            ->where('amount', $data['amount'])
            ->whereDate('transaction_date', $data['transaction_date'])
            ->where('type', 'pengeluaran')
            ->exists();

        if ($alreadyRecorded) {
            return null;
        }

        return FinancialRecord::create([
            'transaction_date' => $data['transaction_date'],
            'type'             => 'pengeluaran',
            'amount'           => $data['amount'],
            'category'         => 'Accounts Payable',
            'description'      => $data['description'] ?? 'Pembayaran hutang supplier',
            'reference_number' => $debt->reference_number,
            'reference_type'   => FinancialRecord::class,
            'reference_id'     => $debt->id,
            'created_by'       => $employee->id,
        ]);
    });
}

// =====================================================================
// AR — Tests
// =====================================================================

test('AR-FIX-001: Pembayaran piutang idempotent — double submit identik tidak ganda', function () {
    [$user, $employee] = arapEmployee();
    $this->actingAs($user);

    $piutang = arapCreatePiutang();
    $data = [
        'transaction_date' => now()->toDateString(),
        'amount'           => 500000,
        'description'      => 'Pembayaran customer',
    ];

    $r1 = simulateARPayment($piutang, $data, $employee);
    $r2 = simulateARPayment($piutang, $data, $employee);

    expect($r1)->not->toBeNull('First call should create a record');
    expect($r2)->toBeNull('Second call (identical) should be no-op');

    $count = FinancialRecord::query()
        ->where('reference_type', FinancialRecord::class)
        ->where('reference_id', $piutang->id)
        ->where('type', 'pemasukan')
        ->count();
    expect($count)->toBe(1);

    // reference_type + reference_id konsisten.
    $record = FinancialRecord::query()
        ->where('type', 'pemasukan')
        ->where('category', 'Accounts Receivable')
        ->first();
    expect($record->reference_type)->toBe(FinancialRecord::class);
    expect($record->reference_id)->toBe($piutang->id);
    expect($record->reference_number)->toBe($piutang->reference_number);

    // created_by = employee id (FK ke nx_employees, bukan user id).
    expect($record->created_by)->toBe($employee->id);
});

test('AR-FIX-002: Partial payment piutang nominal berbeda menumpuk (tidak overwrite)', function () {
    [$user, $employee] = arapEmployee();
    $this->actingAs($user);

    $piutang = arapCreatePiutang();
    $date = now()->toDateString();

    $r1 = simulateARPayment($piutang, ['transaction_date' => $date, 'amount' => 300000], $employee);
    $r2 = simulateARPayment($piutang, ['transaction_date' => $date, 'amount' => 500000], $employee);

    expect($r1)->not->toBeNull();
    expect($r2)->not->toBeNull();

    $count = FinancialRecord::query()
        ->where('type', 'pemasukan')
        ->where('category', 'Accounts Receivable')
        ->count();
    expect($count)->toBe(2);

    expect(FinancialRecord::query()->where('type', 'pemasukan')->where('amount', 300000)->exists())->toBeTrue();
    expect(FinancialRecord::query()->where('type', 'pemasukan')->where('amount', 500000)->exists())->toBeTrue();
});

// =====================================================================
// AP — Tests
// =====================================================================

test('AP-FIX-001: Pembayaran hutang idempotent — double submit identik tidak ganda', function () {
    [$user, $employee] = arapEmployee();
    $this->actingAs($user);

    $hutang = arapCreateHutang();
    $data = [
        'transaction_date' => now()->toDateString(),
        'amount'           => 1000000,
        'description'      => 'Pembayaran supplier',
    ];

    $r1 = simulateAPPayment($hutang, $data, $employee);
    $r2 = simulateAPPayment($hutang, $data, $employee);

    expect($r1)->not->toBeNull('First call should create a record');
    expect($r2)->toBeNull('Second call (identical) should be no-op');

    $count = FinancialRecord::query()
        ->where('reference_type', FinancialRecord::class)
        ->where('reference_id', $hutang->id)
        ->where('type', 'pengeluaran')
        ->count();
    expect($count)->toBe(1);

    $record = FinancialRecord::query()
        ->where('type', 'pengeluaran')
        ->where('category', 'Accounts Payable')
        ->first();
    expect($record->reference_type)->toBe(FinancialRecord::class);
    expect($record->reference_id)->toBe($hutang->id);
    expect($record->reference_number)->toBe($hutang->reference_number);
    expect($record->created_by)->toBe($employee->id);
});

test('AP-FIX-002: Partial payment hutang nominal berbeda menumpuk (tidak overwrite)', function () {
    [$user, $employee] = arapEmployee();
    $this->actingAs($user);

    $hutang = arapCreateHutang();
    $date = now()->toDateString();

    $r1 = simulateAPPayment($hutang, ['transaction_date' => $date, 'amount' => 400000], $employee);
    $r2 = simulateAPPayment($hutang, ['transaction_date' => $date, 'amount' => 600000], $employee);

    expect($r1)->not->toBeNull();
    expect($r2)->not->toBeNull();

    $count = FinancialRecord::query()
        ->where('type', 'pengeluaran')
        ->where('category', 'Accounts Payable')
        ->count();
    expect($count)->toBe(2);

    expect(FinancialRecord::query()->where('type', 'pengeluaran')->where('amount', 400000)->exists())->toBeTrue();
    expect(FinancialRecord::query()->where('type', 'pengeluaran')->where('amount', 600000)->exists())->toBeTrue();
});