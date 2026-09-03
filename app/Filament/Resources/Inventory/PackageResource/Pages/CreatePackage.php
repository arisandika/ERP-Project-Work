<?php

namespace App\Filament\Resources\Inventory\PackageResource\Pages;

use App\Filament\Resources\Inventory\PackageResource;
use App\Models\Inventory\PackageItem; // sesuaikan namespace model item kamu
use App\Services\Inventory\PackageStockValidationService;
use App\Services\Inventory\PackageStockReservationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    protected array $validatedItems = [];

    public function getTitle(): string
    {
        return 'Tambah Paket';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $this->validatedItems = app(PackageStockValidationService::class)
                ->normalizeAndValidate($data['items'] ?? []);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Stock product tidak mencukupi')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->send();

            throw $exception;
        }

        // 'items' bukan kolom di tabel packages, jangan ikut dikirim ke Model::create()
        unset($data['items']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return DB::transaction(function () use ($data) {
            $record = static::getModel()::create($data);

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