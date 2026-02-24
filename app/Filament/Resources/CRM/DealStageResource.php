<?php

namespace App\Filament\Resources\CRM;

use App\Filament\Resources\CRM\DealStageResource\Pages;
use App\Filament\Resources\CRM\DealStageResource\RelationManagers;
use App\Models\CRM\DealStage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class DealStageResource extends Resource
{
    protected static ?string $model = DealStage::class;

    protected static ?string $navigationIcon = 'heroicon-o-flag';

    protected static ?string $navigationGroup = 'Manajemen CRM';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'crm/deal-stages';

    protected static ?string $pluralModelLabel = 'Stage Deal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Konfigurasi Stage Deal')
                    ->description('Tentukan urutan dan probabilitas keberhasilan di setiap tahap.')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Nama Stage')
                                    ->placeholder('Contoh: Negosiasi Harga')
                                    ->required()
                                    ->maxLength(100)
                                    ->prefixIcon('heroicon-o-tag'),

                                Forms\Components\TextInput::make('probability')
                                    ->label('Probabilitas Closing (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->default(0)
                                    ->suffix('%')
                                    ->helperText('Perkiraan persentase keberhasilan di tahap ini')
                                    ->prefixIcon('heroicon-o-presentation-chart-line'),

                                Forms\Components\TextInput::make('order')
                                    ->label('Urutan Tampilan')
                                    ->numeric()
                                    ->default(1)
                                    ->required()
                                    ->helperText('Gunakan angka untuk mengurutkan stage (1, 2, 3...)')
                                    ->prefixIcon('heroicon-o-bars-arrow-down'),
                            ]),
                    ]),
            ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order')
                    ->label('Urutan')
                    ->sortable()
                    ->alignCenter()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Stage')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('probability')
                    ->label('Probabilitas')
                    ->suffix('%')
                    ->sortable()
                    ->badge()
                    ->color(fn(int $state): string => match (true) {
                        $state <= 30 => 'danger',
                        $state <= 70 => 'warning',
                        default => 'success',
                    }),

                Tables\Columns\TextColumn::make('deals_count')
                    ->label('Total Deal Aktif')
                    ->counts('deals')
                    ->badge()
                    ->color(fn(int $state): string => $state > 0 ? 'info' : 'gray')
                    ->sortable()
                    ->formatStateUsing(fn($state) => $state . ' Deal'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime('d M Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order', 'asc') // Agar urutan pipeline rapi dari tahap awal
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDealStages::route('/'),
            'create' => Pages\CreateDealStage::route('/create'),
            'view' => Pages\ViewDealStage::route('/{record}'),
            'edit' => Pages\EditDealStage::route('/{record}/edit'),
        ];
    }
}
