<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\CustomerResource\Pages;
use App\Models\CRM\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'crm/customers';

    protected static ?string $pluralModelLabel = 'Customer';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECTION 1: INFORMASI DASAR
                Forms\Components\Section::make('Informasi Pelanggan')
                    ->description('Pilih tipe pelanggan untuk menampilkan form yang sesuai.')
                    ->schema([
                        Forms\Components\Select::make('customer_type')
                            ->label('Tipe Pelanggan')
                            ->options([
                                'individual' => 'Perorangan (B2C)',
                                'company' => 'Perusahaan (B2B)',
                            ])
                            ->required()
                            ->native(false)
                            ->live()
                            ->afterStateUpdated(fn(Forms\Set $set) => $set('name', null)),

                        Forms\Components\TextInput::make('name')
                            ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Nama Perusahaan (PT/CV)' : 'Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user')
                            ->visible(fn(Forms\Get $get) => filled($get('customer_type'))),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-envelope')
                            ->visible(fn(Forms\Get $get) => filled($get('customer_type'))),

                        Forms\Components\TextInput::make('phone')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-device-phone-mobile')
                            ->required()
                            ->helperText('Nomor ini wajib diisi untuk validasi QR Code.'),

                        Forms\Components\Select::make('status')
                            ->label('Status Customer')
                            ->options([
                                'active' => 'Active',
                                'inactive' => 'Inactive',
                                'lost' => 'Lost',
                            ])
                            ->default('active')
                            ->required()
                            ->native(false),

                        Forms\Components\Textarea::make('address')
                            ->label(fn(Forms\Get $get) => $get('customer_type') === 'company' ? 'Alamat Kantor' : 'Alamat Domisili')
                            ->rows(3)
                            ->maxLength(255)
                            ->visible(fn(Forms\Get $get) => filled($get('customer_type'))),
                    ])
                    ->columns(2),

                // SECTION 2: KHUSUS B2C (PERORANGAN)
                Forms\Components\Section::make('Detail Perorangan')
                    ->schema([
                        Forms\Components\TextInput::make('nik')
                            ->label('NIK (KTP)')
                            ->numeric()
                            ->minLength(16)
                            ->maxLength(16)
                            ->prefixIcon('heroicon-o-identification')
                            ->required(),

                        Forms\Components\TextInput::make('phone')
                            ->label('No. WhatsApp')
                            ->tel()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-device-phone-mobile')
                            ->required()
                            ->helperText('Nomor ini wajib diisi untuk validasi QR Code.'),
                    ])
                    ->columns(2)
                    ->visible(fn(Forms\Get $get) => $get('customer_type') === 'individual'),

                // SECTION 3: KHUSUS B2B (PERUSAHAAN)
                Forms\Components\Section::make('Detail Perusahaan & PIC')
                    ->description('Lengkapi data NPWP dan Penanggung Jawab (PIC).')
                    ->schema([
                        Forms\Components\TextInput::make('npwp')
                            ->label('NPWP Perusahaan')
                            ->prefixIcon('heroicon-o-document-text')
                            ->columnSpanFull(),

                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('pic_name')
                                    ->label('Nama PIC')
                                    ->required()
                                    ->prefixIcon('heroicon-o-user-circle'),

                                Forms\Components\TextInput::make('pic_position')
                                    ->label('Jabatan PIC')
                                    ->prefixIcon('heroicon-o-briefcase'),

                                Forms\Components\TextInput::make('pic_phone')
                                    ->label('No. WhatsApp PIC')
                                    ->tel()
                                    ->maxLength(20)
                                    ->prefixIcon('heroicon-o-device-phone-mobile')
                                    ->required()
                                    ->helperText('Nomor ini wajib diisi untuk validasi QR Code.'),
                            ]),
                    ])
                    ->visible(fn(Forms\Get $get) => $get('customer_type') === 'company'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(fn(Customer $record) => $record->customer_type === 'company' ? 'PIC: ' . $record->pic_name : null),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Kontak')
                    ->icon('heroicon-o-phone')
                    ->searchable(['phone', 'email'])
                    ->getStateUsing(fn(Customer $record) => $record->customer_type === 'individual' ? $record->phone : $record->pic_phone)
                    ->description(fn(Customer $record) => $record->email),

                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Tipe Customer')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'individual' => 'info',
                        'company' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'lost',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipe Customer')
                    ->options([
                        'individual' => 'Perorangan',
                        'company' => 'Perusahaan',
                    ]),

                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('created_until')->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['created_from'], fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['created_until'], fn($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'view' => Pages\ViewCustomer::route('/{record}'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
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
