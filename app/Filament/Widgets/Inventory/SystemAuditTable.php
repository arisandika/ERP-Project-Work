<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class SystemAuditTable extends BaseWidget
{
    protected static ?string $heading = 'System Audit — Data Integrity';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?int $sort = 5;

    protected function getTable_POLLING_INTERVAL(): ?string
    {
        return '60s';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getAuditQuery())
            ->columns([
                Tables\Columns\TextColumn::make('severity')
                    ->label('Severity')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'negative_stock' => 'danger',
                        'data_mismatch' => 'warning',
                        'failed_transaction' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'negative_stock' => 'Stok Negatif',
                        'data_mismatch' => 'Ketidakcocokan Data',
                        'failed_transaction' => 'Transaksi Gagal',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('description')
                    ->label('Deskripsi')
                    ->limit(50),
                Tables\Columns\TextColumn::make('expected_value')
                    ->label('Expected')
                    ->numeric(),
                Tables\Columns\TextColumn::make('actual_value')
                    ->label('Actual')
                    ->numeric()
                    ->color(fn($state, $record) => $state != $record->expected_value ? 'danger' : 'success'),
                Tables\Columns\TextColumn::make('detected_at')
                    ->label('Detected')
                    ->dateTime('d M H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('resolved')
                    ->label('Resolved')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('severity')
                    ->label('Severity')
                    ->options(['critical' => 'Critical', 'warning' => 'Warning', 'info' => 'Info'])
                    ->multiple(),
                Tables\Filters\Filter::make('unresolved')
                    ->label('Belum Diselesaikan')
                    ->query(fn(Builder $query) => $query->where('resolved', false)),
                Tables\Filters\Filter::make('last_24h')
                    ->label('24 Jam Terakhir')
                    ->query(fn(Builder $query) => $query->where('detected_at', '>=', now()->subDay())),
            ])
            ->actions([
                Tables\Actions\Action::make('investigate')
                    ->label('Investigate')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('primary')
                    ->url(fn() => route('filament.admin.resources.inventory.transactions.index'))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('markResolved')
                    ->label('Resolve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Tandai Selesai')
                    ->modalDescription('Tandai masalah ini sebagai sudah diselesaikan.')
                    ->action(fn($record) => $record->update(['resolved' => true])),
            ])
            ->bulkActions([])
            ->defaultSort('detected_at', 'desc')
            ->paginated([10, 25]);
    }

    private function getAuditQuery(): Builder
    {
        $negativeStock = ProductStock::query()
            ->selectRaw("
                'critical' as severity,
                'negative_stock' as category,
                CONCAT('Stok negatif: ', qty_available, ' unit') as description,
                0 as expected_value,
                qty_available as actual_value,
                created_at as detected_at,
                false as resolved
            ")
            ->where('qty_available', '<', 0);

        $failedTransactions = StockTransaction::query()
            ->selectRaw("
                'critical' as severity,
                'failed_transaction' as category,
                CONCAT('Transaksi ', transaction_code, ': stok negatif setelah mutasi') as description,
                stock_before as expected_value,
                stock_after as actual_value,
                transaction_date as detected_at,
                false as resolved
            ")
            ->where('stock_after', '<', 0)
            ->where('transaction_date', '>=', now()->subDays(7));

        $dataMismatches = ProductStock::query()
            ->selectRaw("
                'warning' as severity,
                'data_mismatch' as category,
                CONCAT('Reserved (', qty_reserved, ') + Delivery (', qty_on_delivery, ') > Available (', qty_available, ')') as description,
                0 as expected_value,
                (qty_reserved + qty_on_delivery) as actual_value,
                updated_at as detected_at,
                false as resolved
            ")
            ->whereRaw('(qty_reserved + qty_on_delivery) > qty_available')
            ->whereRaw('qty_reserved > 0 OR qty_on_delivery > 0');

        return $negativeStock->unionAll($failedTransactions)->unionAll($dataMismatches);
    }
}
