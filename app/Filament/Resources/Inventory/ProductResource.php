<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ProductResource\Pages;
use App\Filament\Resources\Inventory\ProductResource\RelationManagers;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\BelongsToModule;

class ProductResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'Inventory';
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'inventory/products';

    protected static ?string $pluralModelLabel = 'Product';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Product')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('Nama Product')
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
                                    ->maxLength(10),

                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ]),

                        Forms\Components\TextInput::make('selling_price')
                            ->label('Harga Jual')
                            ->numeric()
                            ->prefix('IDR')
                            ->required()
                            ->minValue(0),

                        Forms\Components\Section::make('Pengaturan Lanjutan')
                            ->description('Atur identitas unit dan visibilitas katalog')
                            ->schema([
                                Forms\Components\Toggle::make('is_serialized')
                                    ->label('Wajibkan Serial Number (SN)')
                                    ->helperText('Aktifkan jika produk ini butuh scan SN untuk setiap unitnya (Barang IT).')
                                    ->default(false)
                                    ->live()
                                    ->onColor('success')
                                    ->offColor('gray'),

                                Toggle::make('is_web_published')
                                    ->label('Tampilkan di Katalog Web')
                                    ->helperText('Jika aktif, produk ini akan muncul di halaman Company Profile.')
                                    ->default(false)
                                    ->onColor('info')
                                    ->offColor('gray'),
                            ])
                            ->columns(2),

                        Forms\Components\FileUpload::make('image_path')
                            ->label('Foto Product')
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

                                Forms\Components\TextInput::make('qty_available')
                                    ->label('Stock Tersedia')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Otomatis 0. Tambah via proses stok masuk / mutasi sistem.')
                                    ->prefixIcon('heroicon-o-archive-box'),

                                Forms\Components\TextInput::make('qty_reserved')
                                    ->label('Dipesan (Reserved)')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(true),

                                Forms\Components\TextInput::make('qty_on_delivery')
                                    ->label('Dalam Pengiriman')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(true),
                            ])
                            ->columns(4)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Akses Gudang')
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

                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('indigo')
                    ->icon('heroicon-o-tag'),

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
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stock Fisik')
                    ->getStateUsing(
                        fn($record) =>
                        $record->productStocks()->sum('qty_available') +
                        $record->productStocks()->sum('qty_reserved') +
                        $record->productStocks()->sum('qty_on_delivery')
                    )
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
                    ->suffix(' Qty'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),
            ])
            ->filters([
                Tables\Filters\Filter::make('stock')
                    ->label('Filter Stock')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Stock')
                            ->options([
                                'low' => 'Stock Tersedia Rendah',
                                'normal' => 'Stock Tersedia Normal',
                            ])
                            ->prefixIcon('heroicon-o-chart-bar'),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(
                                fn() => Warehouse::orderBy('warehouse_name')
                                    ->pluck('warehouse_name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->prefixIcon('heroicon-o-building-office'),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (($data['status'] ?? null) === 'low') {
                            $indicators[] = 'Status Stock: Hanya Stock Rendah';
                        } elseif (($data['status'] ?? null) === 'normal') {
                            $indicators[] = 'Status Stock: Stock Normal';
                        }

                        if (!empty($data['warehouse_id'])) {
                            $name = Warehouse::find($data['warehouse_id'])?->warehouse_name;

                            if ($name) {
                                $indicators[] = 'Gudang: ' . $name;
                            }
                        }

                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $status = $data['status'] ?? null;
                        $warehouseId = $data['warehouse_id'] ?? null;

                        if ($status === 'low') {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) {
                                    $subQuery->where('warehouse_id', $warehouseId);
                                }

                                $subQuery->where('qty_available', '<=', 10);
                            });
                        }

                        if ($status === 'normal') {
                            return $query
                                ->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('warehouse_id', $warehouseId);
                                    }
                                })
                                ->whereDoesntHave('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('warehouse_id', $warehouseId);
                                    }

                                    $subQuery->where('qty_available', '<=', 10);
                                });
                        }

                        if ($warehouseId) {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                $subQuery->where('warehouse_id', $warehouseId);
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
        $count = \App\Models\Inventory\ProductStock::where('qty_available', '<=', 10)
            ->distinct('product_id')
            ->count('product_id');

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Product dengan stock tersedia rendah (≤ 10)';
    }
}
