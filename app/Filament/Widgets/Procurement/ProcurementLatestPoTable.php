<?php

namespace App\Filament\Widgets\Procurement;

use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Procurement\PurchaseOrder;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;

class ProcurementLatestPoTable extends BaseWidget
{
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?string $heading = 'Purchase Order Terbaru';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()
                    ->with('supplier')
                    ->latest('created_at')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->icon('heroicon-m-building-office'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tanggal Order')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', locale: 'id')
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(PurchaseOrderStatus $state): string => match ($state) {
                        PurchaseOrderStatus::DRAFT => 'gray',
                        PurchaseOrderStatus::SENT => 'info',
                        PurchaseOrderStatus::PARTIAL => 'warning',
                        PurchaseOrderStatus::COMPLETED => 'success',
                        PurchaseOrderStatus::CANCELLED => 'danger',
                    }),
            ])
            ->paginated(false)
            ->emptyStateHeading('Belum Ada PO')
            ->emptyStateDescription('Belum ada purchase order yang dibuat.')
            ->emptyStateIcon('heroicon-o-document-text');
    }
}
