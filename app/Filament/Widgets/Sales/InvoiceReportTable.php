<?php

namespace App\Filament\Widgets\Sales;

use App\Models\Sales\Invoice;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class InvoiceReportTable extends BaseWidget
{
    public $filters = [];

    protected function getTableQuery(): Builder
    {
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->filters['end_date'] ?? now();

        return Invoice::query()
            ->whereBetween('invoice_date', [$startDate, $endDate])
            ->latest('invoice_date');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('invoice_date')
                ->label('Tanggal')
                ->date('d M Y')
                ->sortable(),

            Tables\Columns\TextColumn::make('invoice_number')
                ->label('No. Invoice')
                ->searchable(),

            Tables\Columns\TextColumn::make('customer.name')
                ->label('Customer'),

            Tables\Columns\TextColumn::make('grand_total')
                ->label('Total Tagihan')
                ->money('IDR')
                    ->color(fn($state) => $state < 0 ? 'danger' : 'success')
                    ->sortable()
                    ->weight('semibold'),

            Tables\Columns\TextColumn::make('due_date')
                ->label('Jatuh Tempo')
                ->date('d M Y')
                ->color(fn ($record) => $record->due_date < now() && $record->status !== 'paid' ? 'danger' : 'gray'),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'draft' => 'gray',
                    'unpaid' => 'danger',
                    'partial' => 'warning',
                    'paid' => 'success',
                    default => 'gray',
                })
                ->formatStateUsing(fn(string $state): string => ucwords(str_replace('_', ' ', $state))),
        ];
    }
}
