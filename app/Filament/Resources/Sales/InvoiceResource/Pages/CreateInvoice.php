<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
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
            'invoice_number' => $this->generateInvoiceNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'grand_total' => 0,
            'total_paid' => 0,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();

        $items = $data['items'] ?? [];
        $subtotal = collect($items)->sum(function ($item) {
            $qty = (float) ($item['qty'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);

            return $qty * $price;
        });

        $discount = min((float) ($data['discount'] ?? 0), $subtotal);
        $tax = (float) ($data['tax'] ?? 0); // persen
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

            return $invoice;
        });
    }

    private function generateInvoiceNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'INV';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) ($parts[0] ?? 0)) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
