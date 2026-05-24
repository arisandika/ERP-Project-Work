<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProcurementPoStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Distribusi Status PO';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $statusCounts = PurchaseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total PO',
                    'data' => array_values($statusCounts),
                    'backgroundColor' => [
                        '#9ca3af', // Draft (Gray)
                        '#3b82f6', // Sent (Blue)
                        '#f59e0b', // Partial (Yellow)
                        '#10b981', // Completed (Green)
                        '#ef4444', // Cancelled (Red)
                    ],
                ],
            ],
            'labels' => array_map('ucfirst', array_keys($statusCounts)),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
