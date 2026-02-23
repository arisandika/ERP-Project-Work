<?php

namespace App\Filament\Resources\Marketing\SliderResource\Pages;

use App\Filament\Resources\Marketing\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSlider extends EditRecord
{
    protected static string $resource = SliderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
