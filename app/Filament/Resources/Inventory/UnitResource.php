<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\UnitResource\Pages;
use App\Filament\Resources\Inventory\UnitResource\RelationManagers;
use App\Models\Inventory\Unit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UnitResource extends Resource
{
    protected static ?string $model = Unit::class;

    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Satuan';

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Satuan')
                    ->required()
                    ->maxLength(20)
                    ->unique(ignoreRecord: true),
                
                Forms\Components\TextInput::make('symbol')
                    ->label('Simbol')
                    ->maxLength(10)
                    ->placeholder('Contoh: pcs, kg, m, dll'),
                
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->rows(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Satuan')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('symbol')
                    ->label('Simbol')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Jumlah')
                    ->counts('products')
                    ->numeric()
                    ->sortable()
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListUnits::route('/'),
            'create' => Pages\CreateUnit::route('/create'),
            'edit' => Pages\EditUnit::route('/{record}/edit'),
        ];
    }
}
