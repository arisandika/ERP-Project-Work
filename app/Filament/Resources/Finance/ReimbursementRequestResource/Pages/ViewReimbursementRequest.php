<?php

namespace App\Filament\Resources\Finance\ReimbursementRequestResource\Pages;

use App\Filament\Resources\Finance\ReimbursementRequestResource;
use App\Models\Finance\ReimbursementRequest;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewReimbursementRequest extends ViewRecord
{
    protected static string $resource = ReimbursementRequestResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        
        if (auth()->user()->hasRole('super_admin')) {
            abort(403, 'Super Admin tidak memiliki akses pengajuan reimburse');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending')
                ->requiresConfirmation()
                ->action(function ($record) {
                    $employeeId = auth()->user()?->employee?->id;

                    if (!$record->canBeCancelledBy($employeeId)) {
                        abort(403);
                    }

                    $record->cancel();
                }),

            Actions\EditAction::make()
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
