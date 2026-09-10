<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class StockAlertsTable extends BaseWidget
{
    protected static ?string $heading = 'Stock Alerts';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?int $sort = 2;

    protected function getTable_POLLING_INTERVAL(): ?string
    {
        return '30s';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Product::query()
                    ->with(['productStocks.warehouse', 'category'])
                    ->whereHas('productStocks', function ($query) {
                        $query->whereColumn('qty_available', '<=', 'nx_products.min_stock');
                    })
            )
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(35),
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),
                Tables\Columns\TextColumn::make('productStocks')
                    ->label('Gudang')
                    ->getStateUsing(fn(Product $record) =>
                        $record->productStocks->pluck('warehouse.warehouse_name')->filter()->unique()->implode(', '))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('productStocksSum')
                    ->label('Total Stok')
                    ->getStateUsing(fn(Product $record) => $record->productStocks->sum('qty_available'))
                    ->numeric()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('min_stock')
                    ->label('Min Stock')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock_status')
                    ->label('Status')
                    ->getStateUsing(fn(Product $record) =>
                        $record->getStockStatusLabel($record->productStocks->sum('qty_available')))
                    ->badge()
                    ->color(fn(Product $record) =>
                        $record->getStockStatusColor($record->productStocks->sum('qty_available')))
                    ->sortable(),
                Tables\Columns\TextColumn::make('lastMovement')
                    ->label('Transaksi Terakhir')
                    ->getStateUsing(function (Product $record) {
                        $lastTx = StockTransaction::where('product_id', $record->id)
                            ->latest('transaction_date')
                            ->value('transaction_date');
                        return $lastTx ? \Carbon\Carbon::parse($lastTx)->diffForHumans() : '-';
                    })
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse')
                    ->label('Gudang')
                    ->options(fn() => Warehouse::pluck('warehouse_name', 'id'))
                    ->query(fn(Builder $query, array $data): Builder =>
                        !empty($data['value'])
                            ? $query->whereHas('productStocks', fn($q) => $q->where('warehouse_id', $data['value']))
                            : $query)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options(fn() => Category::pluck('name', 'id'))
                    ->query(fn(Builder $query, array $data): Builder =>
                        !empty($data['value'])
                            ? $query->where('category_id', $data['value'])
                            : $query)
                    ->multiple(),
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Status Stok')
                    ->options([
                        'out_of_stock' => 'Out of Stock',
                        'critical' => 'Critical (≤5)',
                        'low' => 'Low (≤ Min Stock)',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']))
                            return $query;
                        return match ($data['value']) {
                            'out_of_stock' => $query->whereHas('productStocks', fn($q) => $q->where('qty_available', '<=', 0)),
                            'critical' => $query->whereHas('productStocks', fn($q) => $q->where('qty_available', '>', 0)->where('qty_available', '<=', 5)),
                            'low' => $query->whereHas('productStocks', fn($q) =>
                                $q->whereColumn('qty_available', '<=', 'nx_products.min_stock')->where('qty_available', '>', 0)),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat Produk')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn(Product $record): string =>
                        route('filament.admin.resources.inventory.products.view', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('viewTransactions')
                    ->label('Mutasi')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->url(fn(Product $record): string =>
                        route('filament.admin.resources.inventory.transactions.index') . '?tableProductFilter=' . $record->id)
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }
}
