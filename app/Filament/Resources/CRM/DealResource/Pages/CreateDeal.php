<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Models\HR\Employee;
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
            'status' => Deal::STATUS_OPEN,
            'deal_date' => now()->toDateString(),
            'created_by' => Employee::where('user_id', auth()->id())->value('id'),
        ]);
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $targetStage = DealStage::find($data['nx_deal_stage_id']);

        if (!$targetStage)
            return;

        $stageName = strtolower($targetStage->name);
        $isWonStage = str_contains($stageName, 'won');
        $isLostStage = str_contains($stageName, 'lost');

        $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
        $penawaranProbability = $penawaranStage?->probability ?? 0;

        if ($targetStage->probability >= $penawaranProbability || $isWonStage) {
            Notification::make()
                ->title('Gagal Menambahkan Deal')
                ->body('Deal baru tidak bisa memiliki stage Penawaran atau di atasnya.')
                ->danger()
                ->send();

            throw ValidationException::withMessages(['nx_deal_stage_id' => 'Stage tidak valid']);
        }

        if (in_array($data['status'], [Deal::STATUS_CLOSED_WON, Deal::STATUS_CLOSED_LOST])) {
            Notification::make()
                ->title('Gagal Memperbarui Deal')
                ->body('Deal baru tidak boleh langsung berstatus Won atau Lost.')
                ->danger()
                ->send();

            throw ValidationException::withMessages(['status' => 'Status tidak valid']);
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['deal_number'] = $this->generateDealNumber();
        $data['status'] = Deal::STATUS_OPEN; // Paksa selalu open

        // (Opsional) Memaksa stage ke "Kualifikasi" jika form tidak mengirimkannya dengan benar
        $kualifikasiStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%kualifikasi%'])->first();
        if ($kualifikasiStage && empty($data['nx_deal_stage_id'])) {
            $data['nx_deal_stage_id'] = $kualifikasiStage->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $deal = $this->record;

        // 1. Ubah status Lead menjadi Contacted
        if ($deal->nx_lead_id) {
            $lead = Lead::find($deal->nx_lead_id);
            if ($lead && $lead->status === Lead::STATUS_NEW) {
                $lead->update(['status' => Lead::STATUS_CONTACTED]);
            }
        }
    }

    private function generateDealNumber(): string
    {
        do {
            $dealNumber = 'DEAL-' . strtoupper(Str::random(4));
        } while (Deal::where('deal_number', $dealNumber)->exists());

        return $dealNumber;
    }
}