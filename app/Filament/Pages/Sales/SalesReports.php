<?php

namespace App\Filament\Pages\Sales;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Support\Enums\MaxWidth;

class SalesReports extends Page implements HasForms
{
    use InteractsWithForms;
    use HasPageShield;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Finance';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?string $title = 'Laporan Penjualan';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.sales.sales-reports';

    public ?array $data = [];

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
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
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now())
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->columns(2)
                    ->statePath('data')
                    ->live(),
            ]);
    }

    public function getFilterData(): array
    {
        return $this->data ?? [];
    }
}
