<?php

namespace App\Filament\Resources\AfterSales;

use App\Filament\Concerns\BelongsToModule;
use App\Filament\Resources\AfterSales\ReturnRequestResource\Pages;
use App\Models\AfterSales\ReturnRequest;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    use BelongsToModule;

    protected static ?string $module = 'inventory';
    protected static ?string $model = ReturnRequest::class;
    // Konfigurasi Navigasi
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Manajemen After-Sales';
    protected static ?string $navigationLabel = 'Penerimaan Return';
    protected static ?int $navigationSort = 21;

    /**
     * ReturnRequest hanya dibuat lewat Customer Portal (status awal SUBMITTED).
     * Resource ini adalah workflow process, bukan CRUD penuh.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('rma_number')
                    ->label('No. RMA')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Klien')
                    ->searchable(),
                Tables\Columns\TextColumn::make('issue_type')
                    ->label('Jenis Kendala')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ReturnRequest::getIssueTypeLabels()[$state] ?? $state),
                Tables\Columns\TextColumn::make('warranty_type')
                    ->label('Rute Garansi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'supplier' => 'warning',
                        'store' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        ReturnRequest::STATUS_SUBMITTED          => 'gray',
                        ReturnRequest::STATUS_UNDER_REVIEW       => 'warning',
                        ReturnRequest::STATUS_APPROVED           => 'info',
                        ReturnRequest::STATUS_WAITING_FOR_RETURN => 'primary',
                        ReturnRequest::STATUS_RECEIVED           => 'success',
                        ReturnRequest::STATUS_SENT_TO_VENDOR     => 'warning',
                        ReturnRequest::STATUS_INTERNAL_REPAIR    => 'info',
                        ReturnRequest::STATUS_REFUND_PENDING     => 'warning',
                        ReturnRequest::STATUS_READY_FOR_RETURN   => 'primary',
                        ReturnRequest::STATUS_RETURNED_TO_CLIENT => 'success',
                        ReturnRequest::STATUS_REJECTED           => 'danger',
                        ReturnRequest::STATUS_WARRANTY_REJECTED  => 'danger',
                        default                                  => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ReturnRequest::getStatusLabels()[$state] ?? $state),
            ]);
    }

    public static function canViewAny(): bool
    {
        // Ganti dengan otorisasi granular (misal: 'customer_service') saat akan rilis.
        return auth()->user()->hasRole(['super_admin', 'inventory_employees']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'view' => Pages\ViewReturnRequest::route('/{record}'),
        ];
    }
}