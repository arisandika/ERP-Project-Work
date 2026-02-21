<?php

namespace App\Filament\Resources\HR\ReimbursementRequestResource\Pages;

use App\Filament\Resources\HR\ReimbursementRequestResource;
use App\Models\HR\ReimbursementRequest;
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
            Actions\EditAction::make('Edit Pengajuan Reimburse')
                ->visible(fn(ReimbursementRequest $record) => $record->status === 'pending'),
            Action::make('back')
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
