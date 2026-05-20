<?php

namespace App\Filament\Resources\Marketing;

use App\Filament\Resources\Marketing\PromoCodeResource\Pages;
use App\Models\Marketing\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use App\Filament\Concerns\BelongsToModule;

class PromoCodeResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'marketing';
    protected static ?string $model = PromoCode::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Manajemen Marketing';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'marketing/promo-codes';

    protected static ?string $pluralModelLabel = 'Kode Promo';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Kode Promo')
                    ->description('Tentukan kode dan nilai potongannya.')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('code')
                                ->label('Kode Promo')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('Contoh: LEBARAN2026')
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)),

                            Forms\Components\Select::make('type')
                                ->label('Tipe Potongan')
                                ->options([
                                    'fixed' => 'Nominal (Rp)',
                                    'percentage' => 'Persentase (%)',
                                ])
                                ->default('fixed')
                                ->required()
                                ->live()
                                ->native(false),

                            Forms\Components\TextInput::make('value')
                                ->label('Nilai Potongan')
                                ->numeric()
                                ->required()
                                ->prefix(fn(Get $get) => $get('type') === 'percentage' ? '' : 'IDR')
                                ->suffix(fn(Get $get) => $get('type') === 'percentage' ? '%' : ''),
                        ]),
                    ]),

                Forms\Components\Section::make('Informasi Masa Berlaku')
                    ->description('Atur masa berlaku dan batas kuota pemakaian.')
                    ->schema([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->helperText('Jika dimatikan, kode tidak bisa digunakan meskipun tanggal masih berlaku.')
                                ->default(true)
                                ->inline(false)
                                ->columnSpanFull(),

                            Forms\Components\DatePicker::make('start_date')
                                ->label('Mulai Berlaku')
                                ->required()
                                ->default(now())
                                ->displayFormat('d M Y')
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar-days'),

                            Forms\Components\DatePicker::make('end_date')
                                ->label('Berakhir Pada')
                                ->afterOrEqual('start_date')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar-days'),

                            Forms\Components\TextInput::make('usage_limit')
                                ->label('Batas Kuota')
                                ->helperText('Kosongkan jika ingin tanpa batas (Unlimited).')
                                ->numeric()
                                ->minValue(1),

                            Forms\Components\TextInput::make('times_used')
                                ->label('Kuota Terpakai')
                                ->disabled()
                                ->dehydrated(false)
                                ->default(0),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Promo')
                    ->weight('semibold')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('value')
                    ->label('Nilai Potongan')
                    ->formatStateUsing(
                        fn($state, PromoCode $record) =>
                        $record->type === 'fixed'
                        ? 'IDR ' . number_format($state, 0, ',', '.')
                        : number_format($state, 0) . '%'
                    )
                    ->color(fn(PromoCode $record) => $record->type === 'fixed' ? 'success' : 'info')
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('usage_summary')
                    ->label('Kuota Terpakai')
                    ->state(
                        fn(PromoCode $record) =>
                        $record->times_used . ' / ' . ($record->usage_limit ?? '∞')
                    ),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Aktif'),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Periode')
                    ->date('d M Y')
                    ->description(fn(PromoCode $record) => $record->end_date ? 's/d ' . $record->end_date->format('d M Y') : 'Selamanya')
                    ->sortable(),

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
                    ->modalHeading('Lihat Kode Promo'),
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
                Section::make('Informasi Kode Promo')
                    ->description('Detail kode dan nilai potongannya.')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('code')
                            ->label('Kode Promo')
                            ->weight('semibold')
                            ->copyable()
                            ->placeholder('—'),

                        TextEntry::make('type')
                            ->label('Tipe Potongan')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'fixed' => 'success',
                                'percentage' => 'info',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn(string $state): string => match ($state) {
                                'fixed' => 'Nominal (Rp)',
                                'percentage' => 'Persentase (%)',
                                default => ucwords(str_replace('_', ' ', $state)),
                            })
                            ->placeholder('—'),

                        TextEntry::make('value')
                            ->label('Nilai Potongan')
                            ->weight('semibold')
                            ->color(fn($record) => $record->type === 'fixed' ? 'success' : 'info')
                            ->formatStateUsing(
                                fn($state, $record) => $record->type === 'fixed'
                                ? 'IDR ' . number_format($state, 0, ',', '.')
                                : number_format($state, 0) . '%'
                            )
                            ->placeholder('—'),
                    ]),

                Section::make('Informasi Masa Berlaku & Kuota Pemakaian')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('is_active')
                            ->label('Status Aktif')
                            ->badge()
                            ->color(fn($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(fn($state) => $state ? 'Aktif' : 'Tidak Aktif')
                            ->columnSpanFull(),

                        TextEntry::make('start_date')
                            ->label('Mulai Berlaku')
                            ->date('D, d M Y')
                            ->placeholder('—'),

                        TextEntry::make('end_date')
                            ->label('Berakhir Pada')
                            ->date('D, d M Y')
                            ->placeholder('Selamanya'),

                        TextEntry::make('usage_limit')
                            ->label('Batas Kuota')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->placeholder('Tanpa Batas (Unlimited)'),

                        TextEntry::make('times_used')
                            ->label('Kuota Terpakai')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.') . ' Kali')
                            ->placeholder('0 Kali'),
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
            'index' => Pages\ListPromoCodes::route('/'),
            'create' => Pages\CreatePromoCode::route('/create'),
            // 'view' => Pages\ViewPromoCodes::route('/{record}'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
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
