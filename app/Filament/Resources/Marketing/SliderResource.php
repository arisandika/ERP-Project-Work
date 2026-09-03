<?php

namespace App\Filament\Resources\Marketing;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Marketing\SliderResource\Pages;
use App\Filament\Resources\Marketing\SliderResource\RelationManagers;
use App\Models\Marketing\Slider;
use Filament\Forms\Form;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class SliderResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'marketing';
    protected static ?string $model = Slider::class;
    protected static ?string $navigationIcon = 'heroicon-o-photo';
    protected static ?string $navigationGroup = 'Manajemen Marketing';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'marketing/sliders';
    protected static ?string $pluralModelLabel = 'Konten Slider';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Slider')
                    ->description('Informasi teks yang akan tampil pada banner.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul (Heading)')
                            ->maxLength(255)
                            ->placeholder('Contoh: Promo Spesial Ramadhan')
                            ->prefixIcon('heroicon-o-h1'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi (Sub Heading)')
                            ->rows(3)
                            ->maxLength(500)
                            ->placeholder('Tambahkan penjelasan singkat mengenai promo...')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Konten Visual')
                    ->description('Upload gambar banner untuk desktop dan mobile.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\FileUpload::make('image_desktop')
                            ->label('Banner Desktop')
                            ->required()
                            ->image()
                            ->directory('sliders/desktop')
                            ->imageEditor()
                            ->helperText('Rekomendasi ukuran: 1920x600 px'),
                        Forms\Components\FileUpload::make('image_mobile')
                            ->label('Banner Mobile')
                            ->image()
                            ->directory('sliders/mobile')
                            ->imageEditor()
                            ->helperText('Opsional. Jika kosong akan menggunakan gambar desktop'),
                    ]),
                Forms\Components\Section::make('Call To Action')
                    ->description('Arahkan pelanggan ke halaman tertentu.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\TextInput::make('cta_text')
                            ->label('Label Button')
                            ->placeholder('Contoh: Lihat Promo')
                            ->prefixIcon('heroicon-o-cursor-arrow-rays'),
                        Forms\Components\TextInput::make('cta_url')
                            ->label('URL Tujuan')
                            ->url()
                            ->placeholder('https://website.com/promo')
                            ->prefixIcon('heroicon-o-link'),
                        Forms\Components\Toggle::make('open_in_new_tab')
                            ->label('Buka Link di Tab Baru')
                            ->helperText('Aktifkan jika link mengarah ke website luar.')
                            ->default(false)
                            ->inline(false)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make('Pengaturan Tayang')
                    ->description('Atur jadwal dan urutan tampilan slider.')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        Forms\Components\DateTimePicker::make('start_date')
                            ->label('Mulai Tayang')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\DateTimePicker::make('end_date')
                            ->label('Selesai Tayang')
                            ->required()
                            ->after('start_date')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->required()
                            ->default(fn() => (Slider::max('sort_order') ?? 0) + 1)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, ?Model $record) {
                                if (blank($state)) {
                                    return;
                                }

                                $isTaken = function ($value) use ($record) {
                                    return Slider::query()
                                        ->where('sort_order', $value)
                                        ->when($record, fn($q) => $q->where('id', '!=', $record->id))
                                        ->exists();
                                };

                                if ($isTaken($state)) {
                                    $original = $state;

                                    while ($isTaken($state)) {
                                        $state++;
                                    }

                                    $set('sort_order', $state);

                                    Notification::make()
                                        ->title('Urutan Disesuaikan')
                                        ->body("Urutan {$original} sudah digunakan slider lain. Diganti menjadi {$state}.")
                                        ->info()
                                        ->duration(3000)
                                        ->send();
                                }
                            })
                            ->helperText('Jika nomor sudah dipakai, sistem akan menyesuaikan otomatis.'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Aktif')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Jika dimatikan slider tidak akan tampil.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_desktop')
                    ->label('Banner')
                    ->height(60)
                    ->extraImgAttributes([
                        'loading' => 'lazy',
                        'class' => 'object-cover rounded-md shadow-sm w-24',
                        'alt' => 'Gambar Hilang',
                    ])
                    ->placeholder('Tidak ada gambar'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul (Heading)')
                    ->weight('semibold')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->description(
                        fn($record) =>
                            $record->description
                                ? str($record->description)->limit(40)
                                : 'Tidak ada deskripsi'
                    ),
                Tables\Columns\TextColumn::make('cta_text')
                    ->label('Tombol CTA')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->placeholder('Tidak ada tombol')
                    ->description(
                        fn($record) =>
                            $record->cta_url ? str($record->cta_url)->limit(30) : null
                    ),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Aktif'),
                Tables\Columns\TextColumn::make('status_tayang')
                    ->label('Status Tayang')
                    ->badge()
                    ->state(function (Slider $record) {
                        if (!$record->is_active) {
                            return 'Nonaktif';
                        }

                        $now = now();

                        if ($now < $record->start_date) {
                            return 'Terjadwal';
                        }

                        if ($now > $record->end_date) {
                            return 'Selesai Tayang';
                        }

                        return 'Sedang Tayang';
                    })
                    ->color(fn($state) => match ($state) {
                        'Nonaktif' => 'gray',
                        'Terjadwal' => 'warning',
                        'Sedang Tayang' => 'success',
                        'Selesai Tayang' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('Periode Tayang')
                    ->dateTime('d M Y H:i')
                    ->description(
                        fn($record) =>
                            's/d ' . ($record->end_date?->format('d M Y H:i') ?? '—')
                    )
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Aktif')
                    ->options([
                        1 => 'Aktif',
                        0 => 'Nonaktif',
                    ])
                    ->native(false),
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
                    ->modalHeading('Lihat Slider'),
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
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Informasi Slider')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('title')
                            ->label('Judul (Heading)')
                            ->weight('semibold')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        TextEntry::make('description')
                            ->label('Deskripsi (Sub Heading)')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ]),
                Section::make('Konten Visual')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        ImageEntry::make('image_desktop')
                            ->label('Versi Desktop')
                            ->placeholder('Tidak ada gambar')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl'
                            ]),
                        ImageEntry::make('image_mobile')
                            ->label('Versi Mobile')
                            ->placeholder('Otomatis menyesuaikan dari versi Desktop')
                            ->extraImgAttributes([
                                'style' => 'width: 100%; height: auto; object-fit: cover;',
                                'class' => 'w-full rounded-2xl'
                            ]),
                    ]),
                Section::make('Call To Action')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('cta_text')
                            ->label('Label Button')
                            ->placeholder('—'),
                        TextEntry::make('cta_url')
                            ->label('URL Tujuan')
                            ->copyable()
                            ->url(fn($state) => $state)
                            ->openUrlInNewTab()
                            ->color('primary')
                            ->placeholder('—'),
                        TextEntry::make('open_in_new_tab')
                            ->label('Perilaku Klik')
                            ->formatStateUsing(
                                fn($state) =>
                                    $state ? 'Buka di Tab Baru' : 'Buka di Tab yang Sama'
                            )
                            ->columnSpanFull(),
                    ]),
                Section::make('Pengaturan Tayang')
                    ->columns(['default' => 1, 'md' => 3])
                    ->schema([
                        TextEntry::make('sort_order')
                            ->label('Urutan Tampil')
                            ->label('Urutan Tampil')
                            ->formatStateUsing(fn($state): string => 'Urutan ke-' . $state)
                            ->placeholder('—'),
                        TextEntry::make('is_active')
                            ->label('Status Aktif')
                            ->badge()
                            ->color(fn($state) => $state ? 'success' : 'danger')
                            ->formatStateUsing(
                                fn($state) =>
                                    $state ? 'Aktif' : 'Nonaktif'
                            ),
                        TextEntry::make('status_tayang')
                            ->label('Status Penayangan')
                            ->badge()
                            ->state(function ($record) {
                                if (!$record->is_active)
                                    return 'Nonaktif';

                                $now = now();

                                if ($now < $record->start_date)
                                    return 'Terjadwal';
                                if ($now > $record->end_date)
                                    return 'Selesai Tayang';

                                return 'Sedang Tayang';
                            })
                            ->color(fn($state) => match ($state) {
                                'Nonaktif' => 'gray',
                                'Terjadwal' => 'warning',
                                'Sedang Tayang' => 'success',
                                'Selesai Tayang' => 'danger',
                            }),
                        TextEntry::make('start_date')
                            ->label('Mulai Tayang')
                            ->dateTime('d M Y H:i'),
                        TextEntry::make('end_date')
                            ->label('Berakhir Pada')
                            ->dateTime('d M Y H:i'),
                    ]),
                Section::make('Pengelolaan Data')
                    ->columns(['default' => 1, 'md' => 2])
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
            'index' => Pages\ListSliders::route('/'),
            'create' => Pages\CreateSlider::route('/create'),
            // 'view' => Pages\ViewSlider::route('/{record}'),
            'edit' => Pages\EditSlider::route('/{record}/edit'),
        ];
    }
}
