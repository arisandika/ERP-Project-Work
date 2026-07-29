<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\HolidayResource\Pages;
use App\Models\HR\Holiday;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class HolidayResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'hr';
    protected static ?string $model  = Holiday::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'hr/holidays';

    protected static ?string $pluralModelLabel = 'Hari Libur';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Hari Libur')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Hari Libur')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(), // Dibuat penuh 1 baris

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('start_date')
                                    ->label('Dari Tanggal')
                                    ->required()
                                    ->minDate('2026-01-01')
                                    ->maxDate('2026-12-31')
                                    ->displayFormat('d M Y')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(function (\Filament\Forms\Set $set, \Filament\Forms\Get $get, $state) {
                                        if (! $get('end_date') || $get('end_date') < $state) {
                                            $set('end_date', $state);
                                        }
                                    }),

                                Forms\Components\DatePicker::make('end_date')
                                    ->label('Hingga Tanggal')
                                    ->required()
                                    ->minDate('2026-01-01')
                                    ->maxDate('2026-12-31')
                                    ->displayFormat('d M Y')
                                    ->afterOrEqual('start_date')
                                    ->native(false),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->label('Keterangan')
                            ->nullable()
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Hari Libur')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_range')
                    ->label('Tanggal')
                    ->getStateUsing(function (Holiday $record) {
                        $start = Carbon::parse($record->start_date)->translatedFormat('d M Y');
                        $end   = Carbon::parse($record->end_date)->translatedFormat('d M Y');

                        // Jika libur hanya 1 hari, tampilkan 1 tanggal saja
                        return $start === $end ? $start : "$start - $end";
                    })
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('start_date', $direction);
                    }),

                Tables\Columns\TextColumn::make('day_range')
                    ->label('Hari')
                    ->getStateUsing(function (Holiday $record) {
                        $startDay = Carbon::parse($record->start_date)->translatedFormat('l');
                        $endDay   = Carbon::parse($record->end_date)->translatedFormat('l');

                        return $startDay === $endDay ? $startDay : "$startDay - $endDay";
                    })
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50)
                    ->placeholder('-'),

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
                Tables\Filters\SelectFilter::make('month')
                    ->label('Bulan')
                    ->options([
                        '1'  => 'Januari',
                        '2'  => 'Februari',
                        '3'  => 'Maret',
                        '4'  => 'April',
                        '5'  => 'Mei',
                        '6'  => 'Juni',
                        '7'  => 'Juli',
                        '8'  => 'Agustus',
                        '9'  => 'September',
                        '10' => 'Oktober',
                        '11' => 'November',
                        '12' => 'Desember',
                    ])
                    ->query(
                        // Cek apakah start_date ATAU end_date ada di bulan yang dipilih
                        fn($query, $data) =>
                        $data['value'] ? $query->where(function ($q) use ($data) {
                            $q->whereMonth('start_date', $data['value'])
                                ->orWhereMonth('end_date', $data['value']);
                        }) : $query
                    ),

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
                    ->modalHeading('Lihat Hari Libur'),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('start_date', 'desc')
            ->defaultPaginationPageOption(50);
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
            'index'  => Pages\ListHolidays::route('/'),
            'create' => Pages\CreateHoliday::route('/create'),
            'view'   => Pages\ViewHoliday::route('/{record}'),
            'edit'   => Pages\EditHoliday::route('/{record}/edit'),
        ];
    }
}
