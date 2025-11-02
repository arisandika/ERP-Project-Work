<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\WarehouseResource\Pages;
use App\Filament\Resources\Inventory\WarehouseResource\RelationManagers;
use App\Models\Inventory\Warehouse;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WarehouseResource extends Resource
{
    
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationLabel = 'Gudang';
    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Gudang')
                    ->schema([
                        Forms\Components\TextInput::make('warehouse_name')
                            ->label('Nama Gudang')
                            ->required()
                            ->maxLength(100)
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: Gudang Utama')
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('location')
                            ->label('Alamat/Lokasi')
                            ->required()
                            ->maxLength(255)
                            ->rows(3)
                            ->placeholder('Masukkan alamat lengkap gudang')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('manager_name')
                            ->label('Nama Penanggung Jawab')
                            ->maxLength(100)
                            ->placeholder('Nama manager gudang'),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('08xx-xxxx-xxxx'),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->helperText('Gudang aktif dapat digunakan untuk transaksi stok')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
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
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(50),
                
                Tables\Columns\TextColumn::make('manager_name')
                    ->label('Penanggung Jawab')
                    ->searchable()
                    ->sortable()
                    ->default('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('total_products')
                    ->label('Jumlah Produk')
                    ->getStateUsing(fn ($record) => $record->stocks()->distinct('id_product')->count('id_product'))
                    ->badge()
                    ->color('info')
                    ->suffix(' items'),
                
                Tables\Columns\TextColumn::make('total_qty')
                    ->label('Total Stok')
                    ->getStateUsing(fn ($record) => $record->stocks()->sum('qty'))
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->suffix(' unit'),
                
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('Semua Gudang')
                    ->trueLabel('Gudang Aktif')
                    ->falseLabel('Gudang Nonaktif'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Gudang hanya dapat dihapus jika tidak memiliki data stok.'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
