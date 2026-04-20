<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\TransactionResource\Pages;
use App\Models\Inventory\StockTransaction;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TransactionResource extends Resource
{
    protected static ?string $model = StockTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?int $navigationSort = 7;
    protected static ?string $slug = 'inventory/transactions';
    protected static ?string $pluralModelLabel = 'Transaksi Stock Product';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('No. Transaksi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Referensi')
                    ->searchable()
                    ->sortable()
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

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

                Tables\Columns\TextColumn::make('type')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => strtoupper('STOCK ' . $state)),

                Tables\Columns\TextColumn::make('mutation_type')
                    ->label('Detail Mutasi')
                    ->formatStateUsing(fn (string $state): string => strtoupper(str_replace('_', ' ', $state)))
                    ->color('gray')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-cube')
                    ->suffix(' Unit'),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Input Oleh')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->tooltip(fn ($record) => $record->notes)
                    ->toggleable()
                    ->placeholder('–'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
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
}
