<?php

namespace App\Filament\Widgets\Sales;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\Invoice;

class SalesPipelineChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected static ?string $heading = 'Sales Pipeline Funnel';
    protected static ?int $sort = 2;

    protected static ?string $maxHeight = '400px';

    protected function getData(): array
    {
        $start = $this->filters['startDate'] ?? null;
        $end = $this->filters['endDate'] ?? null;

        $count = function ($model, $col = 'created_at') use ($start, $end) {
            return $model::query()
                ->when($start, fn($q) => $q->whereDate($col, '>=', $start))
                ->when($end, fn($q) => $q->whereDate($col, '<=', $end))
                ->count();
        };

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah Dokumen',
                    'data' => [
                        $count(Quotation::class, 'quotation_date'),
                        $count(SalesOrder::class),
                        $count(DeliveryOrder::class),
                        $count(Invoice::class, 'invoice_date'),
                    ],
                    'backgroundColor' => ['#9CA3AF', '#3B82F6', '#F59E0B', '#10B981'],
                    'borderRadius' => 4,
                ],
            ],
            'labels' => ['Quotations', 'Sales Orders', 'Delivery Orders', 'Invoices'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
