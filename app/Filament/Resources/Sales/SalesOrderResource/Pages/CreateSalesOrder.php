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
            'order_number'   => SalesOrder::generateOrderNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'order_date'     => now()->toDateString(),
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $data['order_number'] = SalesOrder::generateOrderNumber();

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
}
