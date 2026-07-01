<?php

use App\Models\Finance\FinancialRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('TC-001: FinancialRecord piutang memiliki account_type asset', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 11100000,
        'category' => 'Accounts Receivable',
        'description' => 'Piutang dari invoice INV-001',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->account_type)->toBe('asset');
    expect($record->normal_balance)->toBe('debit');
    expect($record->cash_flow_activity)->toBe('operating');
});

test('TC-002: FinancialRecord pemasukan memiliki account_type revenue', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 5000000,
        'category' => 'Sales Revenue',
        'description' => 'Pembayaran dari customer',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    expect($record->account_type)->toBe('revenue');
    expect($record->normal_balance)->toBe('credit');
    expect($record->cash_flow_activity)->toBe('operating');
});

test('TC-003: Transaction code piutang menggunakan prefix FIN-AR', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 1000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->transaction_code)->toContain('FIN-AR');
    expect($record->transaction_code)->toContain('NEX');
});

test('TC-004: Transaction code pemasukan menggunakan prefix FIN-IN', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 2000000,
        'category' => 'Sales Revenue',
        'description' => 'Test',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    expect($record->transaction_code)->toContain('FIN-IN');
    expect($record->transaction_code)->toContain('NEX');
});

test('TC-005: Transaction code format sesuai standar', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 5000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test format',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    $year = now()->format('Y');
    $romanMonth = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];

    expect($record->transaction_code)->toMatch('/^\d{4}\/FIN-AR\/NEX\/' . $romanMonth . '\/' . $year . '$/');
});

test('TC-006: Amount dirounding ke 2 decimal places', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 1234567.891,
        'category' => 'Sales Revenue',
        'description' => 'Test rounding',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    expect($record->amount)->toBe('1234567.89');
});

test('TC-007: Amount 0 ditangani dengan benar', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 0,
        'category' => 'Sales Revenue',
        'description' => 'Test zero',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    expect($record->amount)->toBe('0.00');
});

test('TC-008: Transaction code auto-generated saat kosong', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 3000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test auto code',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->transaction_code)->not->toBeNull();
    expect($record->transaction_code)->not->toBeEmpty();
});

test('TC-009: Duplicate transaction code dicegah (unique)', function () {
    $record1 = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 1000000,
        'category' => 'Accounts Receivable',
        'description' => 'First',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    $record2 = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 2000000,
        'category' => 'Accounts Receivable',
        'description' => 'Second',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 2,
    ]);

    expect($record1->transaction_code)->not->toBe($record2->transaction_code);
});

test('TC-010: Sequential transaction code meningkat', function () {
    $records = [];
    for ($i = 0; $i < 3; $i++) {
        $records[] = FinancialRecord::create([
            'transaction_date' => now(),
            'type' => 'pemasukan',
            'amount' => ($i + 1) * 1000000,
            'category' => 'Sales Revenue',
            'description' => "Test sequential $i",
            'reference_type' => 'App\Models\Sales\Payment',
            'reference_id' => $i + 1,
        ]);
    }

    $codes = array_map(fn($r) => (int) explode('/', $r->transaction_code)[0], $records);
    expect($codes[0])->toBeLessThan($codes[1]);
    expect($codes[1])->toBeLessThan($codes[2]);
});

test('TC-011: Polymorphic reference tersimpan dengan benar', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 7500000,
        'category' => 'Accounts Receivable',
        'description' => 'Test polymorphic',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 42,
    ]);

    expect($record->reference_type)->toBe('App\Models\Sales\Invoice');
    expect($record->reference_id)->toBe(42);
});

test('TC-012: Reference number tersimpan dari invoice number', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 8000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test ref number',
        'reference_number' => '001/INV/NEX/VI/2026',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->reference_number)->toBe('001/INV/NEX/VI/2026');
});

test('TC-013: Transaction date tersimpan dengan benar', function () {
    $date = now()->subDays(5);
    $record = FinancialRecord::create([
        'transaction_date' => $date,
        'type' => 'piutang',
        'amount' => 4000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test date',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->transaction_date->format('Y-m-d'))->toBe($date->format('Y-m-d'));
});

test('TC-014: Description tersimpan dengan benar', function () {
    $desc = 'Piutang customer dari invoice 001/INV/NEX/VI/2026';
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 6000000,
        'category' => 'Accounts Receivable',
        'description' => $desc,
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    expect($record->description)->toBe($desc);
});

test('TC-015: Soft delete FinancialRecord', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 3000000,
        'category' => 'Sales Revenue',
        'description' => 'Test soft delete',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    $record->delete();

    expect(FinancialRecord::find($record->id))->toBeNull();
    expect(FinancialRecord::withTrashed()->find($record->id))->not->toBeNull();
});

test('TC-016: Restore soft deleted FinancialRecord', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 4500000,
        'category' => 'Sales Revenue',
        'description' => 'Test restore',
        'reference_type' => 'App\Models\Sales\Payment',
        'reference_id' => 1,
    ]);

    $record->delete();
    $record->restore();

    expect(FinancialRecord::find($record->id))->not->toBeNull();
});

test('TC-017: Update FinancialRecord amount', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 5000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test update',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
    ]);

    $record->update(['amount' => 7500000]);

    expect($record->fresh()->amount)->toBe('7500000.00');
});

test('TC-018: guessAccountingFields auto-classify operating expense', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pengeluaran',
        'amount' => 500000,
        'category' => 'Office Supply',
        'description' => 'Test opex',
        'reference_type' => 'App\Models\Finance\FinancialRecord',
        'reference_id' => 1,
    ]);

    expect($record->account_type)->toBe('operating_expense');
    expect($record->normal_balance)->toBe('debit');
    expect($record->cash_flow_activity)->toBe('operating');
});

test('TC-019: guessAccountingFields auto-classify liability dari type hutang', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'hutang',
        'amount' => 10000000,
        'category' => 'Supplier Invoice',
        'description' => 'Test hutang',
        'reference_type' => 'App\Models\Finance\FinancialRecord',
        'reference_id' => 1,
    ]);

    expect($record->account_type)->toBe('liability');
    expect($record->normal_balance)->toBe('credit');
});

test('TC-020: Created by employee ID tersimpan', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 2500000,
        'category' => 'Accounts Receivable',
        'description' => 'Test created_by',
        'reference_type' => 'App\Models\Sales\Invoice',
        'reference_id' => 1,
        'created_by' => 99,
    ]);

    expect($record->created_by)->toBe(99);
});
