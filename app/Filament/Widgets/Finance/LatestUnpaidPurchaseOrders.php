<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Procurement\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUnpaidPurchaseOrders extends BaseWidget
{
    protected static ?string $heading = '🟡 Hutang Supplier (A/P) Menunggu';
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::whereIn('status', ['sent', 'partial'])
                    ->orderBy('created_at', 'asc')
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm'),

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->limit(15)
                    ->size('sm'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'warning',
                        'partial' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper($state))
                    ->size('sm'),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR', true)
                    ->color('danger')
                    ->weight('bold')
                    ->size('sm'),
            ])
            ->paginated(false)
            ->striped();
    }
}
