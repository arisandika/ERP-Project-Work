<?php

namespace App\Filament\Resources\Marketing\PopupBannerResource\Pages;

use App\Filament\Resources\Marketing\PopupBannerResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewPopupBanner extends ViewRecord
{
    protected static string $resource = PopupBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Action::make('back')
                ->url(static::getResource()::getUrl()) 
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Popup & Banner';
    }
}
