<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Resources\AfterSales\InternalRepairResource\Pages;
use App\Models\AfterSales\InternalRepair;
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
    protected static ?string $model = InternalRepair::class;
    protected static ?string $modelLabel = 'Servis Internal';
    protected static ?string $pluralModelLabel = 'Daftar Servis Internal';
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Servis Internal';
    protected static ?string $slug = 'after-sales/internal-repairs';
    protected static ?int $navigationSort = 22;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma.rma_number')
                    ->label('No. RMA')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('rma.issue_description')
                    ->label('Keluhan')
                    ->limit(50),

                Tables\Columns\TextColumn::make('resolution_type')
                    ->label('Jenis Servis')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state
                        ? (ReturnRequest::getResolutionTypeLabels()[$state] ?? $state)
                        : '—'),

                Tables\Columns\TextColumn::make('technician.name')
                    ->label('Teknisi')
                    ->placeholder('Belum ditugaskan'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        InternalRepair::STATUS_PENDING    => 'info',
                        InternalRepair::STATUS_IN_PROGRESS => 'warning',
                        InternalRepair::STATUS_COMPLETED  => 'success',
                        default                           => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => InternalRepair::getStatusLabels()[$state] ?? $state),
            ])
            ->actions([
                // 1. MULAI SERVIS (pending -> in_progress)
                Tables\Actions\Action::make('start_repair')
                    ->label('Mulai Servis')
                    ->icon('heroicon-o-play')
                    ->color('primary')
                    ->visible(fn (InternalRepair $record) => $record->status === InternalRepair::STATUS_PENDING)
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Kerja')
                            ->placeholder('Diagnosa awal, rencana perbaikan, dll.')
                            ->rows(3),
                    ])
                    ->action(function (InternalRepair $record, array $data) {
                        $record->update([
                            'status'            => InternalRepair::STATUS_IN_PROGRESS,
                            'technician_user_id' => auth()->id(),
                            'started_at'        => now(),
                            'notes'             => $data['notes'] ?? $record->notes,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->title('Servis Dimulai')
                            ->body("RMA {$record->rma->rma_number} sedang dikerjakan.")
                            ->success()->send();
                    }),

                // 2. SELESAI SERVIS (in_progress -> completed) — via service agar SN + RMA ikut update
                Tables\Actions\Action::make('complete_repair')
                    ->label('Selesaikan Servis')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (InternalRepair $record) => $record->status === InternalRepair::STATUS_IN_PROGRESS)
                    ->form([
                        Forms\Components\Radio::make('resolution_type')
                            ->label('Hasil Pengerjaan')
                            ->options(ReturnRequest::getResolutionTypeLabels())
                            ->inline()
                            ->live()
                            ->required(),

                        Forms\Components\Select::make('new_serial_number_id')
                            ->label('Pilih SN Unit Pengganti')
                            ->options(function (InternalRepair $record) {
                                $productId = $record->rma?->serialNumber?->product_id ?? $record->rma?->product_id;
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
                    ->action(function (InternalRepair $record, array $data) {
                        app(ReturnWorkflowService::class)->completeInternalRepair($record->rma, $data);

                        \Filament\Notifications\Notification::make()
                            ->title('Servis Selesai')
                            ->body("RMA {$record->rma->rma_number} siap dikembalikan ke klien.")
                            ->success()->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Servis Unit'),
            ])
            ->emptyStateHeading('Tidak ada antrean servis internal');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->hasRole(['super_admin', 'warehouse_employees']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternalRepairs::route('/'),
        ];
    }
}