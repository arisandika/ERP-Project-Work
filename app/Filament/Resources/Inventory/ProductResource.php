<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ProductResource\Pages;
use App\Filament\Resources\Inventory\ProductResource\RelationManagers;
use App\Models\Inventory\Product;
use App\Models\Inventory\Category;
use App\Models\Inventory\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Barang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\TextInput::make('product_name')
                            ->label('Nama Barang')
                            ->required()
                            ->maxLength(100)
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required()
                                    ->maxLength(50),
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
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Satuan')
                                    ->required()
                                    ->maxLength(20),
                                Forms\Components\TextInput::make('symbol')
                                    ->label('Simbol')
                                    ->maxLength(10),
                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->rows(2),
                            ]),
                        
                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01),
                    ])
                    ->columns(2),
                    
                
                Forms\Components\Section::make('Stok per Gudang')
                    ->schema([
                        Forms\Components\Repeater::make('productStocks')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('id_warehouse')
                                    ->label('Gudang')
                                    ->relationship('warehouse', 'warehouse_name', fn ($query) => $query->where('is_active', true))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->distinct()
                                    ->disableOptionsWhenSelectedInSiblingRepeaterItems(),
                                
                                Forms\Components\TextInput::make('qty')
                                    ->label('Jumlah Stok')
                                    ->required()
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->options([
                                        'available' => 'Tersedia',
                                        'reserved' => 'Dipesan',
                                        'out_of_stock' => 'Habis',
                                    ])
                                    ->default('available')
                                    ->required(),
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Gudang')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['id_warehouse'] 
                                    ? \App\Models\Inventory\Warehouse::find($state['id_warehouse'])?->warehouse_name 
                                    : 'Gudang Baru'
                            ),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('kode_barang')
                    ->label('Kode Barang')
                    ->getStateUsing(fn ($record) => 'BRG-' . str_pad($record->id_product, 6, '0', STR_PAD_LEFT))
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                
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
                    ->separator(',')
                    ->searchable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Total Stok')
                    ->getStateUsing(fn ($record) => $record->productStocks()->sum('qty'))
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn ($state) => match (true) {
                        $state <= 0 => 'heroicon-m-x-circle',
                        $state <= 10 => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->suffix(fn ($record) => ' ' . ($record->unit->symbol ?? $record->unit->name ?? '')),
                
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('idr')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('stock')
                    ->label('Filter Stok')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'low' => 'Stok Rendah',
                                'normal' => 'Stok Normal',
                            ]),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn () => \App\Models\Inventory\Warehouse::query()
                                ->orderBy('warehouse_name')
                                ->pluck('warehouse_name', 'id_warehouse')
                                ->toArray())
                            ->searchable()
                            ->preload(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if (($data['status'] ?? null) === 'low') {
                            $indicators[] = 'Status Stok: Hanya Stok Rendah';
                        } elseif (($data['status'] ?? null) === 'normal') {
                            $indicators[] = 'Status Stok: Stok Normal';
                        }
                        if (!empty($data['warehouse_id'])) {
                            $name = \App\Models\Inventory\Warehouse::find($data['warehouse_id'])?->warehouse_name;
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
                                    $subQuery->where('id_warehouse', $warehouseId);
                                }
                                $subQuery->where('qty', '<=', 10);
                            });
                        }

                        if ($status === 'normal') {
                            return $query
                                ->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('id_warehouse', $warehouseId);
                                    }
                                })
                                ->whereDoesntHave('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('id_warehouse', $warehouseId);
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
        $count = \App\Models\Inventory\ProductStock::where('qty', '<=', 10)
            ->distinct('id_product')
            ->count('id_product');

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
