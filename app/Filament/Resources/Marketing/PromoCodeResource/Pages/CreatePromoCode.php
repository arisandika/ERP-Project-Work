<?php
namespace App\Filament\Resources\Marketing\PromoCodeResource\Pages;

use App\Filament\Resources\Marketing\PromoCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePromoCode extends CreateRecord
{
    protected static string $resource = PromoCodeResource::class;

    public function getTitle(): string
    {
        return 'Tambah Kode Promo';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
