<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\DeliveryOrder;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DeliveryOrderReportTable extends BaseWidget
{
    public $filters = [];

    protected function getTableQuery(): Builder
    {
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->filters['end_date'] ?? now();

        return DeliveryOrder::query()
            ->whereBetween('do_date', [$startDate, $endDate])
            ->latest('do_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('do_date')
                ->label('Tanggal Kirim')
                ->date('d M Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('do_number')
                ->label('No. DO')
                ->searchable(),

            Tables\Columns\TextColumn::make('salesOrder.order_number')
                ->label('Ref. SO')
                ->searchable(),

            Tables\Columns\TextColumn::make('customer.name')
                ->label('Penerima'),

            Tables\Columns\TextColumn::make('status')
                ->labe('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft'     => 'gray',
                    'ready'     => 'info',
                    'pending'   => 'warning',
                    'shipped'   => 'primary',
                    'delivered' => 'success',
                    'returned'  => 'danger',
                    default     => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),

        ];
    }
}
