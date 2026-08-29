<?php

namespace App\Filament\Widgets\Finance;

use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Procurement\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUnpaidPurchaseOrders extends BaseWidget
{
    protected static ?string $heading = 'Outstanding Payables';
    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 12,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()
                    ->with('supplier')
                    ->whereIn('status', [
                        PurchaseOrderStatus::SENT,
                        PurchaseOrderStatus::PARTIAL,
                    ])
                    ->latest()
                    ->limit(5)
            )
            ->striped()
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('PO Number')
                    ->weight('semibold')
                    ->color('primary')
                    ->searchable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->limit(20)
                    ->icon('heroicon-m-building-office'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => str($state->value ?? $state)->title()->toString()),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Outstanding')
                    ->money('IDR', locale: 'id')
                    ->alignRight()
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-eye')
                    ->url(fn ($record) => route('filament.admin.resources.procurement.purchase-orders.edit', $record)),
            ])
            ->emptyStateHeading('Tidak Ada Hutang')
            ->emptyStateDescription('Semua PO sudah lunas.')
            ->emptyStateIcon('heroicon-o-check-badge')
            ->paginated(false);
    }
}
