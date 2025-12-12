<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    /**
     * 1. Mengisi form saat dibuka (Visual)
     */
    protected function getFormDefaults(): array
    {
        return [
            'order_number'   => $this->generateOrderNumber(),
            'order_date'     => now(),
            // Pastikan relasi user->employee ada. Jika null, field kosong (tapi field-nya disabled/locked).
            'nx_employee_id' => auth()->user()?->employee?->id,
        ];
    }

    /**
     * 2. Generate ulang saat tombol Simpan ditekan (Backend)
     * Untuk mencegah duplikat jika ada 2 user input bersamaan.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = $this->generateOrderNumber();
        return $data;
    }

    /**
     * 3. Logic Generator: Reset Tahunan
     * Format: 0001/SO/NEX/XII/2025
     */
    private function generateOrderNumber(): string
    {
        $roman   = $this->getRomanMonth(now()->month);
        $year    = now()->year;
        $company = 'NEX';
        $code    = 'SO';

        // Suffix Tampilan: /SO/NEX/XII/2025
        $visualSuffix = "/{$code}/{$company}/{$roman}/{$year}";

        // Logic Cari Nomor Terakhir (Abaikan Bulan, Cek Tahun Saja)
        // Pola Query: %/SO/NEX/%/2025
        $searchPattern = "%/{$code}/{$company}/%/{$year}";

        $lastOrder = SalesOrder::query()
            ->where('order_number', 'like', $searchPattern)
            ->orderByDesc('id')
            ->value('order_number');

        $seq = 1;
        if ($lastOrder) {
            $parts = explode('/', $lastOrder);
            // Ambil angka depan (index 0)
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $seq = (int) $parts[0] + 1;
            }
        }

        // Padding 4 digit (0001) agar muat sampai 9999 order/tahun
        $seqStr = str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        return "{$seqStr}{$visualSuffix}";
    }

    /**
     * Helper Romawi
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
