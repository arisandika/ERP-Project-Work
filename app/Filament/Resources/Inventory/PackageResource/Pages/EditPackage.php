<?php

namespace App\Filament\Resources\Inventory\PackageResource\Pages;

use App\Filament\Resources\Inventory\PackageResource;
use App\Services\Inventory\PackageStockValidationService;
use App\Services\Inventory\PackageStockReservationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    protected array $validatedItems = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Paket';
    }

    // Isi repeater manual saat form dibuka, karena 'items' bukan lagi relationship-bound
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items()
            ->get()
            ->map(fn ($item) => [
                'item_type' => $item->item_type,
                'item_id'   => $item->item_id,
                'quantity'  => $item->quantity,
                'price'     => $item->price,
                'subtotal'  => $item->subtotal,
            ])
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        try {
            $this->validatedItems = app(PackageStockValidationService::class)
                ->normalizeAndValidate($data['items'] ?? [], $this->record);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Stock product tidak mencukupi')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->send();

            throw $exception;
        }

        // 'items' bukan kolom tabel packages, jangan ikut di-update ke Model::update()
        unset($data['items']);

        return $data;
    }

    protected function handleRecordUpdate($record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            $record->update($data);

            // Strategi paling simpel & aman: hapus semua item lama, insert ulang yang baru
            $record->items()->delete();

            foreach ($this->validatedItems as $item) {
                $record->items()->create([
                    'item_type' => $item['item_type'],
                    'item_id'   => $item['item_id'],
                    'quantity'  => $item['quantity'],
                    'price'     => $item['price'] ?? 0,
                    'subtotal'  => $item['subtotal'] ?? 0,
                ]);
            }

            app(PackageStockReservationService::class)->sync($record, $this->validatedItems);

            return $record;
        });
    }

    protected function onValidationError(ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Package gagal disimpan')
            ->body(implode(' ', $exception->validator->errors()->all()))
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}