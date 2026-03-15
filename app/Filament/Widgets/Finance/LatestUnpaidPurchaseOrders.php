<?php

namespace App\Filament\Widgets\Finance;

use App\Models\Procurement\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestUnpaidPurchaseOrders extends BaseWidget
{
    protected static ?string $heading = '🟡 Hutang Supplier (A/P) Menunggu Pembayaran';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::whereIn('status', ['sent', 'partial'])
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('No. PO')
                    ->weight('bold')
                    ->color('primary'), // FIX: Fitur URL link dihapus agar tidak error RouteNotFound

                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'warning',
                        'partial' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Harus Dibayar')
                    ->money('IDR', true)
                    ->color('danger')
                    ->weight('bold'),
            ])
            ->paginated([5])
            ->defaultPaginationPageOption(5);
    }
}
