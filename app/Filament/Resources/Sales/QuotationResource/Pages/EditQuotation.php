<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\DealStage;
use Filament\Actions;
use Filament\Notifications\Notification;
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
            // ACTION APPROVE (WON)
            Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => !in_array($record->status, ['accepted', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('Approve Penawaran')
                ->modalDescription('Apakah Anda yakin? Status Deal akan otomatis menjadi WON.')
                ->action(function ($record) {
                    $user = Auth::user();

                    // 1. Update Quotation jadi Accepted
                    $record->update([
                        'status' => 'accepted',
                        'approved_by' => $user?->employee?->id,
                        'approved_at' => now(),
                    ]);

                    // 2. Update Parent Deal jadi Won
                    if ($record->nx_deal_id) {
                        $deal = $record->deal; // Asumsi relasi sudah ada
        
                        // Cari Stage 'Closed Won' atau 'Won'
                        $wonStage = DealStage::where('name', 'like', '%Won%')->first();

                        if ($deal) {
                            $deal->update([
                                'status' => 'won',
                                'nx_deal_stage_id' => $wonStage?->id, // Pindah stage
                                'close_date' => now(), // Set tanggal closing
                            ]);

                            Notification::make()
                                ->title('Deal Won!')
                                ->body("Deal {$deal->deal_number} berhasil ditutup (Won).")
                                ->success()
                                ->send();
                        }
                    }
                }),

            // ACTION REJECT (LOST)
            Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->required(),
                ])
                // Perbaikan logic visibility
                ->visible(fn($record) => !in_array($record->status, ['accepted', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('Reject Penawaran')
                ->modalDescription('Apakah Anda yakin? Status Deal akan otomatis menjadi LOST.')
                ->action(function (array $data, $record) {
                    $user = Auth::user();

                    // 1. Update Quotation jadi Rejected
                    $record->update([
                        'status' => 'rejected',
                        'approved_by' => $user?->employee?->id,
                        'approved_at' => now(),
                        'notes' => trim(($record->notes ? $record->notes . "\n" : '') . "Alasan Rejected: " . $data['reason']),
                    ]);

                    // 2. Update Parent Deal jadi Lost
                    if ($record->nx_deal_id) {
                        $deal = $record->deal;

                        // Cari Stage 'Closed Lost' atau 'Lost'
                        $lostStage = DealStage::where('name', 'like', '%Lost%')->first();

                        if ($deal) {
                            $deal->update([
                                'status' => 'lost',
                                'nx_deal_stage_id' => $lostStage?->id, // Pindah stage
                                'close_date' => now(), // Set tanggal closing
                            ]);

                            Notification::make()
                                ->title('Deal Lost')
                                ->body("Deal {$deal->deal_number} ditandai sebagai Lost.")
                                ->danger()
                                ->send();
                        }
                    }
                }),

            Actions\DeleteAction::make(),
            // Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {

        if ($this->record->status !== 'accepted' && $data['status'] === 'accepted') {
            $user = auth()->user();
            $data['approved_by'] = $user?->employee?->id;
            $data['approved_at'] = now();
        }

        return $data;
    }
}
