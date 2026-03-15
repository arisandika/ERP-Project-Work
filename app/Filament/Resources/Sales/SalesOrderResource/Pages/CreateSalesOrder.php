<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Services\Sales\SalesOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    public function getTitle(): string { return 'Buat Pesanan'; }

    public function mount(): void
    {
        parent::mount();
        $this->form->fill([
            'order_number'   => $this->generateOrderNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'order_date'     => now()->toDateString(),
        ]);
    }

    // Override handleRecordCreation untuk menggunakan Service
    protected function handleRecordCreation(array $data): Model
    {
        $data['order_number'] = $this->generateOrderNumber();

        // Gunakan Service untuk create order dan items
        return app(SalesOrderService::class)->createOrder($data);
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record->status === 'confirmed') {
            try {
                // Panggil Service Logic untuk konfirmasi
                app(SalesOrderService::class)->processConfirmation($record, auth()->id());

                Notification::make()->title('Pesanan Confirmed & Stok Di-booking!')->success()->send();
            } catch (\Exception $e) {
                Notification::make()->title('Gagal: ' . $e->getMessage())->danger()->persistent()->send();
                // Jika gagal konfirmasi, mungkin perlu rollback status atau delete?
                // Disini kita biarkan record ada tapi user tau errornya.
            }
        } else {
            Notification::make()->title('Pesanan Draft Tersimpan')->info()->send();
        }
    }

    private function generateOrderNumber(): string {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $prefix = "%/SO/NEX/{$roman}/" . now()->year;
        $last = SalesOrder::withTrashed()->where('order_number', 'like', $prefix)->orderByDesc('id')->value('order_number');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/SO/NEX/{$roman}/" . now()->year;
    }
}
