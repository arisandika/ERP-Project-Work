<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Services\Sales\SalesOrderService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getTitle(): string { return 'Edit Pesanan (Sales Order)'; }

    // Override handleRecordUpdate untuk menggunakan Service
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->toArray();
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $newStatus = $data['status'] ?? $oldStatus;

        $service = app(SalesOrderService::class);

        try {
            // Gunakan Service untuk update order dan items
            $record = $service->updateOrder($record, $data);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Gagal Update Pesanan')
                ->body($e->getMessage())
                ->danger()
                ->persistent()
                ->send();
            $this->halt();
        }

        // LOGIKA KETIKA SO DI-CONFIRM
        if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
            try {
                $service->processConfirmation($record, auth()->id());
                Notification::make()->title('Sales Order Confirmed!')->body('Stok berhasil di-booking dan Surat Jalan otomatis dibuat.')->success()->send();
            } catch (\Exception $e) {
                Notification::make()->title('Gagal Konfirmasi: ' . $e->getMessage())->danger()->persistent()->send();
                // Kembalikan status ke draft jika gagal
                $record->update(['status' => 'draft']);
                $this->halt();
            }
        }

        return $record;
    }
}
