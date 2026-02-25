<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateDeal extends CreateRecord
{
    protected static string $resource = DealResource::class;

    public function getTitle(): string
    {
        return 'Tambah Deal';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'deal_number' => $this->generateDealNumber(),
            'status' => 'open', // default
        ]);
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();

        $targetStage = DealStage::find($data['nx_deal_stage_id']);

        if (!$targetStage) {
            return;
        }

        $stageName = strtolower($targetStage->name);

        $isWonStage = str_contains($stageName, 'won');
        $isLostStage = str_contains($stageName, 'lost');

        // Cari stage Penawaran
        $penawaranStage = DealStage::whereRaw(
            'LOWER(name) LIKE ?',
            ['%penawaran%']
        )->first();

        $penawaranProbability = $penawaranStage?->probability ?? 0;

        // VALIDASI STAGE = Deal baru belum punya quotation
        if ($targetStage->probability >= $penawaranProbability || $isWonStage) {

            Notification::make()
                ->title('Gagal Menambahkan Deal')
                ->body('Deal baru tidak bisa memiliki stage Penawaran atau di atasnya.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'nx_deal_stage_id' => 'Stage tidak valid',
            ]);
        }

        // VALIDASI STATUS
        if (in_array($data['status'], ['won', 'lost'])) {

            Notification::make()
                ->title('Gagal Memperbarui Deal')
                ->body('Deal baru tidak boleh langsung berstatus Won atau Lost.')
                ->danger()
                ->send();

            throw ValidationException::withMessages([
                'status' => 'Status tidak valid',
            ]);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['deal_number'] = $this->generateDealNumber();
        $data['status'] = 'open'; // paksa selalu open

        return $data;
    }

    private function generateDealNumber(): string
    {
        do {
            $dealNumber = 'DEAL-' . strtoupper(Str::random(4));
        } while (
            Deal::where('deal_number', $dealNumber)->exists()
        );

        return $dealNumber;
    }
}