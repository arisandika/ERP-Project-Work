<?php

namespace App\Filament\Resources\Marketing;

use App\Filament\Resources\Marketing\SliderResource\Pages;
use App\Filament\Resources\Marketing\SliderResource\RelationManagers;
use App\Models\Marketing\Slider;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class SliderResource extends Resource
{
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
                Forms\Components\Section::make('Konten Visual')
                    ->description('Upload gambar untuk tampilan desktop dan mobile.')
                    ->schema([
                        Forms\Components\FileUpload::make('image_desktop')
                            ->label('Banner Desktop')
                            ->required()
                            ->image()
                            ->directory('sliders/desktop')
                            ->imageEditor()
                            ->columnSpanFull()
                            ->helperText('Rekomendasi ukuran: 1920x600 px'),

                        Forms\Components\FileUpload::make('image_mobile')
                            ->label('Banner Mobile (Opsional)')
                            ->image()
                            ->directory('sliders/mobile')
                            ->imageEditor()
                            ->columnSpanFull()
                            ->helperText('Jika kosong, akan menggunakan gambar desktop. Rekomendasi: 800x800 px'),
                    ]),

                Forms\Components\Section::make('Informasi Teks')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Internal')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Promo Ramadhan 2026')
                            ->prefixIcon('heroicon-o-tag'),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul Utama (Heading)')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-h1'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi (Sub-heading)')
                            ->rows(3)
                            ->maxLength(500)
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('Call To Action (Tombol)')
                    ->schema([
                        Forms\Components\TextInput::make('cta_text')
                            ->label('Teks Tombol')
                            ->placeholder('Contoh: Beli Sekarang')
                            ->prefixIcon('heroicon-o-cursor-arrow-rays'),

                        Forms\Components\TextInput::make('cta_url')
                            ->label('Link Tujuan (URL)')
                            ->url()
                            ->placeholder('https://...')
                            ->prefixIcon('heroicon-o-link'),

                        Forms\Components\Toggle::make('open_in_new_tab')
                            ->label('Buka di Tab Baru')
                            ->default(false)
                            ->inline(false),
                    ])->columns(2),

                Forms\Components\Section::make('Pengaturan')
                    ->schema([
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Urutan Tampil')
                            ->numeric()
                            ->default(0)
                            ->prefixIcon('heroicon-o-bars-arrow-up'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Slider')
                            ->default(true)
                            ->inline(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_desktop')
                    ->label('Preview')
                    ->height(50)
                    ->extraImgAttributes(['class' => 'object-cover w-16 h-10 rounded-md']),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Internal')
                    ->searchable()
                    ->sortable()
                    ->description(fn(Slider $record) => $record->title ?? '-'),

                Tables\Columns\TextColumn::make('cta_text')
                    ->label('Tombol')
                    ->icon('heroicon-o-cursor-arrow-rays')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Urutan Tampil')
                    ->sortable()
                    ->alignCenter(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Aktif'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
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
            ->defaultSort('sort_order', 'desc');
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
            'view' => Pages\ViewSlider::route('/{record}'),
            'edit' => Pages\EditSlider::route('/{record}/edit'),
        ];
    }
}
