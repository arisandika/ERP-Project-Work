<?php

namespace App\Filament\Resources\CRM\DealResource\Pages;

use App\Filament\Resources\CRM\DealResource;
use App\Models\CRM\Deal;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

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
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['deal_number'] = $this->generateDealNumber();

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
