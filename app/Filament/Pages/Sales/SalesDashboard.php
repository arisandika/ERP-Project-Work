<?php

namespace App\Filament\Pages\Sales;

use App\Filament\Widgets\Sales\RevenueChart;
use App\Filament\Widgets\Sales\SalesSummaryStats;
use App\Filament\Widgets\Sales\InvoiceReportTable;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Dashboard as BaseDashboard;

class SalesDashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Dashboard Penjualan';
    protected static string $routePath = 'sales-dashboard';

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filter Analitik')
                    ->schema([
                        DatePicker::make('start_date')
                            ->label('Tanggal Awal')
                            ->default(now()->startOfMonth()),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir')
                            ->default(now()),
                    ])
                    ->columns(2),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            SalesSummaryStats::class, // KPI Atas
            RevenueChart::class,      // Grafik Tengah
            InvoiceReportTable::class // Tabel Ringkasan Bawah (Opsional)
        ];
    }
}
