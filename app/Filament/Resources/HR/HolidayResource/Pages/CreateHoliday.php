<?php

namespace App\Filament\Resources\HR\HolidayResource\Pages;

use App\Filament\Resources\HR\HolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateHoliday extends CreateRecord
{
    protected static string $resource = HolidayResource::class;

    public function getTitle(): string
    {
        return 'Tambah Hari Libur';
    }
}
