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
                    ->date('d F Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'masuk'  => 'success',
                        'keluar' => 'danger',
                        default  => 'gray',
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'masuk'  => 'heroicon-m-arrow-down-tray',
                        'keluar' => 'heroicon-m-arrow-up-tray',
                        default  => 'heroicon-m-question-mark-circle',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'masuk'  => 'Masuk',
                        'keluar' => 'Keluar',
                        default  => ucfirst($state),
                    }),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->formatStateUsing(fn($state, $record) => number_format($state) . ' ' . ($record->product->unit->symbol ?? 'pcs'))
                    ->alignRight()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->notes ?? '-'),
            ])
            ->filters([
                Tables\Filters\Filter::make('transaction_date')
                    ->label('Periode Transaksi')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->icon('heroicon-o-calendar'),

                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->displayFormat('d/m/Y')
                            ->icon('heroicon-o-calendar'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
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
