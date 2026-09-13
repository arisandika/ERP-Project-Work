<?php

use App\Models\CRM\Customer;
use App\Models\Finance\FinancialRecord;
use App\Models\Finance\PurchaseOrderPayment;
use App\Models\Procurement\PurchaseInvoice;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\Supplier;
use App\Models\Sales\Invoice;
use App\Models\Sales\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->employee = \App\Models\HR\Employee::create([
        'user_id' => $this->user->id,
        'full_name' => 'Test Employee',
        'email' => 'emp-' . uniqid() . '@test.local',
        'phone_number' => '0812' . random_int(10000000, 99999999),
        'position' => 'Staff',
    ]);
    $this->actingAs($this->user);
});

// =====================================================================
// PHASE 1.1 — Sales Invoice Payment: HARUS posting SATU FinancialRecord
// =====================================================================

test('P1.1-001: Pembayaran Invoice Sales membuat tepat satu FinancialRecord', function () {
    $invoice = Invoice::create([
        'invoice_number' => 'TEST-INV-' . uniqid(),
        'invoice_date' => now(),
        'status' => 'sent',
        'subtotal' => 1000000,
        'grand_total' => 1000000,
    ]);

    $invoice->payments()->create([
        'payment_date' => now(),
        'amount' => 1000000,
        'payment_method' => 'transfer',
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    $count = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $invoice->payments()->first()->id)
        ->count();

    expect($count)->toBe(1);
});

test('P1.1-002: Tidak ada posting ganda untuk satu pembayaran yang sama', function () {
    $invoice = Invoice::create([
        'invoice_number' => 'TEST-INV-' . uniqid(),
        'invoice_date' => now(),
        'status' => 'sent',
        'subtotal' => 500000,
        'grand_total' => 500000,
    ]);

    // Simulasi create yang sama persis (mis. double submit) — model Payment
    // harus updateOrCreate, bukan menambah jurnal baru.
    $payment = $invoice->payments()->create([
        'payment_date' => now(),
        'amount' => 500000,
        'payment_method' => 'cash',
        'status' => 'paid',
        'created_by' => $this->user->id,
    ]);

    $payment->syncFinancialRecord();
    $payment->syncFinancialRecord();

    $count = FinancialRecord::where('reference_type', Payment::class)
        ->where('reference_id', $payment->id)
        ->count();

    expect($count)->toBe(1);
});

// =====================================================================
// PHASE 1.2 — PurchaseOrderPayment: idempotent posting + delete bersih
// =====================================================================

function mkPurchaseInvoice(array $overrides = []): PurchaseInvoice
{
    $supplier = Supplier::create(['name' => 'Supplier ' . uniqid()]);
    $po = PurchaseOrder::create([
        'po_number' => 'PO-' . uniqid(),
        'supplier_id' => $supplier->id,
        'order_date' => now()->toDateString(),
        'status' => \App\Enums\Procurement\PurchaseOrderStatus::SENT,
        'created_by' => auth()->id(),
    ]);

    return PurchaseInvoice::create(array_merge([
        'invoice_number' => 'PI-' . uniqid(),
        'vendor_invoice_number' => 'VI-' . uniqid(),
        'purchase_order_id' => $po->id,
        'supplier_id' => $supplier->id,
        'invoice_date' => now()->toDateString(),
        'due_date' => now()->addDays(14)->toDateString(),
        'grand_total' => 2000000,
        'created_by' => auth()->id(),
    ], $overrides));
}

test('P1.2-001: Pembayaran Purchase Invoice membuat tepat satu FinancialRecord', function () {
    $invoice = mkPurchaseInvoice();
    $po = $invoice->purchaseOrder;

    $payment = PurchaseOrderPayment::create([
        'purchase_order_id' => $po->id,
        'purchase_invoice_id' => $invoice->id,
        'payment_number' => 'PAYPI-' . uniqid(),
        'payment_date' => now()->toDateString(),
        'amount' => 2000000,
        'payment_method' => 'transfer',
    ]);

    $count = FinancialRecord::where('reference_type', PurchaseOrderPayment::class)
        ->where('reference_id', $payment->id)
        ->count();

    expect($count)->toBe(1);
});

test('P1.2-002: Posting PO Payment idempotent (updateOrCreate, bukan create)', function () {
    $invoice = mkPurchaseInvoice();
    $po = $invoice->purchaseOrder;

    $payment = PurchaseOrderPayment::create([
        'purchase_order_id' => $po->id,
        'purchase_invoice_id' => $invoice->id,
        'payment_number' => 'PAYPI-' . uniqid(),
        'payment_date' => now()->toDateString(),
        'amount' => 1000000,
        'payment_method' => 'cash',
    ]);

    // Re-trigger sync → tidak boleh menambah jurnal kedua.
    $payment->created_at = now();
    $payment->save();

    $count = FinancialRecord::where('reference_type', PurchaseOrderPayment::class)
        ->where('reference_id', $payment->id)
        ->count();

    expect($count)->toBe(1);
});

test('P1.2-003: Menghapus PO payment menghapus jurnal HANYA payment itu', function () {
    $invoice = mkPurchaseInvoice();
    $po = $invoice->purchaseOrder;

    $p1 = PurchaseOrderPayment::create([
        'purchase_order_id' => $po->id,
        'purchase_invoice_id' => $invoice->id,
        'payment_number' => 'PAYPI-A-' . uniqid(),
        'payment_date' => now()->toDateString(),
        'amount' => 500000,
        'payment_method' => 'cash',
    ]);
    $p2 = PurchaseOrderPayment::create([
        'purchase_order_id' => $po->id,
        'purchase_invoice_id' => $invoice->id,
        'payment_number' => 'PAYPI-B-' . uniqid(),
        'payment_date' => now()->toDateString(),
        'amount' => 500000,
        'payment_method' => 'cash',
    ]);

    $p1->delete();

    // Jurnal p1 hilang, jurnal p2 tetap ada (tidak ikut terhapus).
    expect(FinancialRecord::where('reference_type', PurchaseOrderPayment::class)
        ->where('reference_id', $p1->id)->count())->toBe(0);
    expect(FinancialRecord::where('reference_type', PurchaseOrderPayment::class)
        ->where('reference_id', $p2->id)->count())->toBe(1);
});

test('P1.2-004: PO payment menautkan ke PurchaseInvoice (kolom purchase_invoice_id ada)', function () {
    $invoice = mkPurchaseInvoice();
    $po = $invoice->purchaseOrder;

    $payment = PurchaseOrderPayment::create([
        'purchase_order_id' => $po->id,
        'purchase_invoice_id' => $invoice->id,
        'payment_number' => 'PAYPI-' . uniqid(),
        'payment_date' => now()->toDateString(),
        'amount' => 2000000,
        'payment_method' => 'transfer',
    ]);

    expect($payment->purchase_invoice_id)->toBe($invoice->id);
    expect($payment->purchaseInvoice->id)->toBe($invoice->id);
    expect($payment->purchaseOrder->id)->toBe($po->id);
});
