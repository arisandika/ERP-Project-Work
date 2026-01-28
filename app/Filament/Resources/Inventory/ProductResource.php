<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ProductResource\Pages;
use App\Filament\Resources\Inventory\ProductResource\RelationManagers;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'inventory/products';
    protected static ?string $pluralModelLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        // === KODE PRODUK (Best Practice Input) ===
                        Forms\Components\TextInput::make('product_code')
                            ->label('Kode Produk')
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->placeholder('Kosongkan untuk auto-generate (BRG-XXXXXX)')
                            ->helperText('Kode otomatis dibuat jika dikosongkan')
                            ->prefixIcon('heroicon-o-qr-code'),

                        Forms\Components\TextInput::make('product_name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(100)
                            ->placeholder('Contoh: Laptop Lenovo ThinkPad')
                            ->prefixIcon('heroicon-o-cube'),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-tag')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->maxLength(50)
                                    ->prefixIcon('heroicon-o-tag'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(3),
                            ]),

                        Forms\Components\Select::make('unit_id')
                            ->label('Satuan')
                            ->relationship('unit', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-scale')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Satuan')
                                    ->required()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-scale'),
                                Forms\Components\TextInput::make('symbol')
                                    ->label('Simbol')
                                    ->maxLength(10)
                                    ->prefixIcon('heroicon-o-pencil'),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ]),

                        Forms\Components\TextInput::make('purchase_price')
                            ->label('Harga Beli')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01),

                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Foto Produk')
                            ->directory('products')
                            ->disk('public')
                            ->image()
                            ->imageEditor()
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('4:3')
                            ->openable()
                            ->downloadable()
                            ->preserveFilenames(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Stock per Gudang')
                    ->icon('heroicon-o-building-storefront')
                    ->schema([
                        Forms\Components\Repeater::make('productStocks')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('warehouse_id')
                                    ->label('Gudang')
                                    ->relationship('warehouse', 'warehouse_name', fn($query) => $query->where('is_active', true))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),

                                Forms\Components\TextInput::make('qty')
                                    ->label('Jumlah Stock')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText(
                                        'Stock awal otomatis 0. ' .
                                        'Penambahan Stock dilakukan melalui menu "Transaksi Stock Product".'
                                    )
                                    ->prefixIcon('heroicon-o-archive-box'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'available' => 'Tersedia',
                                        'reserved' => 'Dipesan',
                                        'out_of_stock' => 'Habis',
                                    ])
                                    ->default('available')
                                    ->required()
                                    ->prefixIcon('heroicon-o-adjustments-horizontal'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Stok di Gudang')
                            ->reorderable(false)
                            ->collapsible(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Foto')
                    ->getStateUsing(fn($record) => $record->image_url)
                    ->circular(),

                // === FIX: Ambil dari kolom DB, bukan accessor lagi ===
                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Kode disalin!')
                    ->icon('heroicon-o-qr-code'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-tag')
                    ->color('info'),

                Tables\Columns\TextColumn::make('warehouses')
                    ->label('Gudang')
                    ->getStateUsing(function ($record) {
                        return $record->productStocks()
                            ->with('warehouse')
                            ->get()
                            ->pluck('warehouse.warehouse_name')
                            ->unique()
                            ->implode(', ');
                    })
                    ->badge()
                    ->wrap()
                    ->icon('heroicon-o-building-office'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stock')
                    ->getStateUsing(fn($record) => $record->productStocks()->sum('qty'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn($state) => match (true) {
                        $state <= 0 => 'heroicon-m-x-circle',
                        $state <= 10 => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->suffix(fn($record) => ' ' . ($record->unit->symbol ?? $record->unit->name ?? '')),

                Tables\Columns\TextColumn::make('purchase_price')
                    ->label('Harga Beli')
                    ->money('idr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('idr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('stock')
                    ->label('Filter Stock')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Stock')
                            ->options([
                                'low' => 'Stock Rendah',
                                'normal' => 'Stock Normal',
                            ])
                            ->prefixIcon('heroicon-o-chart-bar'),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn() => Warehouse::orderBy('warehouse_name')
                                ->pluck('warehouse_name', 'id')
                                ->toArray())
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-building-office'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (($data['status'] ?? null) === 'low') {
                            $indicators[] = 'Stok: Rendah';
                        } elseif (($data['status'] ?? null) === 'normal') {
                            $indicators[] = 'Stok: Normal';
                        }
                        if (!empty($data['warehouse_id'])) {
                        if (!empty($data['warehouse_id'])) {
                            $name = Warehouse::find($data['warehouse_id'])?->warehouse_name;
                            if ($name) $indicators[] = 'Gudang: ' . $name;
                        }
                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $status = $data['status'] ?? null;
                        $warehouseId = $data['warehouse_id'] ?? null;

                        if ($status === 'low') {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) $subQuery->where('warehouse_id', $warehouseId);
                                $subQuery->where('qty', '<=', 10);
                            });
                        }

                        if ($status === 'normal') {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) $subQuery->where('warehouse_id', $warehouseId);
                            })->whereDoesntHave('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) $subQuery->where('warehouse_id', $warehouseId);
                                $subQuery->where('qty', '<=', 10);
                            });
                        }

                        return $query;
                    }),

                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductStocksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = Product::whereHas('productStocks', fn($q) => $q->where('qty', '<=', 10))
            ->distinct()
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Produk dengan stok rendah (≤ 10)';
    }
}
