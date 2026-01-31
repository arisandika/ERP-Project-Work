<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\Action;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    public function getTitle(): string
    {
        return 'Edit Penawaran';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn ($record) => $record && in_array($record->status, ['draft','sent']))
                ->requiresConfirmation()
                ->action(function ($record) {
                    $user = Auth::user();
                    $record->update([
                        'status' => 'accepted',
                        'approved_by_user_id' => $user?->id,
                        'approved_by_employee_id' => $user?->employee?->id,
                        'approved_at' => now(),
                    ]);
                }),
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('reason')->label('Alasan')->required(),
                ])
                ->visible(fn ($record) => $record && in_array($record->status, ['draft','sent']))
                ->action(function (array $data, $record) {
                    $user = Auth::user();
                    $record->update([
                        'status' => 'rejected',
                        'approved_by_user_id' => $user?->id,
                        'approved_by_employee_id' => $user?->employee?->id,
                        'approved_at' => now(),
                        'notes' => trim(($record->notes ? $record->notes."\n" : '')."Rejected: ".$data['reason']),
                    ]);
                }),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {

        if ($this->record->status !== 'accepted' && $data['status'] === 'accepted') {
            $user = auth()->user();
            $data['approved_by_user_id'] = $user?->id;
            $data['approved_by_employee_id'] = $user?->employee?->id;
            $data['approved_at'] = now();
        }

        return $data;
    }
}
