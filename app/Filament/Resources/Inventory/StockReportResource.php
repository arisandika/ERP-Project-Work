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
use App\Filament\Concerns\BelongsToModule;

class StockReportResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'inventory';
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 9;

    protected static ?string $slug = 'inventory/stock-reports';

    protected static ?string $pluralModelLabel = 'Laporan Stock Product';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('product_code')
                    ->label('Kode Product')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('product_name')
                    ->label('Nama Product')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('indigo')
                    ->icon('heroicon-o-tag'),

                Tables\Columns\TextColumn::make('warehouses')
                    ->label('Lokasi Gudang')
                    ->getStateUsing(function ($record) {
                        return $record->productStocks()
                            ->with('warehouse')
                            ->get()
                            ->pluck('warehouse.warehouse_name')
                            ->unique()
                            ->implode(', ');
                    })
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office')
                    ->wrap(),

                Tables\Columns\TextColumn::make('qty_available_total')
                    ->label('Tersedia (Siap Jual)')
                    ->getStateUsing(fn($record) => $record->productStocks()->sum('qty_available'))
                    ->numeric()
                    ->badge()
                    ->color(fn($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty_reserved_total')
                    ->label('Dipesan (Reserved)')
                    ->getStateUsing(fn($record) => $record->productStocks()->sum('qty_reserved'))
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->sortable(),

                Tables\Columns\TextColumn::make('qty_delivery_total')
                    ->label('Pengiriman (Delivery)')
                    ->getStateUsing(fn($record) => $record->productStocks()->sum('qty_on_delivery'))
                    ->numeric()
                    ->badge()
                    ->color('indigo')
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_physical_stock')
                    ->label('Total Fisik Keseluruhan')
                    ->getStateUsing(fn($record) =>
                        $record->productStocks()->sum('qty_available') +
                        $record->productStocks()->sum('qty_reserved') +
                        $record->productStocks()->sum('qty_on_delivery')
                    )
                    ->numeric()
                    ->weight('semibold')
                    ->icon('heroicon-m-archive-box')
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('selling_price')
                    ->label('Harga Jual')
                    ->money('IDR')
                    ->sortable()
                    ->weight('semibold')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('stock')
                    ->label('Filter Stock Tersedia')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->label('Status Tersedia')
                            ->options([
                                'low' => 'Stock Tersedia Rendah (<= 10)',
                                'normal' => 'Stock Tersedia Normal (> 10)',
                            ]),

                        Forms\Components\Select::make('warehouse_id')
                            ->label('Lokasi Gudang')
                            ->options(
                                fn() => Warehouse::query()
                                    ->orderBy('warehouse_name')
                                    ->pluck('warehouse_name', 'id')
                                    ->toArray()
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (($data['status'] ?? null) === 'low') {
                            $indicators[] = 'Status: Hanya Stock Tersedia Rendah';
                        } elseif (($data['status'] ?? null) === 'normal') {
                            $indicators[] = 'Status: Stock Tersedia Normal';
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
                        $status = $data['status'] ?? null;
                        $warehouseId = $data['warehouse_id'] ?? null;

                        if ($status === 'low') {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                if ($warehouseId) {
                                    $subQuery->where('warehouse_id', $warehouseId);
                                }

                                $subQuery->where('qty_available', '<=', 10);
                            });
                        }

                        if ($status === 'normal') {
                            return $query
                                ->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('warehouse_id', $warehouseId);
                                    }
                                })
                                ->whereDoesntHave('productStocks', function ($subQuery) use ($warehouseId) {
                                    if ($warehouseId) {
                                        $subQuery->where('warehouse_id', $warehouseId);
                                    }

                                    $subQuery->where('qty_available', '<=', 10);
                                });
                        }

                        if ($warehouseId && ! $status) {
                            return $query->whereHas('productStocks', function ($subQuery) use ($warehouseId) {
                                $subQuery->where('warehouse_id', $warehouseId);
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
        $count = \App\Models\Inventory\ProductStock::where('qty_available', '<=', 10)
            ->distinct('product_id')
            ->count('product_id');

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Product dengan Stock Tersedia rendah (≤ 10)';
    }
}
