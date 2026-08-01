<?php
namespace App\Filament\Resources\Marketing\PopupBannerResource\Pages;

use App\Filament\Resources\Marketing\PopupBannerResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePopupBanner extends CreateRecord
{
    protected static string $resource = PopupBannerResource::class;

    public function getTitle(): string
    {
        return 'Tambah Popup & Banner';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
