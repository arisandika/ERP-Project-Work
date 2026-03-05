<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\LeaveResource\Pages;
use App\Models\HR\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'hr/leaves';

    protected static ?string $pluralModelLabel = 'Cuti';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informasi Cuti')
                ->schema([
                    Forms\Components\TextInput::make('leave_type')
                        ->label('Jenis Cuti')
                        ->required()
                        ->maxLength(255)
                        ->prefixIcon('heroicon-o-document-text'),

                    Forms\Components\TextInput::make('days_count')
                        ->label('Jumlah Hari')
                        ->required()
                        ->numeric()
                        ->prefixIcon('heroicon-o-calendar-days'),

                    Forms\Components\Toggle::make('is_female_only')
                        ->label('Khusus Wanita')
                        ->default(false)
                        ->inline(false),

                    Forms\Components\Toggle::make('male_only')
                        ->label('Khusus Laki-laki')
                        ->default(false)
                        ->inline(false),
                ])
                ->columns(2),
        ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('leave_type')
                    ->label('Jenis Cuti')
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('days_count')
                    ->label('Jumlah Hari')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\ToggleColumn::make('is_female_only')
                    ->label('Khusus Wanita')
                    ->placeholder('—'),

                Tables\Columns\ToggleColumn::make('is_male_only')
                    ->label('Khusus Laki-laki')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Dibuat Hingga')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Cuti'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                // Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Cutii')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('leave_type')
                            ->label('Nama Cuti')
                            ->placeholder('—'),

                        TextEntry::make('days_count')
                            ->label('Jumlah Hari')
                            ->formatStateUsing(fn($state) => $state . ' Hari')
                            ->placeholder('—'),

                        IconEntry::make('is_female_only')
                            ->label('Khusus Wanita')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->placeholder('—'),

                        IconEntry::make('is_male_only')
                            ->label('Khusus Laki-laki')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->placeholder('—'),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d M Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d M Y H:i')
                            ->visible(fn($record) => $record->trashed()),
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            // 'view' => Pages\ViewLeave::route('/{record}'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
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
