<?php

namespace App\Filament\Pages\Finance;

use App\Models\Finance\FinancialRecord;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AccountsPayable extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Account Payable (Hutang Supplier)';
    protected static ?string $slug = 'finance/accounts-payable';

    protected static string $view = 'filament.pages.finance.accounts-payable';

    public ?string $selectedReferenceNumber = null;

    protected function getViewData(): array
    {
        $debts = FinancialRecord::query()
            ->where('type', 'hutang')
            ->orderByDesc('transaction_date')
            ->get()
            ->map(function (FinancialRecord $debt) {
                $paidAmount = FinancialRecord::query()
                    ->where('type', 'pengeluaran')
                    ->where('category', 'Accounts Payable')
                    ->where('reference_number', $debt->reference_number)
                    ->sum('amount');

                $remainingAmount = max((float) $debt->amount - (float) $paidAmount, 0);

                return [
                    'id' => $debt->id,
                    'reference_number' => $debt->reference_number,
                    'transaction_date' => optional($debt->transaction_date)?->format('d M Y'),
                    'description' => $debt->description,
                    'total_amount' => (float) $debt->amount,
                    'paid_amount' => (float) $paidAmount,
                    'remaining_amount' => (float) $remainingAmount,
                    'status' => match (true) {
                        $remainingAmount <= 0 => 'PAID',
                        $paidAmount > 0 => 'PARTIAL',
                        default => 'UNPAID',
                    },
                ];
            });

        return [
            'debts' => $debts,
            'summary' => [
                'total_debt' => $debts->sum('total_amount'),
                'total_paid' => $debts->sum('paid_amount'),
                'total_remaining' => $debts->sum('remaining_amount'),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('payDebt')
                ->label('Bayar Hutang')
                ->icon('heroicon-o-credit-card')
                ->color('success')
                ->visible(fn (): bool => filled($this->selectedReferenceNumber))
                ->form(function (): array {
                    $debt = $this->getSelectedDebt();

                    if (! $debt) {
                        return [];
                    }

                    return [
                        TextInput::make('reference_number')
                            ->label('Referensi Hutang')
                            ->default($debt['reference_number'])
                            ->disabled()
                            ->dehydrated(false),

                        TextInput::make('remaining_amount')
                            ->label('Sisa Hutang')
                            ->default(number_format($debt['remaining_amount'], 0, ',', '.'))
                            ->disabled()
                            ->dehydrated(false),

                        DatePicker::make('transaction_date')
                            ->label('Tanggal Pembayaran')
                            ->default(now())
                            ->native(false)
                            ->required(),

                        TextInput::make('amount')
                            ->label('Nominal Bayar')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->maxValue((float) $debt['remaining_amount'])
                            ->prefix('IDR'),

                        Textarea::make('description')
                            ->label('Keterangan')
                            ->default('Pembayaran hutang supplier')
                            ->rows(3),
                    ];
                })
                ->action(function (array $data): void {
                    $debt = $this->getSelectedDebt();

                    if (! $debt) {
                        Notification::make()
                            ->title('Data hutang tidak ditemukan')
                            ->danger()
                            ->send();

                        return;
                    }

                    $amount = (float) ($data['amount'] ?? 0);
                    $remaining = (float) $debt['remaining_amount'];

                    if ($amount <= 0) {
                        Notification::make()
                            ->title('Nominal pembayaran tidak valid')
                            ->danger()
                            ->send();

                        return;
                    }

                    if ($amount > $remaining) {
                        Notification::make()
                            ->title('Nominal melebihi sisa hutang')
                            ->danger()
                            ->send();

                        return;
                    }

                    FinancialRecord::create([
                        'transaction_date' => $data['transaction_date'],
                        'type' => 'pengeluaran',
                        'amount' => $amount,
                        'category' => 'Accounts Payable',
                        'description' => $data['description'] ?: 'Pembayaran hutang supplier',
                        'reference_number' => $debt['reference_number'],
                        'created_by' => auth()->id(),
                    ]);

                    Notification::make()
                        ->title('Pembayaran hutang berhasil dicatat')
                        ->success()
                        ->send();

                    $this->selectedReferenceNumber = null;
                }),
        ];
    }

    protected function getSelectedDebt(): ?array
    {
        /** @var Collection<int, array> $debts */
        $debts = collect($this->getViewData()['debts']);

        return $debts
            ->firstWhere('reference_number', $this->selectedReferenceNumber);
    }

    public function selectDebt(string $referenceNumber): void
    {
        $this->selectedReferenceNumber = $referenceNumber;
    }

    public function clearSelection(): void
    {
        $this->selectedReferenceNumber = null;
    }
}
