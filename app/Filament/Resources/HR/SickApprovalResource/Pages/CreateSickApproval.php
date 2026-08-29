<?php

namespace App\Filament\Resources\HR\SickApprovalResource\Pages;

use App\Filament\Resources\HR\SickApprovalResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSickApproval extends CreateRecord
{
    protected static string $resource = SickApprovalResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}