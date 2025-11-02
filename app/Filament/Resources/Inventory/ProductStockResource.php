<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ProductStockResource\Pages;
use App\Filament\Resources\Inventory\ProductStockResource\RelationManagers;
use App\Models\Inventory\ProductStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductStockResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Stok Produk';
    protected static ?int $navigationSort = 6;
    protected static bool $shouldRegisterNavigation = false; // Hidden dari menu

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stok')
                    ->schema([
                        Forms\Components\Select::make('id_product')
                            ->label('Produk')
                            ->relationship('product', 'product_name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($operation) => $operation === 'edit'),
                        
                        Forms\Components\Select::make('id_warehouse')
                            ->label('Gudang')
                            ->relationship('warehouse', 'warehouse_name', fn ($query) => $query->where('is_active', true))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->disabled(fn ($operation) => $operation === 'edit'),
                        
                        Forms\Components\TextInput::make('qty')
                            ->label('Jumlah Stok')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'available' => 'Tersedia',
                                'reserved' => 'Dipesan',
                                'out_of_stock' => 'Habis',
                            ])
                            ->required()
                            ->default('available'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.kode_barang')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->product->category->category_name ?? '-'),
                
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('qty')
                    ->label('Stok')
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
                    ->suffix(fn ($record) => ' ' . ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '')),
                
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'reserved' => 'Dipesan',
                        'out_of_stock' => 'Habis',
                        default => $state,
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('id_warehouse')
                    ->label('Gudang')
                    ->relationship('warehouse', 'warehouse_name')
                    ->searchable()
                    ->preload(),
                
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'reserved' => 'Dipesan',
                        'out_of_stock' => 'Habis',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_low_stock')
                    ->label('Stok Rendah')
                    ->placeholder('Semua')
                    ->trueLabel('Stok Rendah')
                    ->falseLabel('Stok Normal')
                    ->query(function (Builder $query, $state) {
                        if ($state === true) {
                            return $query->where('nx_product_stock.qty', '<=', 10);
                        } elseif ($state === false) {
                            return $query->where('nx_product_stock.qty', '>', 10);
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('qty', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductStocks::route('/'),
            'create' => Pages\CreateProductStock::route('/create'),
            'edit' => Pages\EditProductStock::route('/{record}/edit'),
        ];
    }
}

