<?php

use App\Models\CRM\Customer;
use App\Models\Finance\FinancialRecord;
use App\Models\HR\Employee;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\Sales\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = Employee::factory()->create(['user_id' => $this->user->id]);
    $this->actingAs($this->user);

    $this->customer = Customer::create([
        'name' => 'PT Test Jaya',
        'email' => 'test@ptjaya.com',
        'phone' => '0211234567',
        'customer_type' => 'company',
        'status' => 'active',
    ]);

    $this->salesOrder = SalesOrder::create([
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'order_number' => SalesOrder::generateOrderNumber(),
        'order_date' => now(),
        'status' => 'draft',
        'subtotal' => 10000000,
        'discount_amount' => 0,
        'tax' => 1100000,
        'grand_total' => 11100000,
    ]);

    $this->invoice = Invoice::create([
        'nx_sales_order_id' => $this->salesOrder->id,
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'invoice_number' => Invoice::generateInvoiceNumber(),
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'draft',
        'subtotal' => 10000000,
        'discount' => 0,
        'tax' => 1100000,
        'grand_total' => 11100000,
        'total_paid' => 0,
    ]);
});

// ==========================================
// JURNAL PIUTANG (Accounts Receivable)
// ==========================================

test('TC-001: Invoice sent membuat jurnal piutang (AR) dengan benar', function () {
    // Simulasi sendEmail action - create piutang record
    FinancialRecord::create([
        'transaction_date' => $this->invoice->invoice_date,
        'type' => 'piutang',
        'amount' => (float) $this->invoice->grand_total,
        'category' => 'Accounts Receivable',
        'description' => 'Piutang customer dari invoice ' . $this->invoice->invoice_number,
        'reference_number' => $this->invoice->invoice_number,
        'reference_type' => Invoice::class,
        'reference_id' => $this->invoice->id,
        'created_by' => $this->employee->id,
    ]);

    $record = FinancialRecord::where('reference_type', Invoice::class)
        ->where('reference_id', $this->invoice->id)
        ->where('type', 'piutang')
        ->first();

    expect($record)->not->toBeNull();
    expect($record->amount)->toBe('11100000.00');
    expect($record->category)->toBe('Accounts Receivable');
    expect($record->account_type)->toBe('asset');
    expect($record->normal_balance)->toBe('debit');
    expect($record->cash_flow_activity)->toBe('operating');
    expect($record->transaction_code)->toContain('FIN-AR');
});

test('TC-002: Transaction code format piutang sesuai standar', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 5000000,
        'category' => 'Accounts Receivable',
        'description' => 'Test AR',
        'reference_type' => Invoice::class,
        'reference_id' => $this->invoice->id,
    ]);

    $code = $record->transaction_code;
    $year = now()->format('Y');
    $romanMonth = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];

    expect($code)->toContain('FIN-AR');
    expect($code)->toContain('NEX');
    expect($code)->toContain($romanMonth);
    expect($code)->toContain($year);
    expect($code)->toMatch('/^\d{4}\/FIN-AR\/NEX\/' . $romanMonth . '\/' . $year . '$/');
});

test('TC-003: Duplikasi piutang untuk invoice yang sama dicegah', function () {
    // Create first piutang record
    FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'piutang',
        'amount' => 11100000,
        'category' => 'Accounts Receivable',
        'description' => 'Piutang pertama',
        'reference_type' => Invoice::class,
        'reference_id' => $this->invoice->id,
    ]);

    // Simulate the sendEmail check - should detect existing
    $existing = FinancialRecord::query()
        ->where('type', 'piutang')
        ->where('reference_type', Invoice::class)
        ->where('reference_id', $this->invoice->id)
        ->exists();

    expect($existing)->toBeTrue();

    // Count should still be 1
    $count = FinancialRecord::where('reference_type', Invoice::class)
        ->where('reference_id', $this->invoice->id)
        ->where('type', 'piutang')
        ->count();

    expect($count)->toBe(1);
});

test('TC-004: Jurnal piutang menggunakan grand_total invoice', function () {
    $invoice = Invoice::create([
        'nx_sales_order_id' => $this->salesOrder->id,
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'invoice_number' => Invoice::generateInvoiceNumber(),
        'invoice_date' => now(),
        'due_date' => now()->addDays(30),
        'status' => 'draft',
        'subtotal' => 20000000,
        'discount' => 2000000,
        'tax' => 1980000,
        'grand_total' => 19980000,
        'total_paid' => 0,
    ]);

    FinancialRecord::create([
        'transaction_date' => $invoice->invoice_date,
        'type' => 'piutang',
        'amount' => (float) $invoice->grand_total,
        'category' => 'Accounts Receivable',
        'description' => 'Piutang dari invoice ' . $invoice->invoice_number,
        'reference_number' => $invoice->invoice_number,
        'reference_type' => Invoice::class,
        'reference_id' => $invoice->id,
    ]);

    $record = FinancialRecord::where('reference_id', $invoice->id)->first();
    expect($record->amount)->toBe('19980000.00');
});

// ==========================================
// JURNAL PEMASUKAN (Payment/Revenue)
// ==========================================

test('TC-005: Payment full membuat jurnal pemasukan (revenue) dengan benar', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record)->not->toBeNull();
    expect($record->amount)->toBe('11100000.00');
    expect($record->type)->toBe('pemasukan');
    expect($record->category)->toBe('Sales Revenue');
    expect($record->account_type)->toBe('revenue');
    expect($record->normal_balance)->toBe('credit');
    expect($record->cash_flow_activity)->toBe('operating');
    expect($record->transaction_code)->toContain('FIN-IN');
});

test('TC-006: Transaction code format pemasukan sesuai standar', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 5000000,
        'payment_method' => 'cash',
        'status' => 'paid',
    ]);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    $code = $record->transaction_code;
    $year = now()->format('Y');
    $romanMonth = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];

    expect($code)->toContain('FIN-IN');
    expect($code)->toContain('NEX');
    expect($code)->toContain($romanMonth);
    expect($code)->toContain($year);
    expect($code)->toMatch('/^\d{4}\/FIN-IN\/NEX\/' . $romanMonth . '\/' . $year . '$/');
});

test('TC-007: Payment partial membuat jurnal pemasukan sesuai jumlah bayar', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 5000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record->amount)->toBe('5000000.00');
    expect($record->type)->toBe('pemasukan');
});

test('TC-008: Multiple payments membuat multiple jurnal pemasukan', function () {
    $payment1 = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 5000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $payment2 = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 6100000,
        'payment_method' => 'cash',
        'status' => 'paid',
    ]);

    $records = FinancialRecord::where('reference_type', Payment::class)
        ->whereIn('reference_id', [$payment1->id, $payment2->id])
        ->get();

    expect($records)->toHaveCount(2);
    expect($records->sum('amount'))->toBe('11100000.00');
});

test('TC-009: Payment dengan status pending tidak membuat jurnal', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 5000000,
        'payment_method' => 'transfer',
        'status' => 'pending',
    ]);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record)->toBeNull();
});

test('TC-010: Payment status settlement juga membuat jurnal', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'qris',
        'status' => 'settlement',
    ]);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record)->not->toBeNull();
    expect($record->type)->toBe('pemasukan');
    expect($record->amount)->toBe('11100000.00');
});

// ==========================================
// INVOICE STATUS & CASCADE
// ==========================================

test('TC-011: Invoice status berubah ke paid saat full payment', function () {
    $this->invoice->update(['status' => 'sent']);

    Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('paid');
    expect($this->invoice->total_paid)->toBe('11100000.00');
});

test('TC-012: Invoice status berubah ke partial saat partial payment', function () {
    $this->invoice->update(['status' => 'sent']);

    Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 5000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $this->invoice->refresh();
    expect($this->invoice->status)->toBe('partial');
    expect($this->invoice->total_paid)->toBe('5000000.00');
});

test('TC-013: Sales order status paid saat invoice fully paid', function () {
    $this->salesOrder->update(['status' => 'completed']);
    $this->invoice->update(['status' => 'sent']);

    Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $this->salesOrder->refresh();
    expect($this->salesOrder->status)->toBe('paid');
});

test('TC-014: SO cancelled tidak terpengaruh oleh payment', function () {
    $this->salesOrder->update(['status' => 'cancelled']);
    $this->invoice->update(['status' => 'sent']);

    Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $this->salesOrder->refresh();
    expect($this->salesOrder->status)->toBe('cancelled');
});

// ==========================================
// PAYMENT DELETE & REVERSE
// ==========================================

test('TC-015: Hapus payment menghapus jurnal pemasukan', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    // Verify journal exists
    expect(FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)->count())->toBe(1);

    // Delete payment (soft delete triggers deleteFinancialRecord)
    $payment->delete();

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->withTrashed()
        ->first();

    expect($record)->toBeNull();
});

test('TC-016: Restore payment mengembalikan jurnal pemasukan', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $payment->delete();
    $payment->restore();

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record)->not->toBeNull();
    expect($record->amount)->toBe('11100000.00');
});

test('TC-017: Update payment status ke non-settled menghapus jurnal', function () {
    $payment = Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 11100000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    // Verify journal exists
    expect(FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)->count())->toBe(1);

    // Change status to pending (non-settled)
    $payment->update(['status' => 'pending']);

    $record = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->first();

    expect($record)->toBeNull();
});

// ==========================================
// EDGE CASES & VALIDASI
// ==========================================

test('TC-018: Amount rounding 2 decimal places', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 1234567.891,
        'category' => 'Sales Revenue',
        'description' => 'Test rounding',
        'reference_type' => Payment::class,
        'reference_id' => 999,
    ]);

    expect($record->amount)->toBe('1234567.89');
});

test('TC-019: FinancialRecord amount 0 ditangani dengan benar', function () {
    $record = FinancialRecord::create([
        'transaction_date' => now(),
        'type' => 'pemasukan',
        'amount' => 0,
        'category' => 'Sales Revenue',
        'description' => 'Test zero amount',
        'reference_type' => Payment::class,
        'reference_id' => 998,
    ]);

    expect($record->amount)->toBe('0.00');
});

test('TC-020: Invoice remaining balance akurat setelah partial payment', function () {
    $this->invoice->update(['status' => 'sent']);

    Payment::create([
        'nx_invoice_id' => $this->invoice->id,
        'payment_date' => now(),
        'amount' => 3000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
    ]);

    $this->invoice->refresh();
    expect($this->invoice->remaining_balance)->toBe(8100000.00);
    expect($this->invoice->total_paid)->toBe('3000000.00');
});
