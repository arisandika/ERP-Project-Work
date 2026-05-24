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
    protected static ?string $navigationGroup = 'Manajemen Procurement';
    protected static ?int $navigationSort = 0;
    protected static string $routePath = 'procurement-dashboard';
    protected static ?string $title = 'Procurement Analytics';

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
        return $form->schema([
            Section::make('Analytic Filters')
                ->schema([
                    DatePicker::make('startDate')
                        ->label('Periode Awal')
                        ->native(false)
                        ->maxDate(now()),

                    DatePicker::make('endDate')
                        ->label('Periode Akhir')
                        ->native(false)
                        ->maxDate(now())
                        ->afterOrEqual('startDate'),

                    Select::make('supplier_id')
                        ->label('Filter Supplier')
                        ->searchable()
                        ->getSearchResultsUsing(fn (string $search): array => Supplier::query()
                            ->where('name', 'like', "%{$search}%")
                            ->limit(50)
                            ->pluck('name', 'id')
                            ->toArray()
                        )
                        ->getOptionLabelUsing(fn ($value): ?string => Supplier::find($value)?->name),
                ])
                ->columns(3)
                ->collapsed(false),
        ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Procurement\ProcurementStatsOverview::class,
            \App\Filament\Widgets\Procurement\ProcurementMonthlyCostChart::class,
            \App\Filament\Widgets\Procurement\ProcurementPoStatusChart::class,
            \App\Filament\Widgets\Procurement\ProcurementLatestPoTable::class,
        ];
    }
}
