<?php

namespace App\Filament\Resources\Marketing;

use App\Filament\Resources\Marketing\PopupBannerResource\Pages;
use App\Models\Marketing\PopupBanner;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PopupBannerResource extends Resource
{
    protected static ?string $model = PopupBanner::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Manajemen Marketing';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'marketing/web-contents';

    protected static ?string $pluralModelLabel = 'Popup & Banner';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::activeNow()->count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi Popup/Banner')
                    ->description('Atur jenis konten dan jadwal penayangan.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Promo Lebaran 2026')
                            ->prefixIcon('heroicon-o-tag'),

                        Forms\Components\Select::make('type')
                            ->label('Jenis Konten')
                            ->options([
                                'image' => 'Banner Gambar',
                                'text'  => 'Teks Pengumuman',
                            ])
                            ->default('image')
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-swatch')
                            ->live(), 

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_date')
                                    ->label('Mulai Tayang')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calendar'),

                                Forms\Components\DateTimePicker::make('end_date')
                                    ->label('Selesai Tayang')
                                    ->required()
                                    ->native(false)
                                    ->after('start_date')
                                    ->prefixIcon('heroicon-o-calendar'),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Popup')
                            ->default(true)
                            ->inline(false)
                            ->helperText('Jika dimatikan, popup tidak akan tampil meskipun tanggalnya sesuai.'),
                    ])->columns(2),

                Forms\Components\Section::make('Konten Visual')
                    ->schema([
                        Forms\Components\FileUpload::make('image_path')
                            ->label('Upload Banner')
                            ->image()
                            ->directory('marketing/popups')
                            ->imageEditor()
                            ->required(fn (Get $get) => $get('type') === 'image')
                            ->visible(fn (Get $get) => $get('type') === 'image')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('content_text')
                            ->label('Isi Pengumuman')
                            ->toolbarButtons([
                                'bold', 'italic', 'underline', 'bulletList', 'orderedList', 'link', 'h2', 'h3'
                            ])
                            ->required(fn (Get $get) => $get('type') === 'text')
                            ->visible(fn (Get $get) => $get('type') === 'text')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Aksi (Call to Action)')
                    ->description('Opsional: Arahkan user ke halaman tertentu saat popup diklik.')
                    ->collapsed()
                    ->schema([
                        Forms\Components\TextInput::make('cta_label')
                            ->label('Label Tombol')
                            ->placeholder('Contoh: Lihat Promo')
                            ->prefixIcon('heroicon-o-cursor-arrow-rays'),

                        Forms\Components\TextInput::make('cta_url')
                            ->label('URL Tujuan')
                            ->url()
                            ->placeholder('https://website.com/promo')
                            ->prefixIcon('heroicon-o-link'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_path')
                    ->label('Banner')
                    ->visible(fn ($record) => $record && $record->type === 'image')
                    ->defaultImageUrl(url('/assets/placeholder.jpg')),

                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PopupBanner $record) => $record->type === 'text' ? 'Tipe: Teks' : 'Tipe: Gambar'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->colors([
                        'primary' => 'text',
                        'warning' => 'image',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('start_date')
                    ->label('Jadwal Tayang')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->description(fn (PopupBanner $record) => 's/d ' . $record->end_date->format('d M Y H:i')),

                // Status Aktif dengan Toggle Switch
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Aktif')
                    ->onColor('success')
                    ->offColor('danger'),
                
                // Status Tayang (Logika Kalkulasi Waktu)
                Tables\Columns\TextColumn::make('status_tayang')
                    ->label('Status Live')
                    ->badge()
                    ->state(function (PopupBanner $record) {
                        if (!$record->is_active) return 'Nonaktif';
                        $now = now();
                        if ($now < $record->start_date) return 'Terjadwal';
                        if ($now > $record->end_date) return 'Selesai';
                        return 'Tayang';
                    })
                    ->colors([
                        'gray'    => 'Nonaktif',
                        'warning' => 'Terjadwal',
                        'success' => 'Tayang',
                        'danger'  => 'Selesai',
                    ]),

                Tables\Columns\TextColumn::make('view_count')
                    ->label('Dilihat')
                    ->icon('heroicon-o-eye')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe Konten')
                    ->options([
                        'image' => 'Gambar',
                        'text'  => 'Teks',
                    ]),

                Tables\Filters\Filter::make('active_now')
                    ->label('Sedang Tayang')
                    ->query(fn (Builder $query) => $query->activeNow())
                    ->toggle(),
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
            'index' => Pages\ListPopupBanners::route('/'),
            'create' => Pages\CreatePopupBanner::route('/create'),
            'view' => Pages\ViewPopupBanner::route('/{record}'),
            'edit' => Pages\EditPopupBanner::route('/{record}/edit'),
        ];
    }
}
