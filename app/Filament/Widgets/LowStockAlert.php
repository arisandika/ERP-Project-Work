<?php

namespace App\Filament\Widgets;

use App\Models\Inventory\ProductStock;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LowStockAlert extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    // REVISI: Copywriting disesuaikan
    protected static ?string $heading = 'Peringatan: Product dengan Stock Tersedia Rendah';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                // REVISI: Ganti qty menjadi qty_available
                fn() => ProductStock::query()
                    ->where('qty_available', '<=', 10)
                    ->with(['product.unit', 'product.category', 'warehouse'])
                    ->orderBy('qty_available', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.product_code')
                    ->label('Kode Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Product')
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

                // REVISI: Ganti qty menjadi qty_available
                Tables\Columns\TextColumn::make('qty_available')
                    ->label('Stock Siap Jual')
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
                    ->suffix(' Unit'),

                // TAMBAHAN: Menampilkan stok yang tertahan/dipesan
                Tables\Columns\TextColumn::make('qty_reserved')
                    ->label('Dipesan (Reserved)')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->suffix(' Unit'),

                // REVISI: Menghapus kolom 'status' (enum) yang sudah tidak ada
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(
                        fn(ProductStock $record): string =>
                        route('filament.admin.resources.inventory.products.view', [
                            'record' => $record->product->id
                        ])
                    )
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('Semua Stock Tersedia Aman')
            ->emptyStateDescription('Tidak ada Product dengan stock siap jual yang rendah saat ini.')
            ->emptyStateIcon('heroicon-o-check-circle')
            ->poll('30s');
    }

    public static function canView(): bool
    {
        // REVISI: Ganti qty menjadi qty_available
        return ProductStock::where('qty_available', '<=', 10)->exists();
    }
}
