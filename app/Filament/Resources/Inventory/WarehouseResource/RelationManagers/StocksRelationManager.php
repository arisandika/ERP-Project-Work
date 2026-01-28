<?php
namespace App\Filament\Resources\Inventory\WarehouseResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StocksRelationManager extends RelationManager
{
    protected static string $relationship = 'stocks';

    protected static ?string $title = 'Daftar Product di Gudang';

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->recordTitleAttribute('product.product_name')
            ->columns([
                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.category.name')
                    ->label('Kategori')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Stock')
                    ->numeric()
                    ->badge()
                    ->color(fn($record) =>
                        $record->qty <= 0 ? 'danger'
                            : ($record->qty <= $record->product->min_stock ? 'warning' : 'success')
                    ),

                Tables\Columns\TextColumn::make('product.unit.name')
                    ->label('Satuan')
                    ->toggleable(),
            ])
            ->defaultSort('qty', 'asc')
            ->filters([
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Menipis')
                    ->query(fn(Builder $query) =>
                        $query->whereColumn('qty', '<=', 'nx_products.min_stock')
                            ->join('nx_products', 'nx_products.id', '=', 'nx_product_stock.product_id')
                    ),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
