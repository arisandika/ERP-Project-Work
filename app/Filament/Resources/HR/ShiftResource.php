<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\ShiftResource\Pages;
use App\Filament\Resources\HR\ShiftResource\RelationManagers;
use App\Models\HR\Shift;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class ShiftResource extends Resource
{
    protected static ?string $model = Shift::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?int $navigationSort = 3;
    
    protected static ?string $slug = 'hr-management/shifts';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Shift Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Shift Name')
                            ->required()
                            ->maxLength(50),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('Start Time')
                            ->required(),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('End Time')
                            ->required(),

                        Forms\Components\TextInput::make('tolerance_minutes')
                            ->label('Tolerance (minutes)')
                            ->numeric()
                            ->default(10)
                            ->required(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Shift Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('start_time')
                    ->label('Start Time')
                    ->time(),

                TextColumn::make('end_time')
                    ->label('End Time')
                    ->time(),

                TextColumn::make('tolerance_minutes')
                    ->label('Tolerance (min)'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn(Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['created_from'] ?? null) {
                            $indicators[] = 'Created from ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Created until ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
                Tables\Filters\TrashedFilter::make()
                    ->label('Deleted Status')
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Shift Details')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')->label('Shift Name'),
                        TextEntry::make('start_time')->label('Start Time')->time(),
                        TextEntry::make('end_time')->label('End Time')->time(),
                        TextEntry::make('tolerance_minutes')->label('Tolerance (minutes)'),
                    ]),
                Section::make('Additional Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')->label('Created At')->dateTime('d F Y H:i'),
                        TextEntry::make('updated_at')->label('Updated At')->dateTime('d F Y H:i'),
                        TextEntry::make('deleted_at')->label('Deleted At')->dateTime('d F Y H:i')->visible(fn($record) => $record->trashed()),
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
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
