<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\FinancialRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class AccountsReceivable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Account Receivable (Piutang Customer)';
    protected static ?string $slug = 'finance/accounts-receivable';

    protected static string $view = 'filament.pages.finance.accounts-receivable';

    public ?string $selectedReferenceNumber = null;

    protected function getViewData(): array
    {
        $receivables = FinancialRecord::query()
            ->where('type', 'piutang')
            ->orderByDesc('transaction_date')
            ->get()
            ->map(function (FinancialRecord $receivable) {
                $receivedAmount = FinancialRecord::query()
                    ->where('type', 'pemasukan')
                    ->where('category', 'Accounts Receivable')
                    ->where('reference_number', $receivable->reference_number)
                    ->sum('amount');

                $remainingAmount = max((float) $receivable->amount - (float) $receivedAmount, 0);

                return [
                    'id' => $receivable->id,
                    'reference_number' => $receivable->reference_number,
                    'transaction_date' => optional($receivable->transaction_date)?->format('d M Y'),
                    'description' => $receivable->description,
                    'total_amount' => (float) $receivable->amount,
                    'received_amount' => (float) $receivedAmount,
                    'remaining_amount' => (float) $remainingAmount,
                    'status' => match (true) {
                        $remainingAmount <= 0 => 'PAID',
                        $receivedAmount > 0 => 'PARTIAL',
                        default => 'UNPAID',
                    },
                ];
            });

        return [
            'receivables' => $receivables,
            'summary' => [
                'total_receivable' => $receivables->sum('total_amount'),
                'total_received' => $receivables->sum('received_amount'),
                'total_remaining' => $receivables->sum('remaining_amount'),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receivePayment')
                ->label('Terima Pembayaran')
                ->icon('heroicon-o-banknotes')
                ->color('success')
                ->visible(fn (): bool => filled($this->selectedReferenceNumber))
                ->form(function (): array {
                    $receivable = $this->getSelectedReceivable();

                    if (! $receivable) {
                        return [];
                    }

                    return [
                        TextInput::make('reference_number')
                            ->label('Referensi Piutang')
                            ->default($receivable['reference_number'])
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('remaining_amount')
                            ->label('Sisa Piutang')
                            ->default(number_format($receivable['remaining_amount'], 0, ',', '.'))
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('transaction_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('amount')
                            ->label('Nominal Diterima')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue((float) $receivable['remaining_amount'])
                            ->prefix('IDR'),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->default('Penerimaan pembayaran customer')
                            ->rows(3),
                    ];
                })
                ->action(function (array $data): void {
                    $receivable = $this->getSelectedReceivable();

                    if (! $receivable) {
                        Notification::make()
                            ->title('Data piutang tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    $amount = (float) ($data['amount'] ?? 0);
                    $remaining = (float) $receivable['remaining_amount'];

                    if ($amount <= 0) {
                        Notification::make()
                            ->title('Nominal pembayaran tidak valid')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($amount > $remaining) {
                        Notification::make()
                            ->title('Nominal melebihi sisa piutang')
                            ->danger()
                            ->send();

                        return;
                    }

                    FinancialRecord::create([
                        'transaction_date' => $data['transaction_date'],
                        'type' => 'pemasukan',
                        'amount' => $amount,
                        'category' => 'Accounts Receivable',
                        'description' => $data['description'] ?: 'Penerimaan pembayaran customer',
                        'reference_number' => $receivable['reference_number'],
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Pembayaran customer berhasil dicatat')
                        ->success()
                        ->send();

                    $this->selectedReferenceNumber = null;
                }),
        ];
    }

    protected function getSelectedReceivable(): ?array
    {
        /** @var Collection<int, array> $receivables */
        $receivables = collect($this->getViewData()['receivables']);

        return $receivables
            ->firstWhere('reference_number', $this->selectedReferenceNumber);
    }

    public function selectReceivable(string $referenceNumber): void
    {
        $this->selectedReferenceNumber = $referenceNumber;
    }

    public function clearSelection(): void
    {
        $this->selectedReferenceNumber = null;
    }
}
