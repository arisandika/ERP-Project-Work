<?php

namespace App\Filament\Resources\Inventory\ProductResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductStocksRelationManager extends RelationManager
{
    protected static string $relationship = 'productStocks';
    protected static ?string $title = 'Distribusi Stock per Gudang';
    protected static ?string $recordTitleAttribute = 'id';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('warehouse_id')
                    ->label('Gudang')
                    ->relationship('warehouse', 'warehouse_name', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload()
                    // Cegah perubahan gudang saat edit untuk menjaga integritas data stock
                    ->disabled(fn ($context) => $context === 'edit'),

                // REVISI: Mengganti qty dan status menjadi 3 kolom metric
                Forms\Components\Grid::make(['default' => 1, 'sm' => 3]) // Menggunakan Grid agar sejajar
                    ->schema([
                        Forms\Components\TextInput::make('qty_available')
                            ->label('Stock Tersedia')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix(fn ($get, $record) => $record ? ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '') : '')
                            ->helperText('Siap dijual'),

                        Forms\Components\TextInput::make('qty_reserved')
                            ->label('Dipesan')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix(fn ($get, $record) => $record ? ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '') : '')
                            ->helperText('Menunggu pengiriman'),

                        Forms\Components\TextInput::make('qty_on_delivery')
                            ->label('Dalam Pengiriman')
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->suffix(fn ($get, $record) => $record ? ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '') : '')
                            ->helperText('Sedang di jalan'),
                    ]),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('warehouse_id')
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office')
                    ->description(fn ($record) => $record->warehouse->location ?? ''),

                // REVISI: Menampilkan 3 kolom quantity
                Tables\Columns\TextColumn::make('qty_available')
                    ->label('Stock Tersedia')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->suffix(fn ($record) => ' ' . ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? ''))
                    // Memindahkan logika alert ke qty_available
                    ->description(fn ($state) => match (true) {
                        $state <= 0 => 'Stock Habis',
                        $state <= 5 => 'Stock Kritis',
                        $state <= 10 => 'Stock Rendah',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('qty_reserved')
                    ->label('Dipesan (Reserved)')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->suffix(fn ($record) => ' ' . ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '')),

                Tables\Columns\TextColumn::make('qty_on_delivery')
                    ->label('Pengiriman')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('indigo')
                    ->suffix(fn ($record) => ' ' . ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '')),

                // Tambahan Arsitektur: Total Stock Fisik di gudang tersebut
                Tables\Columns\TextColumn::make('total_physical')
                    ->label('Total Fisik')
                    ->getStateUsing(fn($record) =>
                        $record->qty_available + $record->qty_reserved + $record->qty_on_delivery
                    )
                    ->numeric()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // REVISI: Filter diubah dari Enum Status menjadi Treshold Quantity
                Tables\Filters\Filter::make('low_stock')
                    ->label('Hanya Stock Rendah')
                    ->toggle()
                    ->query(fn (Builder $query): Builder => $query->where('qty_available', '<=', 10)),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Akses Gudang Baru')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Buka Akses Stock di Gudang Baru')
                    ->successNotificationTitle('Akses gudang berhasil ditambahkan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Stock Manual'),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Akses Gudang')
                    ->modalDescription('Apakah Anda yakin ingin menghapus data stock di gudang ini?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum didistribusikan ke gudang')
            ->emptyStateDescription('Tambahkan akses gudang untuk produk ini.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->defaultSort('qty_available', 'asc');
    }
}
