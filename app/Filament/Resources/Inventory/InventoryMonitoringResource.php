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
                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Produk')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('qty')
                    ->label('Stock Saat Ini')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->alignCenter()
                    ->suffix(fn(ProductStock $record): string => ' ' . $record->product->unit->unit_name),


                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'available' => 'success',
                        'reserved' => 'warning',
                        'out_of_stock' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'available' => 'Tersedia',
                        'reserved' => 'Dipesan',
                        'out_of_stock' => 'Habis',
                        default => $state,
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(
                        fn(ProductStock $record): string =>
                        route('filament.admin.resources.inventory.products.view', [
                            'record' => $record->product->id
                        ])
                    )
                    ->openUrlInNewTab(),

                Tables\Actions\Action::make('restock')
                    ->label('Tambah Stock')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->url(
                        fn(ProductStock $record): string =>
                        route('filament.admin.resources.inventory.transactions.create', [
                            'product' => $record->product->id,
                            'warehouse' => $record->warehouse->id,
                        ])
                    ),
            ])
            ->filters([
                Filter::make('low_stock')
                    ->label('Stock ≤ 10')
                    ->query(fn(Builder $query) => $query->where('qty', '<=', 10)),
            ])
            ->bulkActions([])
            ->heading('Semua Product Stock')
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMonitorings::route('/'),
        ];
    }
}
