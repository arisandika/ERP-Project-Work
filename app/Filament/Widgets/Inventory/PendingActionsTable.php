<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class PendingActionsTable extends BaseWidget
{
    protected static ?string $heading = 'Pending Actions';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?int $sort = 3;

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
                    ->whereIn('mutation_type', ['stock_in', 'adjustment_in', 'adjustment_out'])
                    ->whereDate('transaction_date', '>=', now()->subDays(30))
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('mutation_type')
                    ->label('Tipe')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'stock_in' => 'success',
                        'adjustment_in' => 'warning',
                        'adjustment_out' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'stock_in' => 'Stock In',
                        'adjustment_in' => 'Adjustment +',
                        'adjustment_out' => 'Adjustment -',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->limit(25)
                    ->sortable(),
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable()
                    ->weight('bold')
                    ->suffix(fn(StockTransaction $record) => $record->type === 'masuk' ? ' ↑' : ' ↓')
                    ->color(fn(StockTransaction $record) => $record->type === 'masuk' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Oleh')
                    ->sortable(),
                Tables\Columns\TextColumn::make('age')
                    ->label('Umur')
                    ->getStateUsing(fn(StockTransaction $record): int => now()->diffInDays($record->transaction_date))
                    ->sortable()
                    ->badge()
                    ->color(function (StockTransaction $record) {
                        $days = now()->diffInDays($record->transaction_date);
                        return match (true) {
                            $days <= 3 => 'success',
                            $days <= 7 => 'warning',
                            default => 'danger',
                        };
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('mutation_type')
                    ->label('Tipe Aksi')
                    ->options([
                        'stock_in' => 'Stock In / Transfer',
                        'adjustment_in' => 'Adjustment (+)',
                        'adjustment_out' => 'Adjustment (-)',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->options(fn() => Warehouse::pluck('warehouse_name', 'id'))
                    ->multiple(),
                Tables\Filters\Filter::make('old_items')
                    ->label('Lebih dari 7 hari')
                    ->query(fn(Builder $query) => $query->where('transaction_date', '<', now()->subDays(7))),
                Tables\Filters\Filter::make('very_old_items')
                    ->label('Lebih dari 14 hari')
                    ->query(fn(Builder $query) => $query->where('transaction_date', '<', now()->subDays(14))),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(StockTransaction $record): string =>
                        route('filament.admin.resources.inventory.transactions.view', $record->id))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('escalate')
                    ->label('Eskalasi')
                    ->icon('heroicon-o-arrow-up')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eskalasi Transaksi')
                    ->modalDescription('Transaksi ini akan dieskalasi ke manajer gudang.')
                    ->action(fn() => null),
            ])
            ->bulkActions([])
            ->defaultSort('transaction_date', 'desc')
            ->paginated([10, 25]);
    }
}
