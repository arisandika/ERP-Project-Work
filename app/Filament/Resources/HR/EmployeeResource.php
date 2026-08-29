<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\EmployeeResource\Pages;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers\AttendancesRelationManager;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers\LeaveRequestsRelationManager;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers\ReimbursementRequestsRelationManager;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers\SickRequestsRelationManager;
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
    use BelongsToModule;
    protected static ?string $module = 'hr';
    protected static ?string $model  = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'hr/employees';

    protected static ?string $pluralModelLabel = 'Karyawan';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['full_name', 'email', 'phone_number'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Pribadi')
                    ->schema([
                        Forms\Components\TextInput::make('identity_number')
                            ->label('NIK (KTP)')
                            ->required()
                            ->numeric()
                            ->maxLength(16)
                            ->helperText('Max 16 karakter')
                            ->prefixIcon('heroicon-o-identification'),

                        Forms\Components\TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->multiple()
                            ->options(fn() => \Spatie\Permission\Models\Role::pluck('name', 'name')->toArray())
                            ->preload()
                            ->searchable()
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-shield-check')
                            ->afterStateHydrated(function (Forms\Components\Select $component, $record) {
                                if ($record && $record->user) {
                                    $component->state($record->user->roles->pluck('name')->toArray());
                                }
                            }),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            // Update: Pastikan email tidak digunakan oleh Employee lain.
                            // Jika ada yang mendaftarkan email ganda, Filament akan menampilkan pesan error UI dengan rapi.
                            ->unique(table: 'nx_employees', column: 'email', ignoreRecord: true)
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
                            ->helperText('Jika email sudah terdaftar di sistem, kata sandi ini akan diabaikan.')
                            ->required(fn(string $operation): bool => $operation === 'create'),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('No. WhatsApp')
                            ->required()
                            ->numeric()
                            ->unique(ignoreRecord: true)
                            ->maxLength(14)
                            ->helperText('Max 14 karakter')
                            ->prefixIcon('heroicon-o-phone'),

                        Forms\Components\TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->maxLength(50)
                            ->prefixIcon('heroicon-o-map-pin'),

                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
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
                                    ->label('Kode Departemen')
                                    ->required()
                                    ->maxLength(3)
                                    ->afterStateUpdated(fn($state, callable $set) =>
                                        $set('code', strtoupper($state))),
                            ]),

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
                            ->prefixIcon('heroicon-o-document-text'),

                        Forms\Components\Select::make('office_id')
                            ->label('Kantor Cabang')
                            ->required()
                            ->relationship('office', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-building-office'),

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

                        Forms\Components\DatePicker::make('join_date')
                            ->label('Tanggal Masuk')
                            ->default(now())
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\Select::make('status')
                            ->label('Status Karyawan')
                            ->options([
                                'active'     => 'Active',
                                'resigned'   => 'Resigned',
                                'terminated' => 'Terminated',
                            ])
                            ->native(false)
                            ->prefixIcon('heroicon-o-check-circle'),

                        Forms\Components\Toggle::make('can_wfa')
                            ->label('Izin WFA')
                            ->default(false)
                            ->inline(false),

                        Forms\Components\Toggle::make('can_unlock_shift')
                            ->label('Jam Kerja Flexible')
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
                    ->defaultImageUrl(url('/assets/placeholder.jpg'))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-user')
                    ->color(function (Employee $record) {
                        $record->withTrashed()->first();
                        if ($record && $record->trashed()) {
                            return 'danger';
                        }

                        return '';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Kontak')
                    ->description(fn(Employee $record) => $record->email)
                    ->searchable(['phone', 'user.email'])
                    ->sortable()
                    ->icon('heroicon-o-phone')
                    ->searchable(['phone_number', 'user.email'])
                    ->color(function (Employee $record) {
                        $record->withTrashed()->first();
                        return ($record && $record->trashed()) ? 'danger' : 'success';
                    })
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Departemen')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Jabatan')
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('office.name')
                    ->label('Kantor Cabang')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('shift.name')
                    ->label('Jam Kerja')
                    ->placeholder('—')
                    ->formatStateUsing(function ($record) {
                        if (! $record->shift) {
                            return '—';
                        }

                        return $record->shift->name . ' (' .
                        $record->shift->start_time . ' - ' .
                        $record->shift->end_time . ')';
                    })
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\BadgeColumn::make('status')
                    ->sortable()
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'resigned',
                        'danger'  => 'terminated',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('user.roles.name') // Tambahkan prefix 'user.'
                    ->label('Role')
                    ->badge() // Gunakan ->badge() sebagai pengganti BadgeColumn
                    ->color('indigo')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->placeholder('—')
                    ->searchable(), // Bisa ditambah searchable agar bisa dicari di table

                Tables\Columns\ToggleColumn::make('can_wfa')
                    ->label(new HtmlString(Blade::render('<x-heroicon-o-map-pin class="w-6 h-6" />')))
                    ->placeholder('—')
                    ->tooltip('Izin WFA'),

                Tables\Columns\ToggleColumn::make('can_unlock_shift')
                    ->label(new HtmlString(Blade::render('<x-heroicon-o-clock class="w-6 h-6" />')))
                    ->placeholder('—')
                    ->tooltip('Jam Kerja Flexible'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
                    ->dateTime('d M Y H:i')
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
                        'active'     => 'active',
                        'resigned'   => 'resigned',
                        'terminated' => 'terminated',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name')
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
                            ->defaultImageUrl(url('/assets/placeholder.jpg'))
                            ->placeholder('—')
                            ->columnSpanFull(),

                        TextEntry::make('full_name')
                            ->label('Nama Lengkap')
                            ->weight('semibold')
                            ->icon('heroicon-o-user')
                            ->placeholder('—'),

                        TextEntry::make('email')
                            ->label('Email')
                            ->url(fn($record) => $record->email ? "mailto:{$record->email}" : null)
                            ->placeholder('—')
                            ->color('warning'),

                        TextEntry::make('phone_number')
                            ->label('No. Whatsapp')
                            ->placeholder('—')
                            ->color('success'),

                        TextEntry::make('address')
                            ->label('Alamat')
                            ->placeholder('—'),

                        TextEntry::make('roles')
                            ->label('Peran (Role)')
                            ->state(fn(Employee $employee) => $employee->roles->pluck('name')->join(', '))
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                            ->placeholder('—'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Informasi Pekerjaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('status')
                            ->label('Status Karyawan')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'active'     => 'success',
                                'resigned'   => 'danger',
                                'terminated' => 'danger',
                                default      => 'danger',
                            })
                            ->formatStateUsing(fn(string $state) => match ($state) {
                                'active'     => 'Active',
                                'resigned'   => 'Resigned',
                                'terminated' => 'Terminated',

                                default      => ucwords(
                                    str_replace('_', ' ', $state)
                                ),
                            })
                            ->placeholder('—'),

                        TextEntry::make('department.name')
                            ->label('Departemen')
                            ->placeholder('—'),

                        TextEntry::make('position')
                            ->label('Jabatan')
                            ->formatStateUsing(fn(?string $state): string => ucwords(str_replace('_', ' ', $state ?? '-')))
                            ->placeholder('—'),

                        TextEntry::make('contract_type')
                            ->label('Jenis Kontrak')
                            ->formatStateUsing(fn(?string $state): string => match ($state) {
                                'Karyawan Tetap', 'permanent' => 'Karyawan Tetap',
                                'Kontrak', 'contract'         => 'Kontrak',
                                'Magang', 'intern'            => 'Magang',
                                default => ucwords($state ?? '-'),
                            })
                            ->placeholder('—'),

                        TextEntry::make('office.name')
                            ->label('Kantor Cabang')
                            ->placeholder('—'),

                        TextEntry::make('shift.name')
                            ->label('Jam Kerja')
                            ->placeholder('—')
                            ->formatStateUsing(function ($record) {
                                if (! $record->shift) {
                                    return '—';
                                }

                                return $record->shift->name . ' (' .
                                $record->shift->start_time . ' - ' .
                                $record->shift->end_time . ')';
                            }),

                        TextEntry::make('join_date')
                            ->label('Tanggal Masuk')
                            ->date('d M Y')
                            ->placeholder('—'),

                        IconEntry::make('can_wfa')
                            ->label('Izin WFA')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->placeholder('—'),

                        IconEntry::make('can_unlock_shift')
                            ->label('Jam Kerja Flexible')
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->placeholder('—'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Informasi Tambahan Pribadi')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('identity_number')
                            ->label('NIK (KTP)')
                            ->placeholder('—'),

                        TextEntry::make('birth_place')
                            ->label('Tempat Lahir')
                            ->placeholder('—'),

                        TextEntry::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->date('d M Y')
                            ->placeholder('—'),

                        TextEntry::make('gender')
                            ->label('Jenis Kelamin')
                            ->placeholder('—'),

                        TextEntry::make('marital_status')
                            ->label('Status Perkawinan')
                            ->placeholder('—'),

                        TextEntry::make('education_level')
                            ->label('Pendidikan Terakhir')
                            ->placeholder('—'),
                    ])
                    ->collapsible()
                    ->persistCollapsed(),

                Section::make('Pengelolaan Data')
                    ->columns(2)
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
            AttendancesRelationManager::class,
            LeaveRequestsRelationManager::class,
            SickRequestsRelationManager::class,
            ReimbursementRequestsRelationManager::class,
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
        $query = parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);

        // Cek apakah user yang login saat ini BUKAN super_admin
        if (auth()->check() && !auth()->user()->hasRole('super_admin')) {
            // Sembunyikan karyawan dengan email admin@nexicon.id
            $query->where('email', '!=', 'admin@nexicon.id');
            
            // Opsional: Jika email juga disimpan di tabel users dan direlasikan
            $query->whereHas('user', function ($q) {
                $q->where('email', '!=', 'admin@nexicon.id');
            });
        }

        return $query;
    }
}
