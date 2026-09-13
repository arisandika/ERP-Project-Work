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

class AccountsReceivable extends Page
{
    /**
     * Resolusi Konflik Trait
     * Memastikan akses dibatasi oleh Role User (Shield) dan Modul Tenant.
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'finance';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-up-circle';
    protected static ?string $title = 'Accounts Receivable';
    protected static ?string $navigationLabel = 'Accounts Receivable';
    protected static ?string $slug = 'finance/accounts-receivable';

    protected static string $view = 'filament.pages.finance.accounts-receivable';

    public ?string $selectedReferenceNumber = null;

    /**
     * Override method canAccess()
     */
    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    /**
     * Override method shouldRegisterNavigation()
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    protected function getViewData(): array
    {
        // REFAKTORISASI: Subquery Eloquent untuk mencegah N+1 Query.
        // Menghitung total pembayaran yang diterima langsung di sisi Database.
        $receivables = FinancialRecord::query()
            ->where('type', 'piutang')
            ->addSelect([
                'received_amount' => FinancialRecord::query()
                    ->selectRaw('COALESCE(SUM(amount), 0)')
                    ->whereColumn('reference_number', 'nx_financial_records.reference_number')
                    ->where('type', 'pemasukan')
                    ->where('category', 'Accounts Receivable')
            ])
            ->orderByDesc('transaction_date')
            ->get()
            ->map(function (FinancialRecord $receivable) {
                // Menggunakan hasil agregasi SQL (tidak ada query database tambahan di sini)
                $receivedAmount = (float) $receivable->received_amount;
                $remainingAmount = max((float) $receivable->amount - $receivedAmount, 0);

                return [
                    'id' => $receivable->id,
                    'reference_number' => $receivable->reference_number,
                    'transaction_date' => optional($receivable->transaction_date)?->format('d M Y'),
                    'description' => $receivable->description,
                    'total_amount' => (float) $receivable->amount,
                    'received_amount' => $receivedAmount,
                    'remaining_amount' => $remainingAmount,
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

                    if ($amount <= 0 || $amount > $remaining) {
                        Notification::make()
                            ->title('Nominal pembayaran tidak valid atau melebihi sisa piutang')
                            ->danger()
                            ->send();

                        return;
                    }

                    // REFAKTORISASI: Implementasi DB Transaction untuk integritas pencatatan kas
                    try {
                        DB::transaction(function () use ($data, $amount, $receivable) {
                            // IDEMPOTENT: double-execution pada data identical (retry/double-click)
                            // tidak menambah jurnal ganda. Guard = reference piutang + nominal + tanggal.
                            // Partial payment dengan nominal berbeda TETAP menumpuk (create baru), tidak overwrite.
                            $alreadyRecorded = FinancialRecord::query()
                                ->where('reference_type', FinancialRecord::class)
                                ->where('reference_id', $receivable['id'])
                                ->where('amount', $amount)
                                ->whereDate('transaction_date', $data['transaction_date'])
                                ->where('type', 'pemasukan')
                                ->exists();

                            if (! $alreadyRecorded) {
                                FinancialRecord::create([
                                    'transaction_date' => $data['transaction_date'],
                                    'type' => 'pemasukan', // Pembayaran piutang adalah uang masuk
                                    'amount' => $amount,
                                    'category' => 'Accounts Receivable',
                                    'description' => $data['description'] ?: 'Penerimaan pembayaran customer',
                                    'reference_number' => $receivable['reference_number'],
                                    'reference_type' => FinancialRecord::class,
                                    'reference_id'   => $receivable['id'],
                                    'created_by' => auth()->user()?->employee?->id,
                                ]);
                            }
                        });

                        Notification::make()
                            ->title('Pembayaran customer berhasil dicatat')
                            ->success()
                            ->send();

                        $this->selectedReferenceNumber = null;

                    } catch (Throwable $e) {
                        Notification::make()
                            ->title('Gagal mencatat penerimaan.')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    protected function getSelectedReceivable(): ?array
    {
        /** @var Collection<int, array> $receivables */
        $receivables = collect($this->getViewData()['receivables']);

        return $receivables->firstWhere('reference_number', $this->selectedReferenceNumber);
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
