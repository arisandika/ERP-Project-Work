<?php

namespace App\Filament\Pages\Finance;

use Filament\Pages\Page;
use App\Models\Finance\FinancialRecord;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Carbon;

class ProfitAndLossReport extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?int $navigationSort = 4;
    protected static ?string $title = 'Laporan Laba Rugi';
    protected static ?string $slug = 'finance/profit-and-loss';

    protected static string $view = 'filament.pages.finance.profit-and-loss-report';

    // FIX UTAMA: Di Filament v3, Form State harus dibungkus dalam properti array
    public ?array $data = [];

    public function mount(): void
    {
        // Isi nilai default form
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
                    ->live() // Auto-refresh saat diubah
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Sampai Tanggal')
                    ->native(false)
                    ->displayFormat('d M Y')
                    ->live()
                    ->required(),
            ])
            ->statePath('data') // FIX UTAMA: Wajib deklarasi state path
            ->columns(2);
    }

    /**
     * Engine Perhitungan Akuntansi
     */
    protected function getViewData(): array
    {
        // Ambil data dari state array
        $startDateStr = $this->data['start_date'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDateStr = $this->data['end_date'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');

        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate = Carbon::parse($endDateStr)->endOfDay();

        // 1. Ambil semua transaksi di rentang waktu tersebut
        $transactions = FinancialRecord::whereBetween('transaction_date', [$startDate, $endDate])->get();

        // 2. Pendapatan (Semua Pemasukan)
        $revenueDetails = $transactions->where('type', 'pemasukan')->groupBy('category');
        $totalRevenue = $transactions->where('type', 'pemasukan')->sum('amount');

        // 3. Harga Pokok Penjualan (HPP)
        $cogsTransactions = $transactions->where('type', 'pengeluaran')->whereIn('category', ['Purchase Order', 'Purchase']);
        $totalCogs = $cogsTransactions->sum('amount');

        // 4. Laba Kotor
        $grossProfit = $totalRevenue - $totalCogs;

        // 5. Biaya Operasional (Opex)
        $opexTransactions = $transactions->where('type', 'pengeluaran')->whereNotIn('category', ['Purchase Order', 'Purchase']);
        $opexDetails = $opexTransactions->groupBy('category');
        $totalOpex = $opexTransactions->sum('amount');

        // 6. Laba Bersih
        $netProfit = $grossProfit - $totalOpex;

        return [
            'period' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y'),
            'revenueDetails' => $revenueDetails,
            'totalRevenue' => $totalRevenue,
            'totalCogs' => $totalCogs,
            'grossProfit' => $grossProfit,
            'opexDetails' => $opexDetails,
            'totalOpex' => $totalOpex,
            'netProfit' => $netProfit,
        ];
    }
}
