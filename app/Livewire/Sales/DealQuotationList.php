<?php

namespace App\Livewire\Sales;

use App\Filament\Resources\Sales\QuotationResource;
use App\Models\CRM\Deal;
use App\Models\CRM\DealStage;
use App\Models\CRM\Lead;
use App\Models\Sales\Quotation;
use App\Services\Sales\QuotationService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DealQuotationList extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public $dealId;

    public function table(Table $table): Table
    {
        return $table
            ->query(Quotation::where('nx_deal_id', $this->dealId)->latest())
            ->columns([
                Tables\Columns\TextColumn::make('quotation_number')
                    ->label('No. Penawaran')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'new' => 'gray',
                        'sent' => 'warning',
                        'negotiation' => 'info',
                        'accepted' => 'success',
                        'rejected' => 'danger',
                        'expired' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'new' => 'Baru',
                        'sent' => 'Terkirim',
                        'negotiation' => 'Negosiasi',
                        'accepted' => 'Diterima',
                        'rejected' => 'Ditolak',
                        'expired' => 'Expired',
                        default => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('quotation_date')
                    ->label('Tgl Penawaran')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valid_until')
                    ->label('Berlaku Hingga')
                    ->date('d M Y')
                    ->sortable()
                    ->color(function (Quotation $record) {
                        if (in_array($record->status, ['accepted', 'rejected'])) return 'gray';
                        if (\Carbon\Carbon::parse($record->valid_until)->isPast()) return 'danger';
                        return 'success';
                    }),
            ])
            ->headerActions([
                // == CREATE ACTION (Replikasi CreateQuotation) ==
                Tables\Actions\CreateAction::make('create_quotation')
                    ->label('Tambah Penawaran')
                    ->icon('heroicon-o-plus')
                    ->modalWidth('6xl')
                    ->form(fn($form) => QuotationResource::form($form)->getComponents())
                    // 1. Set Default Values
                    ->mountUsing(function ($form) { 
                        $form->fill([
                            'created_by'       => auth()->user()?->employee?->id,
                            'nx_deal_id'       => $this->dealId,
                            'quotation_number' => $this->generateQuotationNumber(),
                            'quotation_date'   => now()->toDateString(),
                            'valid_until'      => now()->addDays(7)->toDateString(),
                            'status'           => 'new',
                            'tax'              => 11,
                        ]);
                    })
                    // 2. Custom Save Logic
                    ->using(function (array $data): Model {
                        // Formatting sebelum save
                        $data['nx_deal_id']       = $this->dealId; // pastikan tetap aman
                        $data['quotation_number'] = $this->generateQuotationNumber();
                        $data['quotation_date']   = Carbon::parse($data['quotation_date'])->setTimeFrom(now());
                        $data['valid_until']      = Carbon::parse($data['valid_until'])->endOfDay();

                        if (auth()->user()?->employee) {
                            $data['created_by'] = auth()->user()->employee->id;
                        }

                        // Save menggunakan QuotationService
                        return app(QuotationService::class)->createQuotation($data);
                    })
                    // 3. After Create Hooks (Ubah stage & status lead)
                    ->after(function (Model $record) {
                        if ($record->nx_deal_id) {
                            $deal = Deal::find($record->nx_deal_id);
                
                            if ($deal) {
                                // Ubah Deal Stage menjadi Penawaran
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

                        // Memicu refresh kanban board agar tampilan stage deal terbaru langsung pindah
                        $this->dispatch('refresh-board');
                    }),
            ])
            ->actions([
                // == APPROVE ACTION ==
                Tables\Actions\Action::make('approve')
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
                        $this->dispatch('refresh-board');
                    }),

                // == REJECT ACTION ==
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Textarea::make('reason')
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
                        $this->dispatch('refresh-board');
                    }),

                // == EDIT ACTION (Replikasi EditQuotation) ==
                Tables\Actions\EditAction::make('edit_quotation')
                    ->modalWidth('6xl')
                    ->form(fn($form) => QuotationResource::form($form)->getComponents())
                    // Tarik data relasi items (MutateFormDataBeforeFill)
                    ->mutateRecordDataUsing(function (array $data, Model $record): array {
                        $data['items'] = $record->items->toArray();
                        return $data;
                    })
                    // Custom Edit Logic (HandleRecordUpdate)
                    ->using(function (Model $record, array $data): Model {
                        if ($record->status !== 'accepted' && ($data['status'] ?? $record->status) === 'accepted') {
                            $user = auth()->user();
                            $data['approved_by'] = $user?->employee?->id;
                            $data['approved_at'] = now();
                        }
                
                        $updatedRecord = app(QuotationService::class)->updateQuotation($record, $data);
                        $this->dispatch('refresh-board');
                        return $updatedRecord;
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->after(fn() => $this->dispatch('refresh-board')),
            ]);
    }

    // Fungsi helper penomoran otomatis
    private function generateQuotationNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'QP';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = Quotation::withTrashed()
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

    public function render()
    {
        return view('livewire.sales.deal-quotation-list');
    }
}