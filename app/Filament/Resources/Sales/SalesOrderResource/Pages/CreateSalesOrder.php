<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Services\Sales\SalesOrderService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

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

    protected function handleRecordCreation(array $data): Model
    {
        $data['order_number'] = $this->generateOrderNumber();

        return DB::transaction(function () use ($data) {
            $service = app(SalesOrderService::class);

            // 1. Buat SO dulu di dalam transaction
            $record = $service->createOrder($data);

            // 2. Kalau status confirmed, langsung proses booking stok + auto DO
            if (($record->status ?? null) === 'confirmed') {
                $service->processConfirmation($record, auth()->id());
            }

            // Kalau processConfirmation gagal, semua rollback
            return $record;
        });
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record->status === 'confirmed') {
            Notification::make()
                ->title('Pesanan Confirmed & Stok Di-booking!')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Pesanan Draft Tersimpan')
                ->info()
                ->send();
        }
    }

    protected function onValidationError(\Throwable $exception): void
    {
        Notification::make()
            ->title('Gagal membuat Sales Order')
            ->body($exception->getMessage())
            ->danger()
            ->persistent()
            ->send();
    }

    private function generateOrderNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $prefix = "%/SO/NEX/{$roman}/" . now()->year;
        $last = SalesOrder::withTrashed()
            ->where('order_number', 'like', $prefix)
            ->orderByDesc('id')
            ->value('order_number');

        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;

        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/SO/NEX/{$roman}/" . now()->year;
    }
}
