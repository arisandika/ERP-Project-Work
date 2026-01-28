<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\SalesOrder;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Database\Eloquent\Builder;

class PromoReportTable extends BaseWidget
{
    
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Top Promo Performance';
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SalesOrder::query()
                    ->selectRaw('promo_code_id, count(*) as usage_count, sum(grand_total) as revenue_generated')
                    // Trik: Alias-kan ID agar Filament mengira ini Primary Key
                    ->selectRaw('promo_code_id as id')
                    ->whereNotNull('promo_code_id')

                    // Filter Tanggal
                    ->when(
                        $this->filters['start_date'] ?? null,
                        fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date)
                    )
                    ->when(
                        $this->filters['end_date'] ?? null,
                        fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date)
                    )

                    ->groupBy('promo_code_id')
                    ->orderByDesc('usage_count')
                    ->limit(5)
            )
            // HAPUS BARIS INI: ->recordKey(...)

            ->columns([
                Tables\Columns\TextColumn::make('promoCode.code')
                    ->label('Kode')
                    ->badge()
                    ->color('info')
                    ->description(fn ($record) => $record->promoCode->type ?? '-'),

                Tables\Columns\TextColumn::make('usage_count')
                    ->label('Used')
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('revenue_generated')
                    ->label('Sales')
                    ->money('IDR')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall),
            ])
            ->paginated(false)
            // Nonaktifkan klik baris karena ini data agregat
            ->recordUrl(null)
            ->recordAction(null);
    }
}
