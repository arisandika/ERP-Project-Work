<?php

namespace App\Filament\Resources\Marketing\SliderResource\Pages;

use App\Filament\Resources\Marketing\SliderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSliders extends ListRecords
{
    protected static string $resource = SliderResource::class;

     protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Slider'),
        ];
    }
}
