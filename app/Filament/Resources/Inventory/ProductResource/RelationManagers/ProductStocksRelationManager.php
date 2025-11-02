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
    protected static ?string $title = 'Stok per Gudang';
    protected static ?string $recordTitleAttribute = 'id_stock';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_warehouse')
                    ->label('Gudang')
                    ->relationship('warehouse', 'warehouse_name', fn ($query) => $query->where('is_active', true))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn ($context) => $context === 'edit'),
                
                Forms\Components\TextInput::make('qty')
                    ->label('Jumlah Stok')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->suffix(fn ($get, $record) => $record ? ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? '') : '')
                    ->helperText('Masukkan jumlah stok untuk gudang ini'),
                
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'reserved' => 'Dipesan',
                        'out_of_stock' => 'Habis',
                    ])
                    ->required()
                    ->default('available'),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id_stock')
            ->columns([
                Tables\Columns\TextColumn::make('warehouse.warehouse_name')
                    ->label('Gudang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-m-building-storefront')
                    ->description(fn ($record) => $record->warehouse->location ?? ''),
                
                Tables\Columns\TextColumn::make('qty')
                    ->label('Stok')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 5 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    })
                    ->icon(fn ($state) => match (true) {
                        $state <= 0 => 'heroicon-m-x-circle',
                        $state <= 10 => 'heroicon-m-exclamation-triangle',
                        default => 'heroicon-m-check-circle',
                    })
                    ->suffix(fn ($record) => ' ' . ($record->product->unit->symbol ?? $record->product->unit->unit_name ?? ''))
                    ->description(fn ($state) => match (true) {
                        $state <= 0 => 'Stok Habis',
                        $state <= 5 => 'Stok Kritis',
                        $state <= 10 => 'Stok Rendah',
                        default => null,
                    }),
                
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
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Update')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'available' => 'Tersedia',
                        'reserved' => 'Dipesan',
                        'out_of_stock' => 'Habis',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Stok Gudang')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Tambah Stok ke Gudang')
                    ->successNotificationTitle('Stok berhasil ditambahkan'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Stok Gudang'),
                Tables\Actions\DeleteAction::make()
                    ->modalHeading('Hapus Stok Gudang')
                    ->modalDescription('Apakah Anda yakin ingin menghapus stok di gudang ini?'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada stok di gudang')
            ->emptyStateDescription('Tambahkan stok produk ini ke gudang dengan klik tombol di atas.')
            ->emptyStateIcon('heroicon-o-building-storefront')
            ->defaultSort('qty', 'asc');
    }
}

