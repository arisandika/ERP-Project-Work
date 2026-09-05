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

    /**
     * Antrean servis toko (warranty_type=store):
     *  - status RECEIVED        : menunggu dimulai servis
     *  - status INTERNAL_REPAIR : sedang dikerjakan teknisi
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('warranty_type', 'store')
            ->whereIn('status', [
                ReturnRequest::STATUS_RECEIVED,
                ReturnRequest::STATUS_INTERNAL_REPAIR,
            ]);
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
                    ->color(fn (string $state): string => match ($state) {
                        ReturnRequest::STATUS_RECEIVED        => 'info',
                        ReturnRequest::STATUS_INTERNAL_REPAIR  => 'warning',
                        default                               => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        ReturnRequest::STATUS_RECEIVED        => 'Menunggu Servis',
                        ReturnRequest::STATUS_INTERNAL_REPAIR  => 'Sedang Dikerjakan',
                        default                               => ReturnRequest::getStatusLabels()[$state] ?? $state,
                    }),
            ])
            ->actions([
                // 1. MULAI SERVIS (received -> internal_repair)
                Tables\Actions\Action::make('start_repair')
                    ->label('Mulai Servis')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_RECEIVED)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Kerja')
                            ->placeholder('Diagnosa awal, rencana perbaikan, dll.')
                            ->rows(3),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        app(ReturnWorkflowService::class)->startInternalRepair($record, $data['notes'] ?? null);

                        \Filament\Notifications\Notification::make()
                            ->title('Servis Dimulai')
                            ->body("RMA {$record->rma_number} sedang dikerjakan.")
                            ->success()->send();
                    }),

                // 2. SELESAI SERVIS (internal_repair -> ready_for_return)
                Tables\Actions\Action::make('complete_repair')
                    ->label('Selesaikan Servis')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (ReturnRequest $record) => $record->status === ReturnRequest::STATUS_INTERNAL_REPAIR)
                    ->form([
                        Forms\Components\Radio::make('resolution_type')
                            ->label('Hasil Pengerjaan')
                            ->options(ReturnRequest::getResolutionTypeLabels())
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('new_serial_number_id')
                            ->label('Pilih SN Unit Pengganti')
                            ->options(function (ReturnRequest $record) {
                                $productId = $record->serialNumber?->product_id ?? $record->product_id;
                                if (! $productId) {
                                    return [];
                                }
                                return SerialNumber::where('product_id', $productId)
                                    ->where('status', SerialNumber::STATUS_AVAILABLE)
                                    ->pluck('serial_number', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->visible(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT)
                            ->required(fn (Forms\Get $get) => $get('resolution_type') === ReturnRequest::RESOLUTION_REPLACEMENT),

                        Forms\Components\Textarea::make('internal_notes')
                            ->label('Catatan Teknisi')
                            ->rows(3),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        app(ReturnWorkflowService::class)->completeInternalRepair($record, $data);

                        \Filament\Notifications\Notification::make()
                            ->title('Servis Selesai')
                            ->body("RMA {$record->rma_number} siap dikembalikan ke klien.")
                            ->success()->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Servis Unit'),
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