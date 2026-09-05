<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Resources\AfterSales\InternalRepairResource\Pages;
use App\Models\AfterSales\ReturnRequest;
use App\Models\Inventory\SerialNumber;
use App\Services\AfterSales\ReturnWorkflowService;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Concerns\BelongsToModule;

class InternalRepairResource extends Resource
{
    use BelongsToModule;
    protected static ?string $module = 'inventory';
    protected static ?string $model = ReturnRequest::class;
    protected static ?string $modelLabel = 'Servis Internal';
    protected static ?string $pluralModelLabel = 'Daftar Servis Internal';
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Servis Internal';
    protected static ?string $slug = 'after-sales/internal-repairs';
    protected static ?int $navigationSort = 22;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('warranty_type', 'store')
            ->where('status', ReturnRequest::STATUS_RECEIVED);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma_number')
                    ->label('No. RMA')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('issue_description')
                    ->label('Keluhan')
                    ->limit(50),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color('danger')
                    ->formatStateUsing(fn () => 'Menunggu Servis'),
            ])
            ->actions([
                // Aksi Khusus Teknisi Internal
                Tables\Actions\Action::make('process_internal')
                    ->label('Proses Perbaikan')
                    ->icon('heroicon-o-cog-8-tooth')
                    ->color('primary')
                    ->form([
                        Forms\Components\Radio::make('resolution_type')
                            ->label('Hasil Perbaikan')
                            ->options([
                                'repaired' => 'Berhasil Diperbaiki (Unit Lama Tetap)',
                                'replaced' => 'Ganti Unit Baru (Ambil dari Gudang)'
                            ])
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('new_serial_number_id')
                            ->label('Pilih SN Unit Pengganti')
                            ->options(function (ReturnRequest $record) {
                                $productId = $record->serialNumber?->product_id ?? $record->product_id;
                                if (!$productId) {
                                    return [];
                                }
                                return SerialNumber::where('product_id', $productId)
                                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                                    ->pluck('serial_number', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('resolution_type') === 'replaced')
                            ->required(fn (Forms\Get $get) => $get('resolution_type') === 'replaced'),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Catatan Teknisi')
                            ->required(),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        // Memanggil Service Pattern agar logika database tidak menumpuk di UI
                        $service = app(ReturnWorkflowService::class);
                        $service->processInternalRepair($record, $data);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Proses Unit Servis Internal'),
            ])
            ->emptyStateHeading('Tidak ada antrean servis internal');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'inventory_employees']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternalRepairs::route('/'),
        ];
    }
}
