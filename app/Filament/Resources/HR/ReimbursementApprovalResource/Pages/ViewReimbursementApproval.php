<?php

namespace App\Filament\Resources\HR\ReimbursementApprovalResource\Pages;

use App\Filament\Resources\HR\ReimbursementApprovalResource;
use App\Models\HR\ReimbursementRequest;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewReimbursementApproval extends ViewRecord
{
    protected static string $resource = ReimbursementApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Review')
                ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),
            Action::make('back')->url(static::getResource()::getUrl())->button()->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Pengajuan Reimburse';
    }
}
