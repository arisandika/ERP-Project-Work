<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\OfficeResource\Pages;
use App\Filament\Resources\HR\OfficeResource\RelationManagers;
use App\Models\HR\Office;
use Dotswan\MapPicker\Fields\Map;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OfficeResource extends Resource
{
    protected static ?string $model = Office::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'hr-management/offices';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Office Details')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Office Name')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('radius_meters')
                            ->label('Allowed Radius (meters)')
                            ->numeric()
                            ->default(100)
                            ->required(),
                    ]),

                Forms\Components\Section::make('Location')
                    ->columns(1)
                    ->schema([
                        Map::make('location')
                            ->label('Pick Office Location')
                            ->columnSpanFull()
                            ->defaultLocation(latitude: 0, longitude: 0)
                            ->draggable(true)
                            ->clickable(true)
                            ->zoom(15)
                            ->minZoom(0)
                            ->maxZoom(28)
                            ->tilesUrl("https://tile.openstreetmap.de/{z}/{x}/{y}.png")
                            ->detectRetina(true)
                            ->showMarker(true)
                            ->markerColor("#3b82f6")
                            ->extraStyles([
                                'min-height: 400px',
                                'border-radius: 8px'
                            ])
                            ->afterStateUpdated(function (\Filament\Forms\Set $set, ?array $state): void {
                                $set('latitude', $state['lat']);
                                $set('longitude', $state['lng']);
                            })
                            ->afterStateHydrated(function ($state, $record, Set $set): void {
                                $set('location', ['lat' => $record?->latitude, 'lng' => $record?->longitude]);
                            })
                    ]),

                Forms\Components\TextInput::make('latitude')
                    ->readOnly(),

                Forms\Components\TextInput::make('longitude')
                    ->readOnly()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Office Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('latitude')
                    ->label('Latitude'),

                TextColumn::make('longitude')
                    ->label('Longitude'),

                TextColumn::make('radius_meters')
                    ->label('Radius (m)'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
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
            'index' => Pages\ListOffices::route('/'),
            'create' => Pages\CreateOffice::route('/create'),
            'view' => Pages\ViewOffice::route('/{record}'),
            'edit' => Pages\EditOffice::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
