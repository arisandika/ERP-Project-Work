<?php

namespace App\Filament\Resources\Finance\ReimbursementApprovalResource\Pages;

use App\Filament\Resources\Finance\ReimbursementApprovalResource;
use App\Models\Finance\ReimbursementRequest;
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
                ->color('warning')
                ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),

            Action::make('Kembali')
                ->url(static::getResource()::getUrl())
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Pengajuan Reimburse';
    }
}
