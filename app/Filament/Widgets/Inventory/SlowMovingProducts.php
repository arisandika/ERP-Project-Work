<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class SlowMovingProducts extends BaseWidget
{
    protected static ?string $heading = 'Top 10 Slow-Moving / Idle Products';

    protected int|string|array $columnSpan = [
        'md' => 12,
        'xl' => 6,
    ];

    protected static bool $isLazy = true;

    protected static ?int $sort = 7;

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getSlowMovingQuery())
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->stateUsing(fn ($record, $rowIndex) => $rowIndex + 1)
                    ->alignCenter()
                    ->weight('bold')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->limit(25)
                    ->sortable(),

                Tables\Columns\TextColumn::make('current_stock')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('days_since_movement')
                    ->label('Hari Tanpa Transaksi')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state >= 180 => 'danger',
                        $state >= 90 => 'warning',
                        default => 'info',
                    }),

                Tables\Columns\TextColumn::make('last_movement_date')
                    ->label('Transaksi Terakhir')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->paginated(false)
            ->poll('60s');
    }

    private function getSlowMovingQuery()
    {
        return Product::query()
            ->join('nx_product_stock', 'nx_product_stock.product_id', '=', 'nx_products.id')
            ->selectRaw('
                nx_products.product_code,
                nx_products.product_name,
                SUM(nx_product_stock.qty_available) as current_stock,
                MAX(nx_stock_transactions.transaction_date) as last_movement_date,
                DATEDIFF(NOW(), MAX(nx_stock_transactions.transaction_date)) as days_since_movement
            ')
            ->leftJoin('nx_stock_transactions', 'nx_stock_transactions.product_id', '=', 'nx_products.id')
            ->where('nx_product_stock.qty_available', '>', 0)
            ->where(function ($query) {
                $query->whereNull('nx_stock_transactions.transaction_date')
                    ->orWhere('nx_stock_transactions.transaction_date', '<', now()->subDays(60));
            })
            ->groupBy('nx_products.product_code', 'nx_products.product_name', 'nx_products.id')
            ->orderByRaw('COALESCE(MAX(nx_stock_transactions.transaction_date), \'1970-01-01\') ASC')
            ->limit(10);
    }
}
