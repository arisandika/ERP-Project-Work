<?php
namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    public function getTitle(): string
    {
        return 'Buat Pesanan';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'order_number'   => $this->generateOrderNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'order_date'     => now()->toDateString(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = $this->generateOrderNumber();
        return $data;
    }

    private function generateOrderNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'SO';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\SalesOrder::withTrashed()
            ->where('order_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('order_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
