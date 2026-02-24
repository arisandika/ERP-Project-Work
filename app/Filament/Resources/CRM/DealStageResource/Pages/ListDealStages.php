<?php

namespace App\Filament\Resources\CRM\DealStageResource\Pages;

use App\Filament\Resources\CRM\DealStageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDealStages extends ListRecords
{
    protected static string $resource = DealStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Stage Deal'),
        ];
    }
}
