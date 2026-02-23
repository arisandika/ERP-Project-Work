<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\TransactionResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TransactionResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 7;

    protected static ?string $slug = 'inventory/transactions';

    protected static ?string $pluralModelLabel = 'Transaksi Stock Product';

    public static function form(Form $form): Form
    {
        $calculateTotal = function (callable $get, callable $set) {
            $price = (float) $get('price');
            $qty = max(1, (int) $get('quantity'));

            $set('total_price', $price * $qty);
        };

        return $form
            ->schema([
                Forms\Components\Section::make('Transaksi Barang Masuk')
                    ->schema([

                        Forms\Components\TextInput::make('transaction_code')
                        ->label('No. Transaksi')
                        ->disabled()
                        ->dehydrated()
                        ->unique(ignoreRecord: true)
                        ->prefixIcon('heroicon-o-hashtag'),

                        Forms\Components\Select::make('product_id')
                            ->label('Nama Produk')
                            ->relationship('product', 'product_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->default(fn() => request()->query('product'))
                            ->disabled(fn() => request()->has('product'))
                            ->dehydrated()
                            ->getOptionLabelUsing(
                                fn($value) =>
                                Product::find($value)
                                ? Product::find($value)->product_name
                                : null
                            )
                            ->afterStateHydrated(function ($state, callable $get, callable $set) use ($calculateTotal) {
                                if (!$state) {
                                    return;
                                }

                                $price = Product::find($state)?->purchase_price ?? 0;
                                $set('price', $price);

                                $calculateTotal($get, $set);
                            })
                            ->afterStateUpdated(function ($state, callable $get, callable $set) use ($calculateTotal) {
                                if (!$state) {
                                    $set('price', 0);
                                    $set('total_price', 0);
                                    return;
                                }

                                $price = Product::find($state)?->purchase_price ?? 0;
                                $set('price', $price);

                                $calculateTotal($get, $set);
                            }),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang Tujuan')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(
                                Warehouse::where('is_active', true)
                                    ->pluck('warehouse_name', 'id')
                            )
                            ->default(fn() => request()->query('warehouse'))
                            ->disabled(fn() => request()->has('warehouse'))
                            ->dehydrated(),

                        Forms\Components\Select::make('type')
                            ->label('Jenis Transaksi')
                            ->options([
                                'masuk' => 'Barang Masuk',
                            ])
                            ->default('masuk')
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->default(now()->startOfMonth())
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Qty Masuk')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(
                                fn($state, callable $get, callable $set)
                                => $calculateTotal($get, $set)
                            ),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga Beli per Unit')
                            ->prefix('IDR')
                            ->disabled()
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga')
                            ->prefix('IDR')
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('Total = Harga × Jumlah'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan'),

                    ])
                    ->columns(2),

                Forms\Components\Section::make('Informasi Stock Gudang')
                    ->schema([

                        Forms\Components\Placeholder::make('stock_before')
                            ->label('Stock Sebelum Transaksi')
                            ->reactive()
                            ->content(function (callable $get, $record) {
                                if ($record) {
                                    return $record->stock_before . ' unit';
                                }
                                $productId = $get('product_id');
                                $warehouseId = $get('warehouse_id');

                                if (!$productId || !$warehouseId) {
                                    return '-';
                                }

                                $stock = ProductStock::query()
                                    ->where('product_id', $productId)
                                    ->where('warehouse_id', $warehouseId)
                                    ->value('qty');

                                return $stock !== null
                                    ? "{$stock} unit"
                                    : '0 unit (belum ada Stock)';
                            }),

                        Forms\Components\Placeholder::make('stock_in')
                            ->label('Jumlah Masuk')
                            ->reactive()
                            ->content(
                                fn(callable $get) =>
                                max(1, (int) $get('quantity')) . ' unit'
                            ),

                        Forms\Components\Placeholder::make('stock_after')
                            ->label('Stock Setelah Transaksi')
                            ->reactive()
                            ->content(function (callable $get, $record) {
                                if ($record) {
                                    return $record->stock_after . ' unit';
                                }
                                $productId = $get('product_id');
                                $warehouseId = $get('warehouse_id');
                                $qty = max(1, (int) $get('quantity'));

                                if (!$productId || !$warehouseId) {
                                    return '-';
                                }

                                $currentStock = ProductStock::query()
                                    ->where('product_id', $productId)
                                    ->where('warehouse_id', $warehouseId)
                                    ->value('qty') ?? 0;

                                return ($currentStock + $qty) . ' unit';
                            }),

                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('no_reference')
                    ->label('Ref. Sales')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
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
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn(string $state) => $state === 'masuk' ? 'warning' : 'success')
                    ->formatStateUsing(fn(string $state) => $state === 'masuk' ? 'Masuk/Beli' : 'Keluar/Terjual'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-m-check-circle')
                    ->suffix(' Qty'),

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
                    ->toggleable()
                    ->placeholder('–'),
            ])
            ->filters([

                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
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
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }
}
