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

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = [
        'xl' => 6,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()
                    ->whereIn('status', [
                        PurchaseOrderStatus::SENT,
                        PurchaseOrderStatus::PARTIAL
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->limit(20),

                Tables\Columns\TextColumn::make('status')
                    ->badge(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Outstanding')
                    ->money('IDR', locale: 'id')
                    ->alignRight()
                    ->weight('bold')
                    ->color('danger'),
            ])
            ->paginated(false);
    }
}
