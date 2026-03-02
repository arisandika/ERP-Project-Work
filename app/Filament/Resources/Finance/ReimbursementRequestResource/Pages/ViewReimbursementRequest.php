<?php

namespace App\Filament\Resources\Finance\ReimbursementRequestResource\Pages;

use App\Filament\Resources\Finance\ReimbursementRequestResource;
use App\Models\Finance\ReimbursementRequest;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
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

                    // Employee tidak ditemukan
                    if (!$employeeId) {
                        Notification::make()
                            ->title('Data karyawan tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Bukan milik sendiri
                    if ($record->employee_id !== $employeeId) {
                        Notification::make()
                            ->title('Anda tidak bisa membatalkan pengajuan orang lain')
                            ->danger()
                            ->send();

                        return;
                    }

                    // Status tidak valid
                    if (!in_array($record->status, ['pending', 'approved'])) {
                        Notification::make()
                            ->title('Status pengajuan tidak bisa dibatalkan')
                            ->warning()
                            ->send();

                        return;
                    }

                    $record->update([
                        'status' => 'cancelled',
                    ]);

                    Notification::make()
                        ->title('Pengajuan berhasil dibatalkan')
                        ->success()
                        ->send();
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
