<?php

namespace App\Filament\Pages\Finance;

use Filament\Pages\Page;
use App\Models\Finance\FinancialRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;

class CashFlowReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 5;
    protected static ?string $title = 'Laporan Arus Kas';
    protected static ?string $slug = 'finance/cash-flow';

    protected static string $view = 'filament.pages.finance.cash-flow-report';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'end_date' => Carbon::now()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('start_date')
                    ->label('Periode Dari')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
            ])
            ->statePath('data')
            ->columns(2);
    }

    protected function getViewData(): array
    {
        $startDateStr = $this->data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDateStr = $this->data['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        // 1. HITUNG SALDO AWAL (Uang kas sebelum tanggal mulai laporan)
        $openingIn = FinancialRecord::where('transaction_date', '<', $startDate)->where('type', 'pemasukan')->sum('amount');
        $openingOut = FinancialRecord::where('transaction_date', '<', $startDate)->where('type', 'pengeluaran')->sum('amount');
        $openingBalance = $openingIn - $openingOut;

        // 2. AMBIL TRANSAKSI PADA PERIODE TERSEBUT
        $transactions = FinancialRecord::whereBetween('transaction_date', [$startDate, $endDate])->get();

        // 3. ARUS KAS MASUK (Cash Inflows)
        $cashInflows = $transactions->where('type', 'pemasukan')->groupBy('category');
        $totalInflow = $transactions->where('type', 'pemasukan')->sum('amount');

        // 4. ARUS KAS KELUAR (Cash Outflows)
        $cashOutflows = $transactions->where('type', 'pengeluaran')->groupBy('category');
        $totalOutflow = $transactions->where('type', 'pengeluaran')->sum('amount');

        // 5. KENAIKAN/PENURUNAN KAS BERSIH
        $netCashFlow = $totalInflow - $totalOutflow;

        // 6. SALDO AKHIR
        $endingBalance = $openingBalance + $netCashFlow;

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'openingBalance' => $openingBalance,
            'cashInflows' => $cashInflows,
            'totalInflow' => $totalInflow,
            'cashOutflows' => $cashOutflows,
            'totalOutflow' => $totalOutflow,
            'netCashFlow' => $netCashFlow,
            'endingBalance' => $endingBalance,
        ];
    }
}
