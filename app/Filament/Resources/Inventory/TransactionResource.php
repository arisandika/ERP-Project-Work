<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\TransactionResource\Pages;
use App\Filament\Resources\Inventory\TransactionResource\RelationManagers;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = StockTransaction::class;

    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-circle';
    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Transaksi';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('product_id')
                    ->label('Nama Barang')
                    ->relationship('product', 'product_name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (Product $record): string => $record->product_name . ' (' . 'BRG-' . str_pad($record->id, 6, '0', STR_PAD_LEFT) . ')'),
                
                Forms\Components\DatePicker::make('transaction_date')
                    ->label('Tanggal')
                    ->required()
                    ->default(now())
                    ->displayFormat('d/m/Y'),
                
                Forms\Components\Select::make('type')
                    ->label('Jenis')
                    ->required()
                    ->options([
                        'masuk' => 'Masuk',
                        'keluar' => 'Keluar',
                    ])
                    ->native(false),
                
                Forms\Components\TextInput::make('quantity')
                    ->label('Jumlah')
                    ->required()
                    ->numeric()
                    ->minValue(1),
                
                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->label('Tanggal')
                    ->date('d/m/Y')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('product.product_name')
                    ->label('Nama Barang')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'masuk' => 'success',
                        'keluar' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'masuk' => 'Masuk',
                        'keluar' => 'Keluar',
                    }),
                
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Jumlah')
                    ->numeric()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->notes),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Jenis')
                    ->options([
                        'masuk' => 'Masuk',
                        'keluar' => 'Keluar',
                    ]),
                
                Tables\Filters\Filter::make('transaction_date')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('transaction_date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('transaction_date', 'desc')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('product'));
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

