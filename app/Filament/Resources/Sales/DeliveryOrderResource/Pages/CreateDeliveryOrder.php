<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    protected function getFormDefaults(): array
    {
        return [
            'do_number'      => $this->generateDoNumber(),
            'delivery_date'  => now(),
            // 'nx_employee_id' => auth()->user()?->employee?->id, // Jika ada field driver/staff gudang
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['do_number'] = $this->generateDoNumber();
        return $data;
    }

    /**
     * Logic Generator: Reset Tahunan
     * Format: 0001/DO/NEX/XII/2025
     */
    private function generateDoNumber(): string
    {
        $roman   = $this->getRomanMonth(now()->month);
        $year    = now()->year;
        $company = 'NEX';
        $code    = 'DO'; // Atau ganti 'SJ' jika mau Surat Jalan

        // Tampilan: /DO/NEX/XII/2025
        $visualSuffix = "/{$code}/{$company}/{$roman}/{$year}";

        // Cari nomor terakhir di TAHUN INI (abaikan bulan)
        // Pola: %/DO/NEX/%/2025
        $searchPattern = "%/{$code}/{$company}/%/{$year}";

        $lastDo = DeliveryOrder::query()
            ->where('do_number', 'like', $searchPattern)
            ->orderByDesc('id')
            ->value('do_number');

        $seq = 1;
        if ($lastDo) {
            $parts = explode('/', $lastDo);
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $seq = (int) $parts[0] + 1;
            }
        }

        // Padding 4 digit (0001)
        $seqStr = str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        return "{$seqStr}{$visualSuffix}";
    }

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
