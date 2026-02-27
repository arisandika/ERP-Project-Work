<?php
namespace App\Filament\Resources\Sales;

use App\Filament\Resources\Sales\SalesPersonResource\Pages;
use App\Models\HR\Employee;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesPerson;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

class SalesPersonResource extends Resource
{
    protected static ?string $model = SalesPerson::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Manajemen Sales';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'sales/pic-sales';

    protected static ?string $pluralModelLabel = 'PIC Sales';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        $fillEmployee = function ($state, callable $set) {
            if (!$state) {
                return;
            }

            $emp = Employee::find($state);

            if ($emp) {
                $set('full_name', $emp->full_name);
                $set('email', $emp->email);
                $set('phone', $emp->phone_number);
            }
        };

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
                                $set('employee_id', null);
                                $set('full_name', null);
                                $set('email', null);
                                $set('phone', null);
                            })
                            ->required(),

                        Select::make('employee_id')
                            ->label('Karyawan')
                            ->relationship('employee', 'id')
                            ->getOptionLabelFromRecordUsing(fn($record) => $record->full_name)
                            ->searchable(['full_name', 'email', 'phone_number'])
                            ->preload()
                            ->optionsLimit(10)
                            ->reactive()
                            ->visible(fn($get) => $get('type') === 'internal')
                            ->required(fn($get) => $get('type') === 'internal')
                            ->afterStateUpdated($fillEmployee)
                            ->afterStateHydrated($fillEmployee),

                        TextInput::make('full_name')
                            ->label('Nama')
                            ->required(fn($get) => $get('type') === 'external')
                            ->disabled(fn($get) => $get('type') === 'internal'),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required(fn($get) => $get('type') === 'external')
                            ->disabled(fn($get) => $get('type') === 'internal'),

                        TextInput::make('phone')
                            ->label('Telepon')
                            ->required(fn($get) => $get('type') === 'external')
                            ->disabled(fn($get) => $get('type') === 'internal'),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'active' => 'Aktif',
                                'inactive' => 'Nonaktif',
                            ])
                            ->default('active')
                            ->required(),
                    ]),
                ])
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Nama PIC')
                    ->getStateUsing(function ($record) {
                        if ($record->type === 'internal') {
                            return $record->employee?->full_name
                                ?? $record->full_name
                                ?? "Employee #{$record->employee_id}";
                        }

                        return $record->full_name ?: '-';
                    })
                    ->icon('heroicon-o-user')
                    ->weight('semibold')
                    ->color(fn($record) => $record->type === 'internal' ? 'primary' : 'success')
                    ->searchable(['full_name', 'employee.full_name'])
                    ->sortable(['full_name']),

                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe PIC')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'internal' => 'primary',
                        'external' => 'warning',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn(string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'danger',
                        default => 'gray'
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => $state,
                    })
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

                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Dibuat Dari')
                            ->required()
                            ->displayFormat('d M Y')
                            ->native(false)
                            ->prefixIcon('heroicon-o-calendar-days'),

                        DatePicker::make('created_until')
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
                // Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    // Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListSalesPeople::route('/'),
            'create' => Pages\CreateSalesPerson::route('/create'),
            'view' => Pages\ViewSalesPerson::route('/{record}'),
            'edit' => Pages\EditSalesPerson::route('/{record}/edit'),
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
