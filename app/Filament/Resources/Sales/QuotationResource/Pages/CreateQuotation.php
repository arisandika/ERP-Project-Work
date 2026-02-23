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

        // form->fill() digunakan untuk mengisi nilai default saat halaman dimuat
        $this->form->fill([
            // [TAMBAHAN] Tangkap parameter dari URL jika ada
            'nx_deal_id'       => request()->query('nx_deal_id'),
            'nx_customer_id'   => request()->query('nx_customer_id'),

            'quotation_number' => $this->generateQuotationNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,

            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'created_by_employee_id' => auth()->user()?->employee?->id,

            'nx_lead_id' => (int) request('nx_lead_id') ?? null,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['quotation_number'] = $this->generateQuotationNumber();

        $data['quotation_date'] =
            Carbon::parse($data['quotation_date'])->setTimeFrom(now());

        $data['valid_until'] =
            Carbon::parse($data['valid_until'])->endOfDay();

        $data['created_by_employee_id'] = auth()->user()->employee->id;

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
