<?php

namespace App\Filament\Resources\Inventory\PackageResource\Pages;

use App\Filament\Resources\Inventory\PackageResource;
use App\Services\Inventory\PackageStockValidationService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreatePackage extends CreateRecord
{
    protected static string $resource = PackageResource::class;

    public function getTitle(): string
    {
        return 'Tambah Paket';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        try {
            $data['items'] = app(PackageStockValidationService::class)
                ->normalizeAndValidate($data['items'] ?? []);
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Stock product tidak mencukupi')
                ->body(implode(' ', $exception->validator->errors()->all()))
                ->send();

            throw $exception;
        }

        return $data;
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
