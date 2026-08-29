<?php
namespace App\Filament\Resources\Sales;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\Sales\SalesPersonResource\Pages;
use App\Models\Sales\SalesPerson;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SalesPersonResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module           = 'sales';
    protected static ?string $model            = SalesPerson::class;
    protected static ?string $navigationIcon   = 'heroicon-o-user-group';
    protected static ?string $navigationGroup  = 'Manajemen Sales';
    protected static ?string $slug             = 'sales/sales-people';
    protected static ?string $pluralModelLabel = 'Sales Person';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Sales Person')
                    ->schema([
                        Forms\Components\TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-user'),

                        Forms\Components\Select::make('type')
                            ->label('Tipe Sales')
                            ->options([
                                'external' => 'External Sales',
                            ])
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-briefcase'),

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
                            ->unique(table: 'nx_sales_people', column: 'email', ignoreRecord: true)
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

                        Forms\Components\TextInput::make('phone')
                            ->label('No. Telepon')
                            ->tel()
                            ->maxLength(20)
                            ->prefixIcon('heroicon-o-phone'),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'active'   => 'Active',
                                'inactive' => 'Inactive',
                            ])
                            ->default('active')
                            ->native(false)
                            ->prefixIcon('heroicon-o-check-circle'),
                    ])->columns(['default' => 1, 'md' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe')
                    ->badge()
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        'external' => 'External',
                        default    => '-',
                    })
                    ->color(fn(?string $state) => $state === 'external' ? 'warning' : 'info'),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(['email', 'user.email']),

                Tables\Columns\TextColumn::make('phone')
                    ->label('No. Telepon')
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.roles.name')
                    ->label('Role')
                    ->badge()
                    ->color('indigo')
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state)))
                    ->placeholder('—')
                    ->searchable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->sortable()
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'inactive',
                    ]),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'external' => 'External',
                    ])
                    ->native(false),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active'   => 'Active',
                        'inactive' => 'Inactive',
                    ])
                    ->native(false),

                Tables\Filters\TrashedFilter::make()
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
                Section::make('Informasi Sales Person')
                    ->columns(['default' => 1, 'md' => 2])
                    ->schema([
                        TextEntry::make('full_name')->label('Nama Lengkap'),
                        TextEntry::make('type')->label('Tipe'),
                        TextEntry::make('email')->label('Email'),
                        TextEntry::make('phone')->label('No. Telepon'),
                        TextEntry::make('status')->label('Status')->badge(),
                        TextEntry::make('roles')
                            ->label('Role')
                            ->state(fn(SalesPerson $record) => $record->user?->roles->pluck('name')->join(', '))
                            ->placeholder('—'),
                    ]),
            ]);
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
