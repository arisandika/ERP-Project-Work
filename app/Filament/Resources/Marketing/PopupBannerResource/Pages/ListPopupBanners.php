<?php

namespace App\Filament\Resources\Marketing\PopupBannerResource\Pages;

use App\Filament\Resources\Marketing\PopupBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPopupBanners extends ListRecords
{
    protected static string $resource = PopupBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Popup & Banner'),
        ];
    }
}
