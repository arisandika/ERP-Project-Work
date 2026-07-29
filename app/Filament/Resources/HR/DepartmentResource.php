<?php
namespace App\Filament\Resources\HR;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\HR\DepartmentResource\Pages;
use App\Models\HR\Department;
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
use Illuminate\Support\Carbon;

class DepartmentResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'hr';

    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Manajemen HR';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'hr/departments';

    protected static ?string $pluralModelLabel = 'Departemen';

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Departemen')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Departemen')
                                    ->required()
                                    ->maxLength(25)
                                    ->prefixIcon('heroicon-o-document-text')
                                // Tambahkan Custom Rule di sini
                                    ->rules([
                                        fn(?Department $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            // Cek database, termasuk yang sudah di-soft delete
                                            $query = Department::withTrashed()->where('name', $value);

                                            // Abaikan record saat ini jika sedang mode Edit
                                            if ($record) {
                                                $query->where('id', '!=', $record->id);
                                            }

                                            $existing = $query->first();

                                            if ($existing) {
                                                if ($existing->trashed()) {
                                                    $fail('Departemen "' . $value . '" sudah ada namun di dalam Trash (Terhapus). Silakan lakukan Restore data tersebut di tab filter "Deleted Status".');
                                                } else {
                                                    $fail('Departemen "' . $value . '" sudah digunakan oleh departemen aktif.');
                                                }
                                            }
                                        },
                                    ]),

                                Forms\Components\TextInput::make('code')
                                    ->label('Kode Departemen')
                                    ->required()
                                    ->maxLength(3)
                                    ->prefixIcon('heroicon-o-viewfinder-circle')
                                    ->afterStateUpdated(fn($state, callable $set) => $set('code', strtoupper($state)))
                                    // Tambahkan hal yang sama untuk kode jika kode juga harus unik
                                    ->rules([
                                        fn(?Department $record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                                            $query = Department::withTrashed()->where('code', $value);

                                            if ($record) {
                                                $query->where('id', '!=', $record->id);
                                            }

                                            $existing = $query->first();

                                            if ($existing) {
                                                if ($existing->trashed()) {
                                                    $fail('Kode "' . $value . '" sudah ada namun di dalam Trash (Terhapus). Silakan lakukan Restore data tersebut.');
                                                } else {
                                                    $fail('Kode "' . $value . '" sudah digunakan.');
                                                }
                                            }
                                        },
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Departemen')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Departemen')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—'),

                Tables\Columns\BadgeColumn::make('employees_count')
                    ->label('Jumlah Karyawan')
                    ->counts('employees')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Karyawan')
                    ->placeholder('—'),

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
                Tables\Actions\ViewAction::make()
                    ->modalHeading('Lihat Departemen'),
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
                Section::make('Informasi Departemen')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nama Departemen')
                            ->placeholder('—'),

                        TextEntry::make('employees_count')
                            ->label('Jumlah Karyawan')
                            ->state(function (Department $department) {
                                return $department->employees()->count() . ' Karyawan';
                            })
                            ->placeholder('—'),
                    ]),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            // 'view' => Pages\ViewDepartment::route('/{record}'),
            'edit'   => Pages\EditDepartment::route('/{record}/edit'),
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
