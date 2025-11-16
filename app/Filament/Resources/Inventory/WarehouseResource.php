<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\WarehouseResource\Pages;
use App\Filament\Resources\Inventory\WarehouseResource\RelationManagers;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'inventory/warehouses';

    protected static ?string $pluralModelLabel = 'Gudang';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Gudang')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('warehouse_name')
                                    ->label('Nama Gudang')
                                    ->required()
                                    ->maxLength(100)
                                    ->unique(ignoreRecord: true)
                                    ->placeholder('Contoh: Gudang Utama')
                                    ->prefixIcon('heroicon-o-home-modern'),

                                Forms\Components\TextInput::make('manager_name')
                                    ->label('Nama Penanggung Jawab')
                                    ->maxLength(100)
                                    ->placeholder('Nama manager gudang')
                                    ->prefixIcon('heroicon-o-user-circle'),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No. Telepon')
                                    ->tel()
                                    ->maxLength(20)
                                    ->placeholder('08xx-xxxx-xxxx')
                                    ->prefixIcon('heroicon-o-phone'),

                                Forms\Components\TextInput::make('maps_url')
                                    ->label('Link Google Maps')
                                    ->url()
                                    ->placeholder('https://maps.google.com/...')
                                    ->prefixIcon('heroicon-o-map-pin'),
                            ]),

                        Forms\Components\Textarea::make('location')
                            ->label('Alamat / Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->rows(3)
                            ->placeholder('Masukkan alamat lengkap gudang')
                            ->columnSpanFull(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Gudang aktif dapat digunakan untuk transaksi stock')
                            ->onIcon('heroicon-s-check-circle')
                            ->offIcon('heroicon-s-x-circle')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('warehouse_name')
                    ->label('Nama Gudang')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(60)
                    ->wrap()
                    ->tooltip(fn($record) => $record->location)
                    ->searchable(),

                Tables\Columns\TextColumn::make('manager_name')
                    ->label('Penanggung Jawab')
                    ->default('-')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->badge()
                    ->color('info')
                    ->searchable(),

                Tables\Columns\TextColumn::make('maps_url')
                    ->label('Maps')
                    ->formatStateUsing(fn($state) => $state ? 'Lihat Maps' : '-')
                    ->url(fn($state) => $state ?: null, shouldOpenInNewTab: true)
                    ->badge()
                    ->color(fn($state) => $state ? 'primary' : 'gray'),

                Tables\Columns\TextColumn::make('total_products')
                    ->label('Jumlah Produk')
                    ->getStateUsing(fn($record) => $record->stocks()->distinct('product_id')->count('id'))
                    ->badge()
                    ->color('info')
                    ->suffix(' items'),

                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total Stok')
                    ->getStateUsing(fn($record) => $record->stocks()->sum('qty'))
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->suffix(' unit'),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-s-x-circle')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d F Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Gudang')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')->label('Dari'),
                        Forms\Components\DatePicker::make('until')->label('Sampai'),
                    ])
                    ->query(
                        fn(Builder $query, array $data) =>
                        $query
                            ->when($data['from'], fn($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn($q, $date) => $q->whereDate('created_at', '<=', $date))
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Gudang hanya dapat dihapus jika tidak memiliki data stock.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Gudang')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('warehouse_name')->label('Nama Gudang'),
                        TextEntry::make('manager_name')->label('Penanggung Jawab'),
                        TextEntry::make('phone')->label('No. Telepon')->badge()->color('info'),
                        TextEntry::make('location')->label('Alamat / Lokasi'),
                        TextEntry::make('maps_url')
                            ->label('Tautan Maps')
                            ->formatStateUsing(fn($state) => $state ? 'Buka Google Maps' : '-')
                            ->url(fn($state) => $state ?: null, shouldOpenInNewTab: true)
                            ->badge()
                            ->color(fn($state) => $state ? 'primary' : 'gray'),
                    ]),

                Section::make('Statistik Gudang')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('total_products')
                            ->label('Jumlah Produk')
                            ->badge()
                            ->color('info')
                            ->state(fn(Warehouse $record) => $record->stocks()->distinct('product_id')->count('id'))
                            ->suffix(' items'),

                        TextEntry::make('total_qty')
                            ->label('Total Stok')
                            ->badge()
                            ->color('success')
                            ->state(fn(Warehouse $record) => $record->stocks()->sum('qty'))
                            ->suffix(' unit'),
                    ]),

                Section::make('Status & Aktivitas')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Status Aktif')
                            ->badge()
                            ->color(fn($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn($state) => $state ? 'Aktif' : 'Nonaktif'),

                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d F Y H:i'),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'view' => Pages\ViewWarehouse::route('/{record}'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
