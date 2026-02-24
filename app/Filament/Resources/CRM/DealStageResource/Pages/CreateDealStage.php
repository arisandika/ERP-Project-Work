<?php

namespace App\Filament\Resources\CRM\DealStageResource\Pages;

use App\Filament\Resources\CRM\DealStageResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDealStage extends CreateRecord
{
    protected static string $resource = DealStageResource::class;

    public function getTitle(): string
    {
        return 'Tambah Stage Deal';
    }
}
