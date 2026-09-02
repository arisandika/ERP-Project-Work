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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = Invoice::generateInvoiceNumber();

        $items = $data['items'] ?? [];

        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);

            return $qty * $price;
        });

        $discount = min((float) ($data['discount'] ?? 0), $subtotal);
        $tax = max(0, min((float) ($data['tax'] ?? 0), 100));
        $afterDiscount = $subtotal - $discount;
        $grandTotal = $afterDiscount + ($afterDiscount * ($tax / 100));

        $data['subtotal'] = round($subtotal, 2);
        $data['discount'] = round($discount, 2);
        $data['tax'] = round($tax, 2);
        $data['grand_total'] = round($grandTotal, 2);
        $data['total_paid'] = 0;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = Invoice::generateInvoiceNumber();

            $items = $data['items'] ?? [];
            unset($data['items']);

            /** @var Invoice $invoice */
            $invoice = static::getModel()::create($data);

            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);

                $invoice->items()->create([
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                    'item_code' => $item['item_code'] ?? null,
                    'item_name' => $item['item_name'] ?? null,
                    'qty' => (int) $qty,
                    'unit_price' => round($price, 2),
                    'line_total' => round($qty * $price, 2),
                ]);
            }

            app(InvoiceStockService::class)->process($invoice->load('items'));

            return $invoice;
        });
    }
}
