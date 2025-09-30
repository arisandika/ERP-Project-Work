<?php

namespace App\Filament\Resources\HR;

use App\Filament\Resources\HR\EmployeeResource\Pages;
use App\Filament\Resources\HR\EmployeeResource\RelationManagers;
use App\Models\HR\Employee;
use Filament\Forms;
use Filament\Forms\Form;
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

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'hr-management/employees';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Personal Information')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Select::make('roles')
                            ->label('Role')
                            ->required()
                            ->relationship('roles', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->required()
                            ->email()
                            ->dehydrated(fn($state, $record, string $operation) => $operation === 'create' || ($operation === 'edit' && $state !== optional($record->user)->email))
                            ->afterStateHydrated(function ($component, $record, string $operation) {
                                if ($operation === 'edit' && $record) {
                                    $component->state(optional($record->user)->email);
                                }
                            }),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->password()
                            ->revealable(),

                        Forms\Components\TextInput::make('phone_number')
                            ->label('Phone Number')
                            ->required()
                            ->unique(ignoreRecord: true),

                        Forms\Components\FileUpload::make('photo')
                            ->label('Profile Photo')
                            ->image()
                            ->directory('employees/photos')
                            ->required()
                            ->imageEditor(),

                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->maxLength(500),
                    ])->columns(2),

                Forms\Components\Section::make('Job Information')
                    ->schema([
                        Forms\Components\Select::make('position')
                            ->label('Position')
                            ->required()
                            ->options([
                                'staff' => 'Staff',
                                'junior' => 'Junior',
                                'senior' => 'Senior',
                                'internship' => 'Internship',
                                'lead' => 'Lead',
                                'ex-employee' => 'Ex-Employee',
                            ])
                            ->native(false),

                        Forms\Components\Select::make('contract_type')
                            ->label('Contract Type')
                            ->options([
                                'permanent' => 'Permanent',
                                'contract' => 'Contract',
                                'intern' => 'Intern',
                            ])
                            ->default('contract')
                            ->required(),

                        Forms\Components\Select::make('status')
                            ->label('Employment Status')
                            ->options([
                                'active' => 'Active',
                                'resigned' => 'Resigned',
                                'terminated' => 'Terminated',
                            ])
                            ->default('active')
                            ->required(),

                        Forms\Components\Select::make('department_id')
                            ->label('Department')
                            ->required()
                            ->relationship('department', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->createOptionForm([
                                Forms\Components\TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(25),

                                Forms\Components\TextInput::make('code')
                                    ->label('Code')
                                    ->required()
                                    ->maxLength(3)
                                    ->afterStateUpdated(fn($state, callable $set) => $set('code', strtoupper($state))),
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Face Recognition (Future)')
                    ->schema([
                        Forms\Components\Textarea::make('face_embeddings')
                            ->label('Face Embeddings')
                            ->disabled()
                            ->hidden(),

                        Forms\Components\TextInput::make('face_embedding_path')
                            ->label('Embedding File Path')
                            ->disabled()
                            ->hidden(),

                        Forms\Components\Textarea::make('face_landmarks')
                            ->label('Face Landmarks')
                            ->disabled()
                            ->hidden(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('photo')
                    ->label('Photo')
                    ->circular(),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('roles.name')
                    ->label('Role')
                    ->colors(['indigo'])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('department.name')
                    ->label('Department')
                    ->sortable(),

                Tables\Columns\TextColumn::make('phone_number')
                    ->label('Phone'),

                Tables\Columns\TextColumn::make('position')
                    ->label('Position')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\BadgeColumn::make('contract_type')
                    ->colors([
                        'success' => 'permanent',
                        'warning' => 'contract',
                        'info' => 'intern',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'terminated',
                        'secondary' => 'resigned',
                    ])
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Employment Status')
                    ->options([
                        'active' => 'Active',
                        'resigned' => 'Resigned',
                        'terminated' => 'Terminated',
                    ]),

                Tables\Filters\SelectFilter::make('roles')
                    ->label('Roles')
                    ->relationship('roles', 'name'),

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
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Personal Information')
                    ->columns(2)
                    ->schema([
                        ImageEntry::make('photo')
                            ->label('Photo')
                            ->circular(),
                        TextEntry::make('full_name')
                            ->label('Full Name'),
                        TextEntry::make('email')
                            ->label('Email')
                            ->state(fn(Employee $employee) => optional($employee->user)->email),
                        TextEntry::make('phone_number')
                            ->label('Phone Number'),
                        TextEntry::make('address')
                            ->label('Address'),
                        TextEntry::make('roles')
                            ->label('Roles')
                            ->state(fn(Employee $employee) => $employee->roles->pluck('name')->join(', '))
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                    ]),

                Section::make('Job Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('position')
                            ->label('Position')
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                        TextEntry::make('contract_type')
                            ->label('Contract Type')
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                        TextEntry::make('status')
                            ->label('Employment Status')
                            ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
                        TextEntry::make('department.name')
                            ->label('Department')
                    ]),

                Section::make('Face Recognition (Future)')
                    ->columns(1)
                    ->schema([
                        TextEntry::make('face_embeddings')
                            ->label('Face Embeddings')
                            ->visible(false),
                        TextEntry::make('face_embedding_path')
                            ->label('Embedding Path')
                            ->visible(false),
                        TextEntry::make('face_landmarks')
                            ->label('Face Landmarks')
                            ->visible(false),
                    ]),

                Section::make('Additional Information')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('d F Y H:i'),
                        TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('d F Y H:i'),
                        TextEntry::make('deleted_at')
                            ->label('Deleted At')
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
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'view' => Pages\ViewEmployee::route('/{record}'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
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
