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

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Monitoring Inventory';

    protected static ?string $pluralModelLabel = 'Monitoring Inventory';

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product.name')
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
                    ->label('Stok <= 10')
                    ->query(fn (Builder $query) => $query->where('qty', '<=', 10)),
            ])
            ->actions([
                // Read-only monitor table – no row actions for now
            ])
            ->bulkActions([
                // No bulk actions on monitoring table
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMonitorings::route('/'),
        ];
    }
}


