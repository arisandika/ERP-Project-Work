<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    public function getTitle(): string
    {
        return 'Buat Penawaran';
    }

    public function mount(): void
    {
        $dealId = request()->query('nx_deal_id');

        if ($dealId) {
            $deal = Deal::withTrashed()->find($dealId);

            if ($deal && $deal->trashed()) {

                Notification::make()
                    ->title('Akses Ditolak')
                    ->body("Deal {$deal->deal_number} sudah dihapus. Restore terlebih dahulu untuk membuat penawaran.")
                    ->danger()
                    ->persistent()
                    ->send();

                $this->redirect($this->getResource()::getUrl('index'));

                return;
            }
        }

        parent::mount();

        $this->form->fill([
            'created_by' => auth()->user()?->employee?->id,
            'nx_deal_id' => request()->query('nx_deal_id'),
            'quotation_number' => $this->generateQuotationNumber(),
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'draft',
            'tax' => 11,
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['quotation_number'] = $this->generateQuotationNumber();

        $data['quotation_date'] =
            Carbon::parse($data['quotation_date'])->setTimeFrom(now());

        $data['valid_until'] =
            Carbon::parse($data['valid_until'])->endOfDay();

        if (auth()->user()?->employee) {
            $data['created_by'] = auth()->user()->employee->id;
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $quotation = $this->record;

        if ($quotation && $quotation->nx_deal_id) {
            $deal = Deal::find($quotation->nx_deal_id);

            if ($deal) {
                $updateData = [];

                $penawaranStage = DealStage::where('name', 'like', '%Penawaran%')->first();

                if ($penawaranStage) {
                    $updateData['nx_deal_stage_id'] = $penawaranStage->id;
                }

                if ($deal->status === 'lost') {
                    $updateData['status'] = 'open';
                    $updateData['close_date'] = null;

                    Notification::make()
                        ->title('Deal Dibuka Kembali')
                        ->body("Status Deal {$deal->deal_number} otomatis berubah dari Lost menjadi Open.")
                        ->info()
                        ->send();
                }

                if (!empty($updateData)) {
                    $deal->update($updateData);
                }
            }
        }
    }

    private function generateQuotationNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'QP';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\Quotation::withTrashed()
            ->where('quotation_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('quotation_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
