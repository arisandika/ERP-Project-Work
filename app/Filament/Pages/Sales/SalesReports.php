<?php
namespace App\Filament\Pages\Sales;

use App\Filament\Concerns\BelongsToModule;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Support\Enums\MaxWidth;

class SalesReports extends Page implements HasForms
{
    use InteractsWithForms;

    /**
     * Resolusi Konflik Trait untuk Keamanan & Multi-Tenant
     * Memastikan akses divalidasi berdasarkan Role (Shield) DAN Modul yang aktif (Sales).
     */
    use HasPageShield, BelongsToModule {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'sales'; // Integrasi ke modul Sales

    protected static ?string $navigationIcon  = 'heroicon-o-document-chart-bar';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?string $title           = 'Laporan Penjualan';
    protected static ?int $navigationSort     = 7;

    protected static string $view = 'filament.pages.sales.sales-reports';

    public ?array $data = [];

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

    public function getMaxContentWidth(): MaxWidth
    {
        return MaxWidth::Full;
    }

    public function mount(): void
    {
        $this->form->fill([
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date'   => now()->toDateString(),
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
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->live(),

                        DatePicker::make('end_date')
                            ->label('Sampai Tanggal')
                            ->default(now())
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days')
                            ->live(),
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function getFilterData(): array
    {
        return $this->data ?? [];
    }
}
