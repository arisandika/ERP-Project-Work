<?php
namespace App\Filament\Resources\Inventory;

use App\Filament\Resources\Inventory\PackageResource\Pages;
use App\Models\Inventory\Package;
use App\Models\Inventory\Product;
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
use Illuminate\Support\Carbon;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationGroup = 'Manajemen Inventory';

    protected static ?int $navigationSort = 6;

    protected static ?string $slug = 'inventory/packages';

    protected static ?string $pluralModelLabel = 'Paket Layanan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Paket')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('package_name')
                                    ->label('Nama Paket')
                                    ->required()
                                    ->maxLength(100)
                                    ->prefixIcon('heroicon-o-archive-box'),

                                Forms\Components\Textarea::make('description')
                                    ->label('Deskripsi Paket')
                                    ->maxLength(255)
                                    ->rows(3),
                            ]),
                    ]),

                Forms\Components\Section::make('Item dalam Paket')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->label('Daftar Produk & Layanan')
                            ->relationship('items')
                            ->schema([
                                Forms\Components\Select::make('item_type')
                                    ->label('Tipe Item')
                                    ->options([
                                        'product' => 'Produk',
                                        'service' => 'Jasa',
                                    ])
                                    ->reactive()
                                    ->required(),

                                Forms\Components\Select::make('item_id')
                                    ->label('Nama Item')
                                    ->options(function (callable $get) {
                                        return match ($get('item_type')) {
                                            'product' => Product::pluck('product_name', 'id'),
                                            'service' => Service::pluck('service_name', 'id'),
                                            default => [],
                                        };
                                    })
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        $type = $get('item_type');
                                        if (!$state || !$type) {
                                            $set('price', 0);
                                            $set('subtotal', 0);
                                            return;
                                        }

                                        $price = match ($type) {
                                            'product' => Product::find($state)?->price,
                                            'service' => Service::find($state)?->price,
                                            default => 0,
                                        } ?? 0;

                                        $set('price', $price);

                                        $qty = (int) $get('quantity');
                                        $qty = $qty > 0 ? $qty : 1;
                                        $set('subtotal', $price * $qty);
                                    })
                                    ->afterStateHydrated(function ($state, callable $set, callable $get) {
                                        $type = $get('item_type');
                                        if (!$state || !$type) {
                                            $set('price', 0);
                                            $set('subtotal', 0);
                                            return;
                                        }

                                        $price = match ($type) {
                                            'product' => Product::find($state)?->price,
                                            'service' => Service::find($state)?->price,
                                            default => 0,
                                        } ?? 0;

                                        $set('price', $price);

                                        $qty = (int) $get('quantity');
                                        $qty = $qty > 0 ? $qty : 1;
                                        $set('subtotal', $price * $qty);
                                    })
                                    ->required()
                                    ->searchable(),

                                Forms\Components\TextInput::make('quantity')
                                    ->label('Kuantitas')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(0)
                                    ->reactive()
                                    ->afterStateUpdated(function (callable $set, callable $get) {
                                        $price = (float) $get('price');
                                        $qty = (int) $get('quantity');
                                        $qty = $qty > 0 ? $qty : 1;
                                        $set('subtotal', $price * $qty);
                                    }),

                                Forms\Components\TextInput::make('price')
                                    ->label('Harga Satuan')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),

                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->disabled()
                                    ->dehydrated(),
                            ])
                            ->columns(3)
                            ->createItemButtonLabel('Tambah Item')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                $total = collect($state)
                                    ->sum(fn($item) => ((float) ($item['price'] ?? 0)) * (max(1, (int) ($item['quantity'] ?? 1))));
                                $set('total_price', $total);
                            }),
                    ]),


                Forms\Components\Section::make('Total Paket')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Harga Paket')
                            ->numeric()
                            ->prefix('Rp')
                            ->disabled()
                            ->dehydrated(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Status Paket')
                            ->default(true)
                            ->helperText('Paket aktif dapat digunakan untuk transaksi penawaran')
                            ->onIcon('heroicon-s-check-circle')
                            ->offIcon('heroicon-s-x-circle'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('package_name')
                    ->label('Nama Paket')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->label('Total Harga')
                    ->money('IDR', true)
                    ->sortable(),

                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->onIcon('heroicon-s-check-circle')
                    ->offIcon('heroicon-s-x-circle')
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

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Dihapus Pada')
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
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
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
