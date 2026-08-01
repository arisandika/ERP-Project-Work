<?php

namespace App\Filament\Resources\Marketing\SliderResource\Pages;

use App\Filament\Resources\Marketing\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSlider extends CreateRecord
{
    protected static string $resource = SliderResource::class;

    public function getTitle(): string
    {
        return 'Tambah Slider';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
