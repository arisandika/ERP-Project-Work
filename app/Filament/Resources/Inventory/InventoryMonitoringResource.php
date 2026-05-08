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
use App\Filament\Concerns\BelongsToModule;

class InventoryMonitoringResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'inventory';
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

                Tables\Columns\TextColumn::make('qty_available')
                    ->label('Siap Jual')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn ($state) => match (true) {
                        $state <= 0 => 'heroicon-m-x-circle',
                        $state <= 5 => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('qty_reserved')
                    ->label('Dipesan (Reserved)')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('qty_on_delivery')
                    ->label('Dikirim (Delivery)')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('total_fisik')
                    ->label('Total Fisik')
                    ->getStateUsing(fn ($record) => $record->qty_available + $record->qty_reserved + $record->qty_on_delivery)
                    ->numeric()
                    ->weight('semibold')
                    ->suffix(' Unit'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Product')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(
                        fn (ProductStock $record): string =>
                        route('filament.admin.resources.inventory.products.view', [
                            'record' => $record->product->id,
                        ])
                    )
                    ->openUrlInNewTab(),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stok Siap Jual ≤ 10')
                    ->query(fn (Builder $query) => $query->where('qty_available', '<=', 10)),
            ])
            ->bulkActions([])
            ->heading('Live Monitoring Stock Gudang')
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMonitorings::route('/'),
        ];
    }
}
