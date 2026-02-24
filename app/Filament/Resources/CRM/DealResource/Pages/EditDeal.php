<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\DealStage;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeal extends EditRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Deal';
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $stage = DealStage::find($data['nx_deal_stage_id']);
        $stageName = strtolower($stage->name);

        // STATUS → STAGE
        if ($data['status'] === 'won') {
            $closedStage = DealStage::where('name', 'like', '%Closed Won%')->first();
            if ($closedStage) {
                $data['nx_deal_stage_id'] = $closedStage->id;
            }
            $data['close_date'] = now();
        }

        if ($data['status'] === 'lost') {
            $closedStage = DealStage::where('name', 'like', '%Closed Lost%')->first();
            if ($closedStage) {
                $data['nx_deal_stage_id'] = $closedStage->id;
            }
            $data['close_date'] = now();
        }

        if ($data['status'] === 'open') {
            $data['close_date'] = null;
        }

        return $data;
    }
}
