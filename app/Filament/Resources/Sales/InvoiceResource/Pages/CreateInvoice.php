<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * 1. FORM DEFAULTS
     * Mengisi nomor otomatis saat form pertama kali dibuka (hanya visual).
     */
    protected function getFormDefaults(): array
    {
        return [
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date'   => now(),
            'nx_employee_id' => auth()->user()?->employee?->id,
        ];
    }

    /**
     * 2. MUTATE BEFORE CREATE
     * Wajib generate ulang nomor sesaat sebelum simpan ke DB.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();
        return $data;
    }

    /**
     * 3. OVERRIDE HANDLE RECORD CREATION
     * Ini yang WAJIB ditambahin buat save items!
     */
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
                    'item_type'  => $item['item_type'] ?? null,
                    'item_id'    => $item['item_id'] ?? null,
                    'item_code'  => $item['item_code'] ?? null,
                    'item_name'  => $item['item_name'] ?? null,
                    'qty'        => $item['qty'] ?? 0,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'line_total' => $item['line_total'] ?? 0,
                ]);
            }
        }

        return $invoice;
    }

    /**
     * 4. LOGIC GENERATOR (Format: 001/INV/NEX/XII/2025)
     */
    private function generateInvoiceNumber(): string
    {
        $roman   = $this->getRomanMonth(now()->month);
        $year    = now()->year;
        $company = 'NEX';
        $code    = 'INV';

        $suffix = "/{$code}/{$company}/{$roman}/{$year}";

        $lastInvoice = Invoice::query()
            ->where('invoice_number', 'like', '%' . $suffix)
            ->orderByDesc('id')
            ->value('invoice_number');

        $seq = 1;
        if ($lastInvoice) {
            $parts = explode('/', $lastInvoice);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $seq = (int) $parts[0] + 1;
            }
        }

        $seqStr = str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
        return "{$seqStr}{$suffix}";
    }

    /**
     * Helper ubah angka bulan ke Romawi
     */
    private function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
