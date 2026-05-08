<?php

namespace App\Filament\Pages\Finance;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Finance\FinancialRecord;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class AccountsPayable extends Page
{
    /**
     * Resolusi Konflik Trait
     * Validasi ganda: Permission User (Shield) DAN Status Modul (Tenant/Lisensi).
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance'; // Set modul ke Finance

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 3;
    protected static ?string $title = 'Account Payable (Hutang Supplier)';
    protected static ?string $slug = 'finance/accounts-payable';

    protected static string $view = 'filament.pages.finance.accounts-payable';

    public ?string $selectedReferenceNumber = null;

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    protected function getViewData(): array
    {
        // REFAKTORISASI: Menggunakan Eloquent Subquery untuk menghindari N+1 Query.
        // Database engine yang akan menjumlahkan total_paid, bukan PHP.
        $debts = FinancialRecord::query()
            ->where('type', 'hutang')
            ->addSelect([
                'paid_amount' => FinancialRecord::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('reference_number', 'financial_records.reference_number')
                    ->where('type', 'pengeluaran')
                    ->where('category', 'Accounts Payable')
            ])
            ->orderByDesc('transaction_date')
            ->get()
            ->map(function (FinancialRecord $debt) {
                // paid_amount sekarang sudah ditarik otomatis dari query di atas
                $paidAmount = (float) $debt->paid_amount;
                $remainingAmount = max((float) $debt->amount - $paidAmount, 0);

                return [
                    'id' => $debt->id,
                    'reference_number' => $debt->reference_number,
                    'transaction_date' => optional($debt->transaction_date)?->format('d M Y'),
                    'description' => $debt->description,
                    'total_amount' => (float) $debt->amount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
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

                    // Validasi backend ekstra (Sangat direkomendasikan)
                    if ($amount <= 0 || $amount > $remaining) {
                        Notification::make()
                            ->title('Nominal pembayaran tidak valid atau melebihi sisa hutang.')
                            ->danger()
                            ->send();
                        return;
                    }

                    // REFAKTORISASI: Bungkus dalam DB::transaction untuk integritas finansial
                    try {
                        DB::transaction(function () use ($data, $amount, $debt) {
                            FinancialRecord::create([
                                'transaction_date' => $data['transaction_date'],
                                'type' => 'pengeluaran',
                                'amount' => $amount,
                                'category' => 'Accounts Payable',
                                'description' => $data['description'] ?: 'Pembayaran hutang supplier',
                                'reference_number' => $debt['reference_number'],
                                'created_by' => auth()->id(),
                            ]);
                        });

                        Notification::make()
                            ->title('Pembayaran hutang berhasil dicatat')
                            ->success()
                            ->send();

                        $this->selectedReferenceNumber = null;

                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Terjadi kesalahan sistem saat memproses pembayaran.')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getSelectedDebt(): ?array
    {
        /** @var Collection<int, array> $debts */
        $debts = collect($this->getViewData()['debts']);

        return $debts->firstWhere('reference_number', $this->selectedReferenceNumber);
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
