<?php
namespace App\Filament\Widgets\CRM;

use App\Models\HR\Employee;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class SalesPerformanceTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Performa Sales (Leaderboard)';

    protected static ?int $sort = 6;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Employee::query()
                    ->whereHas('createdLeads')
                    ->withCount(['createdLeads as total_leads'])
                    ->withCount(['createdDeals as total_deals'])
                    ->withCount(['createdDeals as won_deals' => function ($query) {
                        $query->where('status', 'won');
                    }])
                    ->withSum(['createdDeals as won_value' => function ($query) {
                        $query->where('status', 'won');
                    }], 'estimated_value')
            )
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(url('/assets/placeholder.jpg')),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Sales')
                    ->weight('semibold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_leads')
                    ->label('Total Lead')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_deals')
                    ->label('Total Deal')
                    ->badge()
                    ->color('primary')
                    ->sortable(),
                Tables\Columns\TextColumn::make('won_deals')
                    ->label('Deal Won')
                    ->badge()
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('win_rate')
                    ->label('Win Rate')
                    ->getStateUsing(fn($record) => $record->total_deals > 0
                        ? round(($record->won_deals / $record->total_deals) * 100, 1) . '%'
                        : '0%')
                    ->badge()
                    ->color(fn($record) => $record->total_deals > 0 && ($record->won_deals / $record->total_deals) >= 0.5 ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('won_value')
                    ->label('Total Revenue (Won)')
                    ->money('IDR')
                    ->weight('semibold')
                    ->color('success')
                    ->sortable(),
            ])
            ->defaultSort('won_value', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}
