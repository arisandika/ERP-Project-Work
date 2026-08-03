<?php

namespace App\Filament\Pages\Procurement;

use App\Filament\Concerns\BelongsToModule;
use App\Models\Procurement\Supplier;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Pages\Dashboard as BaseDashboard;

class ProcurementDashboard extends BaseDashboard
{
    use HasPageShield, BelongsToModule, HasFiltersForm {
        HasPageShield::canAccess insteadof BelongsToModule;
        HasPageShield::shouldRegisterNavigation insteadof BelongsToModule;

        HasPageShield::canAccess as shieldCanAccess;
        HasPageShield::shouldRegisterNavigation as shieldShouldRegisterNavigation;

        BelongsToModule::canAccess as moduleCanAccess;
        BelongsToModule::shouldRegisterNavigation as moduleShouldRegisterNavigation;
    }

    protected static ?string $module = 'procurement';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.pages.procurement.dashboard-procurement';

    protected static ?string $slug = 'procurement/dashboard';

    protected static string $routePath = 'procurement/dashboard';

    protected static ?string $navigationLabel = 'Dashboard Procurement';

    protected static ?string $title = 'Dashboard Procurement';

    public function getColumns(): int | string | array
    {
        return [
            'md' => 12,
            'xl' => 12,
        ];
    }

    public static function canAccess(): bool
    {
        return static::shieldCanAccess() && static::moduleCanAccess();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::shieldShouldRegisterNavigation() && static::moduleShouldRegisterNavigation();
    }

    public function filtersForm(Form $form): Form
    {
        return $form
            ->schema([
                Section::make()
                    ->heading('Dashboard Filter')
                    ->description('Sesuaikan periode dan supplier untuk melihat insight procurement.')
                    ->schema([
                        DatePicker::make('startDate')
                            ->label('Start Date')
                            ->native(false)
                            ->maxDate(now()),

                        DatePicker::make('endDate')
                            ->label('End Date')
                            ->native(false)
                            ->afterOrEqual('startDate')
                            ->maxDate(now()),

                        Select::make('supplier_id')
                            ->label('Supplier')
                            ->searchable()
                            ->preload()
                            ->placeholder('All Suppliers')
                            ->getSearchResultsUsing(fn (string $search): array => Supplier::query()
                                ->where('name', 'like', "%{$search}%")
                                ->limit(50)
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->getOptionLabelUsing(
                                fn ($value): ?string => Supplier::find($value)?->name
                            ),
                    ])
                    ->columns(3)
                    ->collapsible()
                    ->persistCollapsed(),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Procurement\ProcurementStatsOverview::class,

            \App\Filament\Widgets\Procurement\ProcurementMonthlyCostChart::class,

            \App\Filament\Widgets\Procurement\ProcurementPoStatusChart::class,

            \App\Filament\Widgets\Procurement\PendingRequisitionTable::class,

            \App\Filament\Widgets\Procurement\ProcurementLatestPoTable::class,
        ];
    }
}
