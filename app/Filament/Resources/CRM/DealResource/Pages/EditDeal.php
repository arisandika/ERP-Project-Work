<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Actions;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditDeal extends EditRecord
{
    protected static string $resource = DealResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make()
                ->before(function (RestoreAction $action, Deal $record) {
                    // Ambil parent lead-nya (meskipun lead sedang soft-deleted)
                    $lead = $record->lead()->withTrashed()->first();

                    if ($lead && $lead->trashed()) {
                        Notification::make()
                            ->warning()
                            ->title('Gagal Restore Deal')
                            ->body('Silakan restore Lead terkait terlebih dahulu!')
                            ->send();

                        $action->cancel(); // Batalkan proses restore
                    }
                }),
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
