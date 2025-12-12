<?php

namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesPersonResource\Pages;
use App\Models\Sales\SalesPerson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesPersonResource extends Resource
{
    protected static ?string $model = SalesPerson::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Manajemen Sales';
    protected static ?string $navigationLabel = 'Sales';
    protected static ?string $pluralModelLabel = 'Daftar Sales';
    protected static ?string $modelLabel = 'Sales';
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identitas Sales')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'internal' => 'Karyawan',
                                'external' => 'Non-karyawan',
                            ])
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state === 'internal') {
                                    $set('full_name', null);
                                    $set('email', null);
                                    $set('phone', null);
                                } else {
                                    $set('employee_id', null);
                                }
                            })
                            ->required(),

                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship('employee', 'id') 
                            ->getOptionLabelFromRecordUsing(function ($record) {
                                return $record->full_name ?: "Employee #{$record->id}";
                            })
                            ->searchable(['full_name', 'email', 'phone_number'])
                            ->visible(fn ($get) => $get('type') === 'internal')
                            ->required(fn ($get) => $get('type') === 'internal')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (!$state) return;
                                $emp = \App\Models\HR\Employee::find($state);
                                if ($emp) {
                                    $set('full_name', $emp->full_name ?? null);
                                    $set('email', $emp->email ?? null);
                                    $set('phone', $emp->phone_number ?? null);
                                }
                            }),


                        TextInput::make('full_name')
                            ->label('Nama')
                            ->required(fn ($get) => $get('type') === 'external')
                            ->disabled(fn ($get) => $get('type') === 'internal'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(fn ($get) => $get('type') === 'external')
                            ->disabled(fn ($get) => $get('type') === 'internal'),

                        TextInput::make('phone')
                            ->label('Telepon')
                            ->required(fn ($get) => $get('type') === 'external')
                            ->disabled(fn ($get) => $get('type') === 'internal'),
                    ]),
                ])
                ->columns(1),

            Section::make('Pengaturan Sales')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'active' => 'Aktif',
                            'inactive' => 'Nonaktif',
                        ])
                        ->default('active')
                        ->required(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nama')
                    ->getStateUsing(fn ($record) =>
                        $record->type === 'internal'
                            ? ($record->employee->full_name ?: "Employee #{$record->employee_id}")
                            : ($record->full_name ?: '-')
                    )
                    ->searchable()
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'success' => 'active',
                        'secondary' => 'inactive',
                    ])
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Tipe')
                    ->options([
                        'internal' => 'Karyawan',
                        'external' => 'Non-karyawan',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ]),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Tables\Actions\EditAction::make()->label('Ubah'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()->label('Hapus'),
                    Tables\Actions\ForceDeleteBulkAction::make()->label('Hapus Permanen'),
                    Tables\Actions\RestoreBulkAction::make()->label('Pulihkan'),
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
            'index'  => Pages\ListSalesPeople::route('/'),
            'create' => Pages\CreateSalesPerson::route('/create'),
            'view'   => Pages\ViewSalesPerson::route('/{record}'),
            'edit'   => Pages\EditSalesPerson::route('/{record}/edit'),
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
