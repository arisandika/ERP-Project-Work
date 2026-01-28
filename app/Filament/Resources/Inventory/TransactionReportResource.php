<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\TransactionReportResource\Pages;
use App\Models\Inventory\StockTransaction;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionReportResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'inventory/transaction-report';

    protected static ?string $pluralModelLabel = 'Laporan Transaksi';

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d F Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state) => $state === 'masuk' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->badge()
                    ->color('info')
                    ->suffix(' unit')
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga Satuan')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Input Oleh')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->tooltip(fn($record) => $record->notes)
                    ->toggleable(),
            ])
            ->filters([

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('transaction_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('transaction_date', '<=', $data['until'])
                            );
                    }),

                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Nama Produk')
                    ->relationship('product', 'product_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'warehouse_name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('created_by')
                    ->label('Input Oleh')
                    ->relationship('creator', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'masuk' => 'Masuk',
                        'keluar' => 'Keluar',
                    ]),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->with(['product.unit']))
            ->deferLoading()
            ->paginated([10, 25, 50, 100]);
    }

    // Disable CRUD actions
    public static function canCreate(): bool
    {return false;}
    public static function canEdit($record): bool
    {return false;}
    public static function canDelete($record): bool
    {return false;}

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactionReports::route('/'),
        ];
    }
}
