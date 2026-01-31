<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\CustomerResource\Pages;
use App\Filament\Resources\CRM\CustomerResource\RelationManagers;
use App\Models\CRM\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon; // Pastikan ini diimpor untuk filter tanggal

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'crm/customer';

    protected static ?string $pluralModelLabel = 'Pelanggan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_type')
                    ->label('Tipe Pelanggan')
                    ->options([
                        'individual' => 'Individu',
                        'company' => 'Perusahaan',
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('name', null)),

                Forms\Components\TextInput::make('name')
                    ->label(fn (Forms\Get $get) => $get('customer_type') === 'individual' ? 'Nama Lengkap' : 'Nama Perusahaan')
                    ->required()
                    ->maxLength(255)
                    ->prefixIcon('heroicon-o-user')
                    ->hidden(fn (Forms\Get $get) => is_null($get('customer_type'))), // Sembunyikan jika tipe belum dipilih

                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true) // Pastikan email unik, kecuali untuk record yang sedang diedit
                    ->prefixIcon('heroicon-o-envelope')
                    ->hidden(fn (Forms\Get $get) => is_null($get('customer_type'))),

                Forms\Components\TextInput::make('phone')
                    ->label('Telepon')
                    ->tel()
                    ->maxLength(20)
                    ->prefixIcon('heroicon-o-phone')
                    ->hidden(fn (Forms\Get $get) => is_null($get('customer_type'))),

                Forms\Components\Textarea::make('address')
                    ->label('Alamat')
                    ->rows(3)
                    ->maxLength(65535)
                    // ->prefixIcon('heroicon-o-home')
                    ->hidden(fn (Forms\Get $get) => is_null($get('customer_type'))),
            ])->columns(2); // Menjadikan layout form menjadi 2 kolom
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_customer')
                    ->label('ID')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable(),

                Tables\Columns\TextColumn::make('customer_type')
                    ->label('Tipe Pelanggan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'individual' => 'info',
                        'company' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucwords($state)) // Kapitalisasi huruf pertama
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),

                Tables\Columns\TextColumn::make('phone')
                    ->label('Telepon')
                    ->searchable(),

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
                Tables\Filters\SelectFilter::make('customer_type')
                    ->label('Tipe Pelanggan')
                    ->options([
                        'individual' => 'Individu',
                        'company' => 'Perusahaan',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Created From')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        Forms\Components\DatePicker::make('created_until')
                            ->label('Created Until')
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
                            $indicators[] = 'Dibuat dari ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Dibuat hingga ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),

                Tables\Filters\TrashedFilter::make()
                    ->label('Status Dihapus')
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
