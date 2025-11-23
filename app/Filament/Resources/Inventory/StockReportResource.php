<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\StockReportResource\Pages;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockReportResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'inventory/stock-report';

    protected static ?string $pluralModelLabel = 'Laporan Stok Produk';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                    ->label('Kategori Produk')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_stock')
                    ->label('Stok')
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
                    }),

                Tables\Columns\TextColumn::make('unit.name')
                    ->label('Satuan')
                    ->formatStateUsing(fn($state, $record) => $record->unit->symbol ?? $record->unit->name ?? '-')
                    ->badge()
                    ->color('info'),

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
                                'low'    => 'Stok Rendah',
                                'normal' => 'Stok Normal',
                            ]),
                        Forms\Components\Select::make('warehouse_id')
                            ->label('Gudang')
                            ->options(fn() => Warehouse::query()
                                    ->orderBy('warehouse_name')
                                    ->pluck('warehouse_name', 'id')
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
            ->defaultSort('created_at', 'desc')
            ->deferLoading()
            ->paginated([10, 25, 50, 100]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockReports::route('/'),
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
        return 'Produk dengan stok rendah (≤ 10)';
    }
}
