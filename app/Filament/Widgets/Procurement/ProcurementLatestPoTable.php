<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget; // <-- Pastikan extends TableWidget, BUKAN Widget biasa

class ProcurementLatestPoTable extends BaseWidget
{
    protected static ?string $heading = 'Purchase Order Terakhir';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full';

    // Kunci biar cuma muncul di dashboard procurement
    public static function canView(): bool
    {
        return request()->routeIs('filament.admin.pages.procurement-dashboard')
            || request()->routeIs('livewire.update');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('po_number')
                    ->label('Nomor PO')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('supplier.name')
                    ->label('Supplier'),
                Tables\Columns\TextColumn::make('order_date')
                    ->label('Tgl Pesan')
                    ->date('d M Y'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'partial' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state) => strtoupper($state)),
                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Total Nilai')
                    ->money('IDR', true)
                    ->weight('bold'),
            ])
            ->paginated(false); // Matikan pagination biar ringkas
    }
}
