<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class RecentMovementsTable extends BaseWidget
{
    protected static ?string $heading = 'Recent Stock Movements';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?int $sort = 4;

    protected function getTable_POLLING_INTERVAL(): ?string
    {
        return '30s';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockTransaction::query()
                    ->with(['product', 'warehouse', 'creator'])
                    ->where('transaction_date', '>=', now()->subDays(7))
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('mutation_type')
                    ->label('Mutasi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'stock_in' => 'success',
                        'adjustment_in' => 'warning',
                        'adjustment_out' => 'danger',
                        'reserve' => 'info',
                        'delivery' => 'primary',
                        'complete' => 'success',
                        'cancel' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'stock_in' => 'Stock In',
                        'adjustment_in' => 'Adj (+)',
                        'adjustment_out' => 'Adj (-)',
                        'reserve' => 'Reserve',
                        'delivery' => 'Delivery',
                        'complete' => 'Complete',
                        'cancel' => 'Cancel',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Produk')
                    ->limit(25)
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->color(fn(StockTransaction $record) => $record->type === 'masuk' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('stock_flow')
                    ->label('Stok')
                    ->getStateUsing(fn(StockTransaction $record): string =>
                        number_format($record->stock_before) . ' → ' . number_format($record->stock_after))
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Waktu')
                    ->dateTime('d M H:i')
                    ->sortable()
                    ->description(fn(StockTransaction $record) => $record->transaction_date->diffForHumans()),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref')
                    ->limit(15)
                    ->copyable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'masuk' => 'Barang Masuk',
                        'keluar' => 'Barang Keluar',
                    ]),
                Tables\Filters\SelectFilter::make('mutation_type')
                    ->label('Jenis Mutasi')
                    ->options([
                        'stock_in' => 'Stock In',
                        'adjustment_in' => 'Adjustment In',
                        'adjustment_out' => 'Adjustment Out',
                        'reserve' => 'Reserve',
                        'delivery' => 'Delivery',
                        'complete' => 'Complete',
                        'cancel' => 'Cancel',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->options(fn() => Warehouse::pluck('warehouse_name', 'id'))
                    ->multiple(),
                Tables\Filters\Filter::make('today')
                    ->label('Hari Ini')
                    ->query(fn(Builder $query) => $query->whereDate('transaction_date', today())),
                Tables\Filters\Filter::make('last_24h')
                    ->label('24 Jam Terakhir')
                    ->query(fn(Builder $query) => $query->where('transaction_date', '>=', now()->subDay())),
                Tables\Filters\Filter::make('potential_issues')
                    ->label('Potensi Masalah')
                    ->query(fn(Builder $query) => $query->where('stock_after', '<', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(StockTransaction $record): string =>
                        route('filament.admin.resources.inventory.transactions.view', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([])
            ->defaultSort('transaction_date', 'desc')
            ->paginated([15, 30, 50])
            ->poll('30s');
    }
}
