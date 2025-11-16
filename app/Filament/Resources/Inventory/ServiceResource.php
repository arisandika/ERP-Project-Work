<?php

namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\ServiceResource\Pages;
use App\Filament\Resources\Inventory\ServiceResource\RelationManagers;
use App\Models\Inventory\Category;
use App\Models\Inventory\Service;
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

class ServiceResource extends Resource
{
    protected static ?string $model = Service::class;

    protected static ?string $navigationIcon = 'heroicon-o-percent-badge';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'inventory/services';

    protected static ?string $pluralModelLabel = 'Jasa';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Layanan')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('service_name')
                                    ->label('Nama Layanan')
                                    ->required()
                                    ->maxLength(100)
                                    ->prefixIcon('heroicon-o-percent-badge'),

                                Forms\Components\TextInput::make('price')
                                    ->label('Harga')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->required(),
                            ]),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->options(Category::pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->prefixIcon('heroicon-o-tag'),
                    ]),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('service_name')
                    ->label('Nama Layanan')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from')
                            ->label('Dari Tanggal')
                            ->displayFormat('d/m/Y')
                            ->native(false),
                        Forms\Components\DatePicker::make('created_until')
                            ->label('Sampai Tanggal')
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
                            $indicators[] = 'Dari ' . Carbon::parse($data['created_from'])->toFormattedDateString();
                        }

                        if ($data['created_until'] ?? null) {
                            $indicators[] = 'Sampai ' . Carbon::parse($data['created_until'])->toFormattedDateString();
                        }

                        return $indicators;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Detail Layanan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('service_name')->label('Nama Layanan'),
                        TextEntry::make('category.name')->label('Kategori'),
                        TextEntry::make('price')->label('Harga')->money('IDR', true),
                    ]),
                Section::make('Waktu Pengelolaan')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('created_at')->label('Dibuat Pada')->dateTime('d F Y H:i'),
                        TextEntry::make('updated_at')->label('Diperbarui Pada')->dateTime('d F Y H:i'),
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
            'index' => Pages\ListServices::route('/'),
            'create' => Pages\CreateService::route('/create'),
            'view' => Pages\ViewService::route('/{record}'),
            'edit' => Pages\EditService::route('/{record}/edit'),
        ];
    }
}
