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
use Closure;

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
                Forms\Components\Section::make('Transaksi Barang Mutasi')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_code')
                            ->label('No. Transaksi')
                            ->disabled()
                            ->dehydrated()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-hashtag'),

                        Forms\Components\TextInput::make('reference_number')
                            ->label('Nomor Referensi (Opsional)')
                            ->maxLength(255),

                        Forms\Components\Select::make('product_id')
                            ->label('Nama Product')
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
                                if (!$state)
                                    return;
                                $product = Product::find($state);
                                $set('price', $product?->purchase_price ?? 0);
                                $set('is_serialized', $product?->is_serialized ?? false);
                                $calculateTotal($get, $set);
                            })
                            ->afterStateUpdated(function ($state, callable $get, callable $set) use ($calculateTotal) {
                                if (!$state) {
                                    $set('price', 0);
                                    $set('total_price', 0);
                                    $set('is_serialized', false);
                                    return;
                                }
                                $product = Product::find($state);
                                $set('price', $product?->purchase_price ?? 0);
                                $set('is_serialized', (bool) ($product?->is_serialized ?? false));
                                $calculateTotal($get, $set);
                            }),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang Tujuan')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->options(Warehouse::where('is_active', true)->pluck('warehouse_name', 'id'))
                            ->default(fn() => request()->query('warehouse'))
                            ->disabled(fn() => request()->has('warehouse'))
                            ->dehydrated(),

                        Forms\Components\Select::make('mutation_type')
                            ->label('Jenis Mutasi')
                            ->options([
                                'stock_in' => 'Barang Masuk',
                                'reserve' => 'Dipesan (Reserved)',
                                'delivery' => 'Pengiriman (Keluar)',
                                'complete' => 'Selesai Terjual',
                                'cancel' => 'Batal (Masuk Kembali)', // Tambahan biar komplit
                                'adjustment_out' => 'Koreksi Stok Keluar',
                            ])
                            ->default('stock_in')
                            ->required(),

                        Forms\Components\DatePicker::make('transaction_date')
                            ->label('Tanggal Transaksi')
                            ->default(now())
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\TextInput::make('quantity')
                            ->label('Qty Mutasi')
                            ->numeric()
                            ->required()
                            ->minValue(1)
                            ->default(1)
                            ->reactive()
                            ->afterStateUpdated(fn($state, callable $get, callable $set) => $calculateTotal($get, $set)),

                        Forms\Components\Hidden::make('is_serialized'),

                        Forms\Components\Textarea::make('scanned_sns')
                            ->label('Scan Serial Number (SN)')
                            ->helperText('Gunakan Barcode Scanner. Pastikan 1 SN per baris. Jumlah scan harus sama dengan Qty Mutasi.')
                            ->rows(8)
                            ->columnSpanFull()
                            ->visible(fn(Forms\Get $get): bool => $get('is_serialized') === true)
                            ->required(fn(Forms\Get $get): bool => $get('is_serialized') === true)
                            ->rules([
                                fn(Forms\Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
                                    if (!$get('is_serialized'))
                                        return;
                                    $sns = array_filter(array_map('trim', explode("\n", $value)));
                                    $qty = (int) $get('quantity');
                                    if (count($sns) !== $qty) {
                                        $fail("ERROR: Jumlah SN yang discan (" . count($sns) . ") TIDAK SAMA dengan Qty Mutasi ({$qty}).");
                                    }
                                    if (count($sns) !== count(array_unique($sns))) {
                                        $fail("ERROR: Terdapat Serial Number yang duplikat dalam hasil scan Anda.");
                                    }
                                },
                            ]),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga Beli per Unit')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->dehydrated(true),

                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0)
                            ->disabled()
                            ->dehydrated(true)
                            ->helperText('Total = Harga × Jumlah'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan'),

                    ])->columns(2),

                Forms\Components\Section::make('Informasi Stock Gudang')
                    ->schema([
                        Forms\Components\Placeholder::make('stock_before')
                            ->label('Stock Siap Jual Saat Ini')
                            ->reactive()
                            ->content(function (callable $get, $record) {
                                if ($record)
                                    return $record->stock_before . ' unit';
                                $productId = $get('product_id');
                                $warehouseId = $get('warehouse_id');
                                if (!$productId || !$warehouseId)
                                    return '-';
                                $stock = ProductStock::query()
                                    ->where('product_id', $productId)
                                    ->where('warehouse_id', $warehouseId)
                                    ->value('qty_available');
                                return $stock !== null ? "{$stock} unit" : '0 unit (belum ada Stock)';
                            }),

                        Forms\Components\Placeholder::make('stock_in')
                            ->label('Jumlah Mutasi')
                            ->reactive()
                            ->content(fn(callable $get) => max(1, (int) $get('quantity')) . ' unit'),

                        Forms\Components\Placeholder::make('stock_after')
                            ->label('Estimasi Stock Setelah Transaksi')
                            ->reactive()
                            ->content(function (callable $get, $record) {
                                if ($record)
                                    return $record->stock_after . ' unit';
                                $productId = $get('product_id');
                                $warehouseId = $get('warehouse_id');
                                $qty = max(1, (int) $get('quantity'));
                                $mutationType = $get('mutation_type');
                                if (!$productId || !$warehouseId)
                                    return '-';
                                $currentStock = ProductStock::query()
                                    ->where('product_id', $productId)
                                    ->where('warehouse_id', $warehouseId)
                                    ->value('qty_available') ?? 0;

                                $estimated = in_array($mutationType, ['stock_in', 'cancel'])
                                    ? $currentStock + $qty
                                    : $currentStock - $qty;
                                return $estimated . ' unit';
                            }),
                    ])->columns(3)->columnSpanFull(),
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
                    ->weight('semibold'),

                // Diubah untuk sinkronisasi dengan command simulator kita (yang masukin no_reference)
                Tables\Columns\TextColumn::make('no_reference')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Product')
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

                // ===== INI BAGIAN YANG DIUBAH (LEBIH CLEAN & JELAS) =====
                Tables\Columns\TextColumn::make('type')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'danger', // Keluar jadi merah biar gampang dibedain
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => strtoupper("STOCK " . $state)),

                Tables\Columns\TextColumn::make('mutation_type')
                    ->label('Detail Mutasi')
                    ->formatStateUsing(fn(string $state): string => strtoupper(str_replace('_', ' ', $state)))
                    ->color('gray')
                    ->size('sm'),
                // ========================================================

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->badge()
                    ->color('primary') // Warnanya diganti primary biar ga bentrok ijonya sama stock_in
                    ->icon('heroicon-m-cube')
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Input Oleh')
                    ->toggleable(isToggledHiddenByDefault: true), // Disembunyikan by default biar tabel ga kepanjangan

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
                            ->displayFormat('d M Y')->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->displayFormat('d M Y')->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date))
                            ->when($data['created_until'], fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('product_id')->relationship('product', 'product_name')->searchable(),
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'warehouse_name'),

                // Filter diubah berdasarkan tipe Masuk / Keluar
                Tables\Filters\SelectFilter::make('type')
                    ->label('Arah Stok (In/Out)')
                    ->options([
                        'masuk' => 'Stock Masuk',
                        'keluar' => 'Stock Keluar',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('transaction_date', 'desc');
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
