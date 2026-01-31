<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    public function getTitle(): string
    {
        return 'Buat Penawaran';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'quotation_number' => $this->generateQuotationNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,

            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Pastikan nomor selalu benar & konsisten
        $data['quotation_number'] = $this->generateQuotationNumber();

        $data['created_by_user_id'] = auth()->id();
        $data['created_by_employee_id'] = auth()->user()?->employee?->id;

        $data['quotation_date'] =
            Carbon::parse($data['quotation_date'])->setTimeFrom(now());

        $data['valid_until'] =
            Carbon::parse($data['valid_until'])->endOfDay();

        return $data;
    }

    private function generateQuotationNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'QP';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\Quotation::withTrashed()
            ->where('quotation_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('quotation_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
