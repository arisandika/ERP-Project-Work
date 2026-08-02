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
                Forms\Components\Group::make()
                    ->schema([
                        // SECTION 1: IDENTITAS
                        Forms\Components\Section::make('Informasi Utama')
                            ->description('Detail dasar identitas entitas supplier.')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Perusahaan / Supplier / Toko')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: PT. Maju Jaya / Toko Makmur Shopee')
                                    ->columnSpanFull(),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('supplier_code')
                                            ->label('Kode Supplier')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->disabled()
                                            ->dehydrated()
                                            ->default(function () {
                                                $prefix = 'SUP-' . date('Y') . '-';
                                                $lastSupplier = Supplier::where('supplier_code', 'like', $prefix . '%')
                                                    ->latest('id')
                                                    ->first();

                                                $number = $lastSupplier ? ((int) substr($lastSupplier->supplier_code, -3)) + 1 : 1;
                                                return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
                                            }),

                                        Forms\Components\Select::make('category')
                                            ->label('Kategori Entitas')
                                            ->options([
                                                'company' => 'Perusahaan (PT/CV)',
                                                'individual' => 'Individu / Perorangan',
                                                'marketplace' => 'Marketplace (Shopee, dll)',
                                            ])
                                            ->required()
                                            ->default('company')
                                            ->live()
                                            ->native(false),

                                        Forms\Components\Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'active'      => 'Aktif',
                                                'inactive'    => 'Non-Aktif',
                                                'blacklisted' => 'Blacklist'
                                            ])
                                            ->default('active')
                                            ->native(false),
                                    ]),

                                Forms\Components\Textarea::make('address')
                                    ->label('Alamat Lengkap')
                                    ->rows(3)
                                    ->columnSpanFull(),

                                // Kontak utama selalu tampil (untuk individu maupun perusahaan)
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('phone')
                                            ->label('No. Telepon / WhatsApp')
                                            ->tel()
                                            ->regex('/^([0-9\s\-\+\(\)]*)$/')
                                            ->required(),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                    ]),
                            ]),

                        // SECTION 2: KEUANGAN
                        Forms\Components\Section::make('Informasi Keuangan & Pembayaran')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('tax_id')
                                            ->label('NPWP / NIK (Jika Individu)'),

                                        Forms\Components\Select::make('payment_term')
                                            ->label('Term of Payment')
                                            ->options([
                                                'cod'    => 'Cash On Delivery (COD)',
                                                'net_7'  => 'Net 7 Days',
                                                'net_30' => 'Net 30 Days',
                                                'net_45' => 'Net 45 Days'
                                            ])
                                            ->native(false),
                                    ]),

                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('bank_name')
                                            ->label('Nama Bank')
                                            ->placeholder('Contoh: BCA / Mandiri'),

                                        Forms\Components\TextInput::make('bank_account_number')
                                            ->label('No. Rekening')
                                            ->numeric(),

                                        Forms\Components\TextInput::make('bank_account_name')
                                            ->label('Atas Nama Rekening'),
                                    ]),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                // SIDEBAR: PIC (hanya untuk perusahaan/marketplace)
                Forms\Components\Group::make()
                    ->schema([
                        // PIC selalu tampil (untuk individu juga, sebagai nama kontak utama)
                        Forms\Components\Section::make('PIC (Penanggung Jawab)')
                            ->schema([
                                Forms\Components\TextInput::make('contact_person')
                                    ->label('Nama PIC')
                                    ->required(),

                                Forms\Components\TextInput::make('pic_position')
                                    ->label('Jabatan PIC')
                                    ->placeholder('Contoh: Sales Manager / Owner'),
                            ]),

                        // REPEATER UNTUK KONTAK TAMBAHAN
                        Forms\Components\Section::make('Kontak Tambahan')
                            ->description('Tambah kontak finance, gudang, logistik, dll. jika diperlukan.')
                            ->collapsed()
                            ->schema([
                                Forms\Components\Repeater::make('contacts')
                                    ->relationship()
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama'),

                                        Forms\Components\Select::make('type')
                                            ->label('Tipe Kontak')
                                            ->options([
                                                'finance' => 'Finance / Invoice',
                                                'delivery' => 'Pengiriman / Logistik',
                                                'other' => 'Lainnya'
                                            ])
                                            ->default('other')
                                            ->native(false),

                                        Forms\Components\TextInput::make('phone')
                                            ->label('Telepon')
                                            ->tel(),

                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email(),
                                    ])
                                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                                    ->addActionLabel('Tambah Kontak')
                                    ->columns(1),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier_code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Entitas')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'company' => 'info',
                        'individual' => 'success',
                        'marketplace' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                Tables\Columns\TextColumn::make('contact_person')
                    ->label('PIC Utama')
                    ->description(fn (Supplier $record): string => match ($record->category) {
                        'individual' => $record->phone ?? '-',
                        default => $record->pic_position ?? 'Tidak ada jabatan',
                    })
                    ->searchable()
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active'      => 'success',
                        'inactive'    => 'warning',
                        'blacklisted' => 'danger',
                        default       => 'gray'
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori Supplier')
                    ->options([
                        'company' => 'Perusahaan',
                        'individual' => 'Individu',
                        'marketplace' => 'Marketplace',
                    ]),
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
