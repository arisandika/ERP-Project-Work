<?php

namespace App\Filament\Widgets;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Support\Colors\Color;

class LowStockAlert extends BaseWidget
{
    use HasPageShield;
    protected static ?int $sort = 1;
    protected int | string | array $columnSpan = 'full';
    protected static ?string $heading = 'Produk dengan Stok Rendah';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ProductStock::query()
                    ->where('qty', '<=', 10)
                    ->with(['product.unit', 'product.category', 'warehouse'])
                    ->orderBy('qty', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.kode_barang')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Produk')
                    ->searchable()
                    ->sortable()
                    ->description(fn (ProductStock $record): string => $record->product->category->category_name ?? '-'),
                
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('qty')
                    ->label('Stok Saat Ini')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->suffix(fn (ProductStock $record): string => ' ' . $record->product->unit->unit_name),
                
                
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
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn (ProductStock $record): string => 
                        route('filament.admin.resources.inventory.products.view', [
                            'record' => $record->product->id
                        ])
                    )
                    ->openUrlInNewTab(),
                
                Tables\Actions\Action::make('restock')
                    ->label('Tambah Stok')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('success')
                    ->url(fn (ProductStock $record): string => 
                        route('filament.admin.resources.inventory.transactions.create', [
                            'product' => $record->id,
                            'warehouse' => $record->id
                        ])
                    ),
            ])
            ->emptyStateHeading('Semua Stok Aman')
            ->emptyStateDescription('Tidak ada produk dengan stock rendah saat ini.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll('30s'); // Auto refresh setiap 30 detik
    }

    public static function canView(): bool
    {
        // Tampilkan widget hanya jika ada low stock
        return ProductStock::where('qty', '<=', 10)->exists();
    }
}

