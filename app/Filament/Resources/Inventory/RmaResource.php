<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\RmaResource\Pages;
use App\Models\Inventory\Rma;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class RmaResource extends Resource
{
    protected static ?string $model = Rma::class;

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';
    protected static ?string $navigationGroup = 'Manajemen Inventory';
    protected static ?string $pluralModelLabel = 'Data RMA';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make([
                Forms\Components\Wizard\Step::make('1. Terima Barang Rusak')
                    ->schema([
                        Forms\Components\Select::make('nx_serial_number_id')
                            ->label('SN Barang Rusak')
                            ->relationship('serialNumber', 'serial_number')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('client_notes')
                            ->label('Keluhan Klien')
                            ->rows(3),
                    ]),
                Forms\Components\Wizard\Step::make('2. Kirim ke Distributor')
                    ->schema([
                        Forms\Components\DatePicker::make('sent_to_distributor_at')
                            ->label('Tgl Kirim ke Distri')
                            ->native(false),
                    ]),
                Forms\Components\Wizard\Step::make('3. Terima Hasil Service')
                    ->schema([
                        Forms\Components\TextInput::make('new_serial_number')
                            ->label('SN Baru (Jika diganti)'),
                        Forms\Components\DatePicker::make('received_from_distributor_at')
                            ->label('Tgl Terima dari Distri')
                            ->native(false),
                    ]),
                Forms\Components\Wizard\Step::make('4. Kembalikan ke Klien')
                    ->schema([
                        Forms\Components\DatePicker::make('returned_to_client_at')
                            ->label('Tgl Kembali ke Klien')
                            ->native(false),
                    ]),
            ])
            ->columnSpanFull()
            // WAJIB: Submit Action agar Wizard menyimpan data ke database
            ->submitAction(new HtmlString(
                '<button type="submit" class="filament-button inline-flex items-center justify-center px-4 py-2 text-sm font-medium tracking-tight text-white transition rounded-lg shadow bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-2 focus:ring-2 focus:ring-inset focus:ring-white focus:outline-none filament-button-size-md">Simpan RMA</button>'
            ))
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serialNumber.serial_number')
                    ->label('Serial Number')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status RMA')
                    ->badge()
                    // Perbaikan: Huruf besar dan spasi agar lebih rapi
                    ->formatStateUsing(fn($state) => strtoupper(str_replace('_', ' ', $state)))
                    ->color(fn (string $state): string => match ($state) {
                        'received' => 'warning',
                        'sent_to_distributor' => 'info',
                        'received_from_distributor' => 'primary',
                        'returned_to_client' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Terima')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRmas::route('/'),
            'create' => Pages\CreateRma::route('/create'),
            'edit' => Pages\EditRma::route('/{record}/edit'),
        ];
    }
}
