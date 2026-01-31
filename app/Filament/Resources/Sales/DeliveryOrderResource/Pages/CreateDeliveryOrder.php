<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    public function getTitle(): string
    {
        return 'Buat Surat Jalan';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'do_number'   => $this->generateDeliveryNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'do_date'     => now()->toDateString(),
        ]);
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['do_number'] = $this->generateDeliveryNumber();
        return $data;
    }

    private function generateDeliveryNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'DO';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\DeliveryOrder::withTrashed()
            ->where('do_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('do_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
