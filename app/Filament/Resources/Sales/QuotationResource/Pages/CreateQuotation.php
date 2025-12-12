<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    /**
     * Mengatur nilai default yang tampil di form saat pertama kali dibuka.
     */
    protected function getFormDefaults(): array
    {
        return [
            'quotation_number' => $this->generateQuotationNumber(),
            'nx_employee_id'   => auth()->user()?->employee?->id,
        ];
    }

    /**
     * Memodifikasi data TEPAT SEBELUM disimpan ke database.
     * Ini adalah tempat yang tepat untuk memastikan semua data wajib ada.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Sisipkan Nomor Quotation ke dalam data yang akan disimpan
        $data['quotation_number'] = $this->generateQuotationNumber();

        // 2. Sisipkan data audit (siapa yang membuat)
        $data['created_by_user_id'] = auth()->id();
        $data['created_by_employee_id'] = auth()->user()?->employee?->id;

        // 3. Panggil fungsi untuk kalkulasi akhir
        return $data;
    }

    /**
     * Fungsi untuk generate nomor unik.
     */
    private function generateQuotationNumber(): string
    {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'QP';

        $prefixLike = "%/$code/$company/$roman/$year";
        $last = \App\Models\Sales\Quotation::query()
            ->where('quotation_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('quotation_number');

        $seq = 1;
        if ($last) {
            $parts = explode('/', $last);
            $seq = isset($parts[0]) ? ((int)$parts[0] + 1) : 1;
        }
        $seqStr = str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
