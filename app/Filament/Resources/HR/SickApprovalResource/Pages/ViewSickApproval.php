<?php

namespace App\Filament\Resources\HR\SickApprovalResource\Pages;

use App\Filament\Resources\HR\SickApprovalResource;
use App\Models\HR\SickRequest;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewSickApproval extends ViewRecord
{
    protected static string $resource = SickApprovalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Review')
                ->color('warning')
                ->visible(fn(SickRequest $record) => $record->status === 'pending'),

            Action::make('Kembali')
                ->url(static::getResource()::getUrl())
                ->button()
                ->color('gray'),
        ];
    }

    public function getTitle(): string
    {
        return 'Lihat Pengajuan Sakit';
    }
}