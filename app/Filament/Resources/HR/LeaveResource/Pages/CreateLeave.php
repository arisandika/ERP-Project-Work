<?php

namespace App\Filament\Resources\HR\LeaveResource\Pages;

use App\Filament\Resources\HR\LeaveResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLeave extends CreateRecord
{
    protected static string $resource = LeaveResource::class;

    public function getTitle(): string
    {
        return 'Tambah Jenis Cuti';
    }
}
