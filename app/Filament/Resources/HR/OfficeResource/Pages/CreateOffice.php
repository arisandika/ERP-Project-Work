<?php

namespace App\Filament\Resources\HR\OfficeResource\Pages;

use App\Filament\Resources\HR\OfficeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOffice extends CreateRecord
{
    protected static string $resource = OfficeResource::class;

    public function getTitle(): string
    {
        return 'Tambah Kantor';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
