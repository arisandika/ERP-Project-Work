<?php
namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Deal;
use App\Models\CRM\Lead;
use App\Models\Sales\Quotation;
use App\Models\Sales\SalesOrder;
use Filament\Widgets\ChartWidget;

class SalesFunnelChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Sales Funnel';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 2,
    ];

    protected function getData(): array
    {
        $totalLeads = Lead::count();
        $qualifiedLeads = Lead::whereIn('status', [Lead::STATUS_QUALIFIED, Lead::STATUS_CONVERTED])->count();
        $totalDeals = Deal::count();
        $totalQuotations = Quotation::count();
        $wonDeals = Deal::where('status', Deal::STATUS_CLOSED_WON)->count();
        $salesOrders = SalesOrder::count();

        return [
            'datasets' => [
                [
                    'label' => 'Jumlah',
                    'data' => [$totalLeads, $qualifiedLeads, $totalDeals, $totalQuotations, $wonDeals, $salesOrders],
                    'backgroundColor' => ['#94a3b8', '#60a5fa', '#818cf8', '#fbbf24', '#22c55e', '#10b981'],
                ],
            ],
            'labels' => ['Lead', 'Qualified', 'Deal', 'Penawaran', 'Won', 'Sales Order'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y',
            'plugins' => ['legend' => ['display' => false]],
        ];
    }
}
