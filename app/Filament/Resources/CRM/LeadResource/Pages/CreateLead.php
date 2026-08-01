<?php
namespace App\Filament\Resources\CRM\LeadResource\Pages;

use App\Filament\Resources\CRM\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    public function getTitle(): string
    {
        return 'Tambah Lead';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
