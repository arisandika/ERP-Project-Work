<?php

namespace App\Filament\Pages\Sales;

use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;

// Panggil Widget-Widget Statistik & Chart di sini
use App\Filament\Widgets\Sales\SalesSummaryStats;
use App\Filament\Widgets\Sales\SalesPipelineChart;
use App\Filament\Widgets\Sales\OperationalAlertTable;
// Kalau mau nambah chart promo, panggil juga: use App\Filament\Widgets\Sales\TopPromosChart;

class SalesReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?string $title = 'Laporan Penjualan';

    protected static ?int $navigationSort = 1;


    protected static string $view = 'filament.pages.sales.sales-reports';

    // 1. Agar tampilan laporan lebar (Full Width)
    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public ?array $data = [];

    public function mount(): void
    {
        // Default filter: Bulan ini
        $this->form->fill([
            'start_date' => now()->startOfMonth(),
            'end_date' => now(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Periode Laporan')
                    ->description('Pilih rentang tanggal. Semua grafik dan tabel di bawah akan menyesuaikan otomatis.')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Mulai Tanggal')
                            ->default(now()->startOfMonth())
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now())
                            ->required(),
                    ])
                    ->columns(2)
                    ->statePath('data')
                    ->live()
            ]);
    }

    public function getFilterData(): array
    {
        return $this->data;
    }
}
