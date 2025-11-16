<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\InventoryMonitoringResource\Pages;
use App\Models\Inventory\ProductStock;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryMonitoringResource extends Resource
{
    protected static ?string $model = ProductStock::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'inventory/monitoring-transactions';

    protected static ?string $pluralModelLabel = 'Monitoring Inventory';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Produk')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Qty')
                    ->sortable()
                    ->alignRight(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'available',
                        'warning' => 'reserved',
                        'danger' => 'out_of_stock',
                    ]),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stok ≤ 10')
                    ->query(fn (Builder $query) => $query->where('qty', '<=', 10)),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMonitorings::route('/'),
        ];
    }
}


