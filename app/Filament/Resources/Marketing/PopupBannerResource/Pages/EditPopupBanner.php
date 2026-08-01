<?php
namespace App\Filament\Resources\Marketing\PopupBannerResource\Pages;

use App\Filament\Resources\Marketing\PopupBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPopupBanner extends EditRecord
{
    protected static string $resource = PopupBannerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Popup & Banner';
    }
    
    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
