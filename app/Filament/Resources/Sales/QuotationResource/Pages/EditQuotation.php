<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Services\Sales\QuotationService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

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
            Actions\Action::make('approve')
                ->label('Approve')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn($record) => !in_array($record->status, ['accepted', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('Approve Penawaran')
                ->modalDescription('Apakah Anda yakin? Lead akan terkonversi otomatis dan status Deal menjadi WON.')
                ->action(function ($record, QuotationService $service) {
                    $user = Auth::user();
                    $service->approveQuotation($record, $user->id, $user?->employee?->id);
                }),

            // ACTION REJECT (LOST)
            Actions\Action::make('reject')
                ->label('Reject')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->label('Alasan Penolakan')
                        ->placeholder('Contoh: Harga tidak masuk budget, Klien pilih vendor lain...')
                        ->required(),
                ])
                ->visible(fn($record) => !in_array($record->status, ['accepted', 'rejected']))
                ->requiresConfirmation()
                ->modalHeading('Reject Penawaran')
                ->modalDescription('Apakah Anda yakin? Status Deal akan otomatis menjadi LOST.')
                ->action(function (array $data, $record, QuotationService $service) {
                    $user = Auth::user();
                    $service->rejectQuotation($record, $user?->employee?->id, $data['reason']);
                }),

            Actions\DeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['items'] = $this->record->items->toArray();
        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // Jika user secara manual mengubah status dropdown menjadi "accepted" via Form edit
        if ($record->status !== 'accepted' && ($data['status'] ?? $record->status) === 'accepted') {
            $user = auth()->user();
            $data['approved_by'] = $user?->employee?->id;
            $data['approved_at'] = now();
        }

        return app(QuotationService::class)->updateQuotation($record, $data);
    }
}
