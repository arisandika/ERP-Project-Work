<?php

namespace App\Filament\Resources\HR\LeaveApprovalResource\Pages;

use App\Filament\Resources\HR\LeaveApprovalResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeaveApproval extends CreateRecord
{
    protected static string $resource = LeaveApprovalResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
