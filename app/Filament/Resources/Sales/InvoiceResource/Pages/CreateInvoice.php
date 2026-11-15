<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use App\Services\Sales\InvoiceStockService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    public function getTitle(): string
    {
        return 'Buat Invoice';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'grand_total' => 0,
            'total_paid' => 0,
            'items' => [],
        ]);
    }

    protected function recalculateInvoiceTotals(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->items()
            ->selectRaw('COALESCE(SUM(qty * unit_price), 0) as total')
            ->value('total');

        $discount = min((float) ($invoice->discount ?? 0), $subtotal);
        $tax      = max(0, min((float) ($invoice->tax ?? 0), 100));

        $afterDiscount = $subtotal - $discount;

        $invoice->forceFill([
            'subtotal'    => round($subtotal, 2),
            'discount'    => round($discount, 2),
            'tax'         => round($tax, 2),
            'grand_total' => round($afterDiscount + ($afterDiscount * $tax / 100), 2),
        ])->saveQuietly();
    }

    protected function afterCreate(): void
    {
        $invoice = $this->getRecord();
        $this->recalculateInvoiceTotals($invoice);
        app(InvoiceStockService::class)->process($invoice->load('items'));
    }
}
