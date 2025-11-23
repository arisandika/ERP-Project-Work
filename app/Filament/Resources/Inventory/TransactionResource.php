<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\TransactionResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockTransaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'inventory/transactions';

    protected static ?string $pluralModelLabel = 'Transaksi Stok Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Transaksi')
                    ->schema([
                        Forms\Components\Select::make('product_id')
                            ->label('Nama Produk')
                            ->relationship('product', 'product_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->getOptionLabelFromRecordUsing(fn(Product $record): string =>
                                $record->product_name . ' (' . 'BRG-' . str_pad($record->id, 6, '0', STR_PAD_LEFT) . ')'
                            ),

                        Forms\Components\DateTimePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->required()
                            ->default(now())
                            ->displayFormat('d F Y H:i')
                            ->icon('heroicon-o-calendar-days'),

                        Forms\Components\Select::make('type')
                            ->label('Jenis Transaksi')
                            ->required()
                            ->options([
                                'masuk'  => 'Masuk (Barang Masuk)',
                                'keluar' => 'Keluar (Barang Keluar)',
                            ])
                            ->native(false),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Jumlah Stock Barang')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->suffixIcon('heroicon-o-cube'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Transaksi')
                            ->rows(3)
                            ->placeholder('Contoh: Barang retur, stock opname, atau penyesuaian stock')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d F Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis Transaksi')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'masuk'  => 'success',
                        'keluar' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah Stock')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->suffix(' unit'),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn($record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'masuk'  => 'Masuk',
                        'keluar' => 'Keluar',
                    ]),

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal')
                            ->icon('heroicon-o-calendar-days'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal')
                            ->icon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('transaction_date', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('transaction_date', '<=', $date));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn(Builder $query) => $query->with('product'));
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view'   => Pages\ViewTransaction::route('/{record}'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
