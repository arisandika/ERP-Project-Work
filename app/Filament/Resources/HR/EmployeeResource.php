<?php
namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\EmployeeResource\Pages;
use App\Models\HR\Employee;
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
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'hr/employees';

    protected static ?string $pluralModelLabel = 'Karyawan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([

                        Forms\Components\TextInput::make('national_id')
                            ->label('NIK')
                            ->required()
                            ->numeric()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-identification'),

                        Forms\Components\TextInput::make('identity_number')
                            ->label('Nomor KTP')
                            ->required()
                            ->numeric()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-identification'),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->required()
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-shield-check'),

                        Forms\Components\TextInput::make('email')
                            ->label('Alamat Email')
                            ->required()
                            ->email()
                            ->prefixIcon('heroicon-o-envelope')
                            ->dehydrated(fn($state, $record, string $operation) =>
                                $operation === 'create' ||
                                ($operation === 'edit' && $state !== optional($record->user)->email))
                            ->afterStateHydrated(function ($component, $record, string $operation) {
                                if ($operation === 'edit' && $record) {
                                    $component->state(optional($record->user)->email);
                                }
                            }),

                        Forms\Components\TextInput::make('password')
                            ->label('Kata Sandi')
                            ->password()
                            ->revealable()
                            ->prefixIcon('heroicon-o-lock-closed')
                            ->required(fn(string $operation): bool => $operation === 'create'),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Nomor HP')
                            ->required()
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->prefixIcon('heroicon-o-phone'),

                        Forms\Components\TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->maxLength(100)
                            ->prefixIcon('heroicon-o-map-pin'),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'Laki-laki' => 'Laki-laki',
                                'Perempuan' => 'Perempuan',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-user-group'),

                        Forms\Components\Select::make('marital_status')
                            ->label('Status Perkawinan')
                            ->options([
                                'Menikah'       => 'Menikah',
                                'Belum Menikah' => 'Belum Menikah',
                                'Duda'          => 'Duda',
                                'Janda'         => 'Janda',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-heart'),

                        Forms\Components\Select::make('education_level')
                            ->label('Pendidikan Terakhir')
                            ->options([
                                'SD'       => 'SD',
                                'SMP'      => 'SMP',
                                'SMA'      => 'SMA',
                                'Diploma'  => 'Diploma (D1/D2/D3)',
                                'Sarjana'  => 'Sarjana (S1)',
                                'Magister' => 'Magister (S2)',
                                'Doktor'   => 'Doktor (S3)',
                                'Lainnya'  => 'Lainnya',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-academic-cap'),

                        Forms\Components\FileUpload::make('photo')
                            ->label('Foto Profil')
                            ->image()
                            ->directory('employees/photos')
                            ->imageEditor(),

                        Forms\Components\Textarea::make('address')
                            ->label('Alamat')
                            ->maxLength(500),

                    ])->columns(2),

                Forms\Components\Section::make('Informasi Pekerjaan')
                    ->schema([

                        Forms\Components\Select::make('office_id')
                            ->label('Kantor Cabang')
                            ->required()
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-building-office'),

                        Forms\Components\Select::make('department_id')
                            ->label('Departemen')
                            ->required()
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-briefcase')
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Departemen')
                                    ->required()
                                    ->maxLength(25),

                                Forms\Components\TextInput::make('code')
                                    ->label('Kode')
                                    ->required()
                                    ->maxLength(3)
                                    ->afterStateUpdated(fn($state, callable $set) =>
                                        $set('code', strtoupper($state))),
                            ]),

                        Forms\Components\Select::make('shift_id')
                            ->label('Jam Kerja')
                            ->required()
                            ->relationship('shift', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-clock')
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->name . ' (' . $record->start_time . ' - ' . $record->end_time . ')';
                            }),

                        Forms\Components\Select::make('position')
                            ->label('Jabatan')
                            ->required()
                            ->options([
                                'Staf'            => 'Staf',
                                'Junior'          => 'Junior',
                                'Senior'          => 'Senior',
                                'Magang'          => 'Magang',
                                'Pimpinan'        => 'Pimpinan',
                                'Mantan Karyawan' => 'Mantan Karyawan',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\Select::make('contract_type')
                            ->label('Jenis Kontrak')
                            ->options([
                                'Karyawan Tetap' => 'Karyawan Tetap',
                                'Kontrak'        => 'Kontrak',
                                'Magang'         => 'Magang',
                            ])
                            ->native(false)
                            ->default('Kontrak')
                            ->prefixIcon('heroicon-o-document-text'),

                        Forms\Components\DatePicker::make('join_date')
                            ->label('Tanggal Masuk')
                            ->prefixIcon('heroicon-o-calendar'),

                        Forms\Components\Select::make('status')
                            ->label('Status Karyawan')
                            ->options([
                                'Aktif'             => 'Aktif',
                                'Mengundurkan Diri' => 'Mengundurkan Diri',
                                'Diberhentikan'     => 'Diberhentikan',
                            ])
                            ->native(false)
                            ->default('Aktif')
                            ->prefixIcon('heroicon-o-check-circle'),

                        Forms\Components\Toggle::make('can_wfa')
                            ->label('Boleh Work From Anywhere (WFA)')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('can_unlock_shift')
                            ->label('Boleh Unlock Shift')
                            ->default(false)
                            ->inline(false),

                    ])->columns(2),
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Foto')
                    ->circular()
                    ->defaultImageUrl(url('/assets/placeholder.jpg')),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('No. HP'),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable(),

                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Kantor Cabang')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Aktif',
                        'gray'    => 'Mengundurkan Diri',
                        'danger'  => 'Diberhentikan',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->colors(['indigo'])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\ToggleColumn::make('can_wfa')
                    ->label(new HtmlString(Blade::render('<x-heroicon-o-map-pin class="w-6 h-6" />'))),

                Tables\Columns\ToggleColumn::make('can_unlock_shift')
                    ->label(new HtmlString(Blade::render('<x-heroicon-o-clock class="w-6 h-6" />'))),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Departemen')
                    ->relationship('department', 'name')
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Karyawan')
                    ->options([
                        'Aktif'             => 'Aktif',
                        'Mengundurkan Diri' => 'Mengundurkan Diri',
                        'Diberhentikan'     => 'Diberhentikan',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
                            ->displayFormat('d/m/Y')
                            ->native(false),
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

                Section::make('Informasi Pribadi')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('photo')
                            ->label('Foto Profil')
                            ->circular()
                            ->defaultImageUrl(url('/assets/placeholder.jpg')),

                        TextEntry::make('full_name')
                            ->label('Nama Lengkap'),

                        TextEntry::make('email')
                            ->label('Alamat Email')
                            ->state(fn(Employee $employee) => optional($employee->user)->email),

                        TextEntry::make('phone_number')
                            ->label('Nomor HP'),

                        TextEntry::make('address')
                            ->label('Alamat'),

                        TextEntry::make('roles')
                            ->label('Peran (Role)')
                            ->state(fn(Employee $employee) => $employee->roles->pluck('name')->join(', '))
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                    ]),

                Section::make('Informasi Pekerjaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('position')
                            ->label('Jabatan')
                            ->formatStateUsing(fn(?string $state): string => ucwords(str_replace('_', ' ', $state ?? '-'))),

                        TextEntry::make('contract_type')
                            ->label('Jenis Kontrak')
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'Karyawan Tetap', 'permanent' => 'Karyawan Tetap',
                                'Kontrak', 'contract'         => 'Kontrak',
                                'Magang', 'intern'            => 'Magang',
                                default => ucwords($state ?? '-'),
                            }),

                        TextEntry::make('status')
                            ->label('Status Karyawan')
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'Aktif', 'active'               => 'Aktif',
                                'Mengundurkan Diri', 'resigned' => 'Mengundurkan Diri',
                                'Diberhentikan', 'terminated'   => 'Diberhentikan',
                                default => ucwords($state ?? '-'),
                            }),

                        TextEntry::make('department.name')
                            ->label('Departemen'),

                        TextEntry::make('office.name')
                            ->label('Kantor Cabang'),

                        TextEntry::make('join_date')
                            ->label('Tanggal Masuk')
                            ->date('d F Y'),

                        IconEntry::make('can_wfa')
                            ->label('Boleh Work From Anywhere (WFA)')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle'),

                        IconEntry::make('can_unlock_shift')
                            ->label('Boleh Unlock Shift')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle'),
                    ]),

                Section::make('Informasi Tambahan Pribadi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('national_id')
                            ->label('NIK'),

                        TextEntry::make('identity_number')
                            ->label('Nomor KTP'),

                        TextEntry::make('birth_place')
                            ->label('Tempat Lahir'),

                        TextEntry::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d F Y'),

                        TextEntry::make('gender')
                            ->label('Jenis Kelamin'),

                        TextEntry::make('marital_status')
                            ->label('Status Perkawinan'),

                        TextEntry::make('education_level')
                            ->label('Pendidikan Terakhir'),
                    ]),

                Section::make('Pengelolaan Data')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Dibuat Pada')
                            ->dateTime('d F Y H:i'),

                        TextEntry::make('updated_at')
                            ->label('Diperbarui Pada')
                            ->dateTime('d F Y H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Dihapus Pada')
                            ->dateTime('d F Y H:i')
                            ->visible(fn(Employee $employee) => $employee->trashed()),
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
            'index'  => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view'   => Pages\ViewEmployee::route('/{record}'),
            'edit'   => Pages\EditEmployee::route('/{record}/edit'),
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
