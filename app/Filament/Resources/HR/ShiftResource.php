<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\ShiftResource\Pages;
use App\Models\HR\Shift;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class ShiftResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'hr';
    protected static ?string $model = Shift::class;
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static ?string $navigationGroup = 'Manajemen HR';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'hr/shifts';
    protected static ?string $pluralModelLabel = 'Jam Kerja';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Jam Kerja')
                    ->columns(['default' => 12, 'md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Jam Kerja')
                            ->required()
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-document-text'),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Waktu Mulai')
                            ->required()
                            ->prefixIcon('heroicon-o-clock')
                            ->native(false),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Waktu Selesai')
                            ->required()
                            ->prefixIcon('heroicon-o-clock')
                            ->native(false),
                        Forms\Components\TextInput::make('tolerance_minutes')
                            ->label('Toleransi (menit)')
                            ->numeric()
                            ->default(10)
                            ->required()
                            ->prefixIcon('heroicon-o-exclamation-circle'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Jam Kerja')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('Waktu Mulai')
                    ->time()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('Waktu Selesai')
                    ->time()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('tolerance_minutes')
                    ->label('Toleransi')
                    ->formatStateUsing(fn($state) => $state . ' Menit')
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Jam Kerja'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Jam Kerja')
                    ->columns(['default' => 12, 'md' => 2])
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Jam Kerja')
                            ->placeholder('—'),
                        TextEntry::make('start_time')
                            ->label('Waktu Mulai')
                            ->time()
                            ->placeholder('—'),
                        TextEntry::make('end_time')
                            ->label('Waktu Selesai')
                            ->time()
                            ->placeholder('—'),
                        TextEntry::make('tolerance_minutes')
                            ->label('Toleransi')
                            ->formatStateUsing(fn($state) => $state . ' Menit')
                            ->placeholder('—'),
                    ]),
                Section::make('Pengelolaan Data')
                    ->columns(['default' => 12, 'md' => 2])
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
            'index' => Pages\ListShifts::route('/'),
            'create' => Pages\CreateShift::route('/create'),
            // 'view' => Pages\ViewShift::route('/{record}'),
            'edit' => Pages\EditShift::route('/{record}/edit'),
        ];
    }
}
