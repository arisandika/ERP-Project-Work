<?php

namespace App\Filament\Resources\Marketing\SliderResource\Pages;

use App\Filament\Resources\Marketing\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewSlider extends ViewRecord
{
    protected static string $resource = SliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
