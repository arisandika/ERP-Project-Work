<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ProductResource\Pages;
use App\Filament\Resources\Inventory\ProductResource\RelationManagers;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
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

                Forms\Components\Section::make('Stok per Gudang')
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
                                    ->label('Jumlah Stok')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-archive-box'),

                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'available'    => 'Tersedia',
                                        'reserved'     => 'Dipesan',
                                        'out_of_stock' => 'Habis',
                                    ])
                                    ->default('available')
                                    ->required()
                                    ->prefixIcon('heroicon-o-adjustments-horizontal'),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Stock di Gudang')
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

                Tables\Columns\TextColumn::make('kode_barang')
                    ->label('Kode Produk')
                    ->getStateUsing(fn($record) => 'BRG-' . str_pad($record->id, 6, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
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
                    ->badge()
                    ->wrap()
                    ->icon('heroicon-o-building-office'),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->getStateUsing(fn($record) => $record->productStocks()->sum('qty'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 0  => 'danger',
                        $state <= 5  => 'danger',
                        $state <= 10 => 'warning',
                        default      => 'success',
                    })
                    ->icon(fn($state) => match (true) {
                        $state <= 0  => 'heroicon-m-x-circle',
                        $state <= 10 => 'heroicon-m-exclamation-triangle',
                        default      => 'heroicon-m-check-circle',
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
            ])
            ->filters([
                Tables\Filters\Filter::make('stock')
                    ->label('Filter Stok')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Stock')
                            ->options([
                                'low'    => 'Stok Rendah',
                                'normal' => 'Stok Normal',
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
                            $indicators[] = 'Status Stok: Hanya Stok Rendah';
                        } elseif (($data['status'] ?? null) === 'normal') {
                            $indicators[] = 'Status Stok: Stok Normal';
                        }
                        if (! empty($data['warehouse_id'])) {
                            $name = Warehouse::find($data['warehouse_id'])?->warehouse_name;
                            if ($name) {
                                $indicators[] = 'Gudang: ' . $name;
                            }
                        }
                        return $indicators;
                    })
                    ->query(function (Builder $query, array $data) {
                        $status      = $data['status'] ?? null;
                        $warehouseId = $data['warehouse_id'] ?? null;

                        if ($status === 'low') {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) {
                                    $subQuery->where('id', $warehouseId);
                                }
                                $subQuery->where('qty', '<=', 10);
                            });
                        }

                        if ($status === 'normal') {
                            return $query
                                ->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('id', $warehouseId);
                                    }
                                })
                                ->whereDoesntHave('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('id', $warehouseId);
                                    }
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
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view'   => Pages\ViewProduct::route('/{record}'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $count = \App\Models\Inventory\ProductStock::where('qty', '<=', 10)
            ->distinct('product_id')
            ->count('id');

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Produk dengan stock rendah (≤ 10)';
    }
}
