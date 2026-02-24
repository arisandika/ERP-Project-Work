<?php

namespace App\Filament\Resources\CRM\DealStageResource\Pages;

use App\Filament\Resources\CRM\DealStageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDealStage extends EditRecord
{
    protected static string $resource = DealStageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Stage Deal';
    }
}
