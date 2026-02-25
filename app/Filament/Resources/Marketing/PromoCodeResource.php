<?php

namespace App\Filament\Resources\Marketing;

use App\Filament\Resources\Marketing\PromoCodeResource\Pages;
use App\Models\Marketing\PromoCode;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;

class PromoCodeResource extends Resource
{
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
                Section::make('Informasi Dasar')
                    ->description('Tentukan kode dan nilai potongannya.')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('code')
                                ->label('Kode Promo')
                                ->required()
                                ->unique(ignoreRecord: true)
                                ->placeholder('Contoh: LEBARAN2026')
                                ->maxLength(255)
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->dehydrateStateUsing(fn(string $state): string => strtoupper($state)),


                            Select::make('type')
                                ->label('Tipe Potongan')
                                ->options([
                                    'fixed' => 'Nominal (Rp)',
                                    'percentage' => 'Persentase (%)',
                                ])
                                ->default('fixed')
                                ->required()
                                ->live(), // Live update untuk prefix value di bawah

                            TextInput::make('value')
                                ->label('Nilai Potongan')
                                ->numeric()
                                ->required()
                                ->prefix(fn(Get $get) => $get('type') === 'percentage' ? '' : 'IDR')
                                ->suffix(fn(Get $get) => $get('type') === 'percentage' ? '%' : ''),
                        ]),
                    ]),

                Section::make('Validasi & Batasan')
                    ->description('Atur masa berlaku dan kuota pemakaian.')
                    ->schema([
                        Grid::make(2)->schema([
                            Toggle::make('is_active')
                                ->label('Status Aktif')
                                ->helperText('Jika dimatikan, kode tidak bisa digunakan meskipun tanggal masih berlaku.')
                                ->default(true)
                                ->columnSpanFull(),

                            DatePicker::make('start_date')
                                ->label('Mulai Berlaku')
                                ->required()
                                ->default(now())
                                ->displayFormat('d M Y')
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar-days'),

                            DatePicker::make('end_date')
                                ->label('Berakhir Pada')
                                ->afterOrEqual('start_date')
                                ->required()
                                ->displayFormat('d M Y')
                                ->native(false)
                                ->prefixIcon('heroicon-o-calendar-days'),

                            TextInput::make('usage_limit')
                                ->label('Batas Kuota (Total)')
                                ->helperText('Kosongkan jika ingin tanpa batas (Unlimited).')
                                ->numeric()
                                ->minValue(1),

                            TextInput::make('times_used')
                                ->label('Sudah Digunakan')
                                ->disabled() // Hanya info, tidak bisa diedit manual
                                ->dehydrated(false) // Jangan kirim ke save process
                                ->default(0),
                        ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode Promo')
                    ->weight('bold')
                    ->copyable()
                    ->searchable()
                    ->sortable(),

                TextColumn::make('value')
                    ->label('Nilai')
                    ->formatStateUsing(
                        fn($state, PromoCode $record) =>
                        $record->type === 'fixed'
                        ? 'IDR ' . number_format($state, 0, ',', '.')
                        : number_format($state, 0) . '%'
                    )
                    ->color(fn(PromoCode $record) => $record->type === 'fixed' ? 'success' : 'info')
                    ->badge(),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('usage_summary')
                    ->label('Kuota Terpakai')
                    ->state(
                        fn(PromoCode $record) =>
                        $record->times_used . ' / ' . ($record->usage_limit ?? '∞')
                    ),

                TextColumn::make('start_date')
                    ->label('Periode')
                    ->date('d M Y')
                    ->description(fn(PromoCode $record) => $record->end_date ? 's/d ' . $record->end_date->format('d M Y') : 'Selamanya')
                    ->sortable(),
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
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewPromoCodes::route('/{record}'),
            'edit' => Pages\EditPromoCode::route('/{record}/edit'),
        ];
    }
}
