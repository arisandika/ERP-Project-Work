<?php

namespace App\Filament\Resources\Marketing\PopupBannerResource\Pages;

use App\Filament\Resources\Marketing\PopupBannerResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreatePopupBanner extends CreateRecord
{
    protected static string $resource = PopupBannerResource::class;

    public function getTitle(): string
    {
        return 'Tambah Popup & Banner';
    }
}
