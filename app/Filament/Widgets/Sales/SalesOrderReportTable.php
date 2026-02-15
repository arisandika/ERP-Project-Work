<?php

namespace App\Filament\Widgets\Sales;

use App\Filament\Exports\SalesOrderExporter;
use App\Models\Sales\SalesOrder; // Sesuaikan Model Lo
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Actions\ExportAction;

class SalesOrderReportTable extends BaseWidget
{
    public $filters = [];

    protected function getTableQuery(): Builder
    {
        // Ambil tanggal dari filter, atau default bulan ini
        $startDate = $this->filters['start_date'] ?? now()->startOfMonth();
        $endDate = $this->filters['end_date'] ?? now();

        return SalesOrder::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest();
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('created_at')
                ->label('Tanggal Order')
                ->date()
                ->sortable(),
            Tables\Columns\TextColumn::make('order_number')
                ->label('No. SO')
                ->searchable(),
            Tables\Columns\TextColumn::make('customer.name')
                ->label('Client'),
            Tables\Columns\TextColumn::make('grand_total')
                ->label('Total')
                ->money('IDR'),
            Tables\Columns\TextColumn::make('status')
                ->badge(),
        ];
    }

    // Fitur Export ke Excel
    protected function getTableHeaderActions(): array
    {
        return [
            ExportAction::make()
                ->exporter(SalesOrderExporter::class) // Pastikan lo udah run: php artisan make:filament-exporter SalesOrderExporter
                ->label('Export Excel'),
        ];
    }
}
