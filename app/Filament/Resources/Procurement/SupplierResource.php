<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Resources\Procurement\SupplierResource\Pages;
use App\Models\Procurement\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationGroup = 'Manajemen Procurement';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'procurement/suppliers';

    protected static ?string $pluralModelLabel = 'Data Supplier';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Perusahaan')
                    ->description('Detail entitas bisnis dari supplier/vendor.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan / Supplier')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-building-office-2'),

                        Forms\Components\TextInput::make('contact_person')
                            ->label('Nama Kontak (PIC)')
                            ->maxLength(255)
                            ->placeholder('Contoh: Bpk. Budi')
                            ->prefixIcon('heroicon-o-user'),
                    ])->columns(2),

                Forms\Components\Section::make('Informasi Kontak & Alamat')
                    ->description('Data untuk keperluan korespondensi dan penagihan.')
                    ->schema([
                        Forms\Components\TextInput::make('phone')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-phone'),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope'),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat Lengkap')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Supplier')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-building-office-2'),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('Nama PIC')
                    ->searchable()
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. WhatsApp')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('No. WhatsApp disalin')
                    ->icon('heroicon-m-phone')
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Email disalin')
                    ->icon('heroicon-m-envelope')
                    ->placeholder('–'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Didaftarkan Pada')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diupdate')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Anda bisa menambahkan filter jika nanti diperlukan
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [
            // Nantinya relasi ke tabel PO bisa dimasukkan ke sini
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
