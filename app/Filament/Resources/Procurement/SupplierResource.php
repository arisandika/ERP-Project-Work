<?php

namespace App\Filament\Resources\Procurement;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Procurement\SupplierResource\Pages;
use App\Models\Procurement\Supplier;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SupplierResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'procurement';
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
                // SECTION 1: IDENTITAS (HEADER)
                Forms\Components\Section::make('Informasi Utama')
                    ->description('Detail dasar identitas entitas supplier.')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama Perusahaan / Supplier')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Masukkan nama PT atau Individu')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make([
                            'default' => 1,
                            'md'      => 3,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('supplier_code')
                                    ->label('Kode Supplier')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active'      => 'Aktif',
                                        'inactive'    => 'Non-Aktif',
                                        'blacklisted' => 'Blacklist'
                                    ])
                                    ->default('active')
                                    ->native(false),

                                Forms\Components\Checkbox::make('is_company')
                                    ->label('Apakah Perusahaan?')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ]),

                // SECTION 2: KONTAK & ALAMAT
                Forms\Components\Section::make('Kontak & Alamat')
                    ->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'md'      => 2,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Nama PIC Supplier')
                                    ->placeholder('Masukan nama PIC atau Penanggungjawab'),

                                Forms\Components\TextInput::make('tax_id')
                                    ->label('NPWP / Tax ID'),

                                Forms\Components\TextInput::make('phone')
                                    ->label('No. WhatsApp')
                                    ->tel(),

                                Forms\Components\TextInput::make('email')
                                    ->label('Email Utama')
                                    ->email(),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->rows(3)
                                    ->columnSpanFull(),
                            ]),
                    ]),

                // SECTION 3: KEUANGAN
                Forms\Components\Section::make('Informasi Keuangan')
                    ->schema([
                        Forms\Components\Grid::make([
                            'default' => 1,
                            'md'      => 3,
                        ])
                            ->schema([
                                Forms\Components\TextInput::make('bank_name')
                                    ->label('Nama Bank'),

                                Forms\Components\TextInput::make('bank_account_number')
                                    ->label('No. Rekening'),

                                Forms\Components\TextInput::make('bank_account_name')
                                    ->label('Atas Nama Rekening'),

                                Forms\Components\Select::make('payment_term')
                                    ->label('Term of Payment')
                                    ->options([
                                        'cod'    => 'COD',
                                        'net_30' => '30 Days',
                                        'net_45' => '45 Days'
                                    ])
                                    ->native(false),

                                Forms\Components\Select::make('currency')
                                    ->label('Mata Uang')
                                    ->options([
                                        'IDR' => 'IDR',
                                        'USD' => 'USD'
                                    ])
                                    ->default('IDR')
                                    ->native(false),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_code')
                    ->label('Kode')
                    ->fontFamily('mono'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Supplier')
                    ->searchable(),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('PIC'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'      => 'success',
                        'inactive'    => 'warning',
                        'blacklisted' => 'danger',
                        default       => 'gray'
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit'   => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
