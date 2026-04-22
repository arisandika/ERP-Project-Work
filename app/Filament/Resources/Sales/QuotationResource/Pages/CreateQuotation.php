<?php

namespace App\Filament\Resources\Sales\QuotationResource\Pages;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Services\Sales\QuotationService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

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
                Notification::make()->title('Akses Ditolak')
                    ->body("Deal {$deal->deal_number} sudah dihapus. Restore terlebih dahulu.")
                    ->danger()->persistent()->send();

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
            'status' => 'new',
            'tax' => 11,
        ]);
    }

    protected function handleRecordCreation(array $data): Model
    {
        $data['quotation_number'] = $this->generateQuotationNumber();
        $data['quotation_date'] = Carbon::parse($data['quotation_date'])->setTimeFrom(now());
        $data['valid_until'] = Carbon::parse($data['valid_until'])->endOfDay();

        if (auth()->user()?->employee) {
            $data['created_by'] = auth()->user()->employee->id;
        }

        return app(QuotationService::class)->createQuotation($data);
    }

    protected function afterCreate(): void
    {
        $quotation = $this->record;

        if ($quotation->nx_deal_id) {
            $deal = Deal::find($quotation->nx_deal_id);

            if ($deal) {
                // 2. Ubah Deal Stage menjadi Penawaran
                $penawaranStage = DealStage::whereRaw('LOWER(name) LIKE ?', ['%penawaran%'])->first();
                if ($penawaranStage && $deal->nx_deal_stage_id !== $penawaranStage->id) {
                    $deal->update([
                        'nx_deal_stage_id' => $penawaranStage->id,
                    ]);
                }

                // Ubah status Lead menjadi Qualified
                if ($deal->nx_lead_id) {
                    $lead = Lead::find($deal->nx_lead_id);
                    if ($lead && in_array($lead->status, [Lead::STATUS_NEW, Lead::STATUS_CONTACTED])) {
                        $lead->update([
                            'status' => Lead::STATUS_QUALIFIED,
                        ]);
                    }
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
            ->lockForUpdate()
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
