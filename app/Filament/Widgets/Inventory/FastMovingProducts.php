<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\StockTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class FastMovingProducts extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Fast-Moving Products (30 Hari)';

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected static bool $isLazy = true;

    protected static ?int $sort = 6;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getFastMovingQuery())
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->stateUsing(fn ($record, $rowIndex) => $rowIndex + 1)
                    ->alignCenter()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->limit(25)
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_movements')
                    ->label('Mutasi')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->color('success'),

                Tables\Columns\TextColumn::make('avg_per_day')
                    ->label('Rata2/Hari')
                    ->getStateUsing(fn ($record): string => number_format($record->total_movements / 30, 1)),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false)
            ->poll('60s');
    }

    private function getFastMovingQuery()
    {
        return StockTransaction::query()
            ->join('nx_products', 'nx_products.id', '=', 'nx_stock_transactions.product_id')
            ->selectRaw('
                nx_products.product_code,
                nx_products.product_name,
                nx_stock_transactions.product_id,
                SUM(nx_stock_transactions.quantity) as total_movements,
                (SELECT SUM(ps.qty_available) FROM nx_product_stock ps WHERE ps.product_id = nx_stock_transactions.product_id) as current_stock
            ')
            ->where('nx_stock_transactions.transaction_date', '>=', now()->subDays(30))
            ->where('nx_stock_transactions.type', 'keluar')
            ->groupBy('nx_products.product_code', 'nx_products.product_name', 'nx_stock_transactions.product_id')
            ->orderByDesc('total_movements')
            ->limit(10);
    }
}
