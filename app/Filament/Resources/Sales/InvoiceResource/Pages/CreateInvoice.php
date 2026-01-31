<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

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
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();
        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        // Pisahkan items dari data utama
        $items = $data['items'] ?? [];
        unset($data['items']);

        // Buat invoice dulu (parent record)
        $invoice = static::getModel()::create($data);

        // Setelah invoice punya ID, baru save items
        if (!empty($items)) {
            foreach ($items as $item) {
                $invoice->items()->create([
                    'item_type' => $item['item_type'] ?? null,
                    'item_id' => $item['item_id'] ?? null,
                    'item_code' => $item['item_code'] ?? null,
                    'item_name' => $item['item_name'] ?? null,
                    'qty' => $item['qty'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => $item['line_total'] ?? 0,
                ]);
            }
        }

        return $invoice;
    }

    private function generateInvoiceNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'INV';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\Invoice::withTrashed()
            ->where('invoice_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
