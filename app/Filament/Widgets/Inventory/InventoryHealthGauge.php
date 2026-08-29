<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class InventoryHealthGauge extends ChartWidget
{
    protected static ?string $heading = 'Inventory Health Score';

    protected static ?string $maxHeight = '300px';

    protected static bool $isLazy = true;

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
        'xl' => 6,
    ];

    protected function getData(): array
    {
        $healthScore = Cache::remember('inventory_health_composite', now()->addMinutes(15), fn() =>
            $this->calculateHealthScore());

        return [
            'datasets' => [
                [
                    'data' => [$healthScore, 100 - $healthScore],
                    'backgroundColor' => [$this->getScoreColor($healthScore), '#e5e7eb'],
                    'borderWidth' => 0,
                    'cutout' => '75%',
                ],
            ],
            'labels' => ['Health', 'Gap'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        $score = Cache::get('inventory_health_composite', 0);

        return [
            'cutout' => '75%',
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => ['enabled' => false],
            ],
            'center' => [
                'text' => $score . '%',
                'color' => $this->getScoreColor($score),
                'font' => ['size' => 24, 'weight' => 'bold'],
            ],
        ];
    }

    /**
     * Composite health score:
     * - Low stock ratio: 30% weight
     * - Overstock ratio: 20% weight
     * - Negative stock ratio: 25% weight
     * - Idle stock ratio: 25% weight
     */
    private function calculateHealthScore(): int
    {
        $totalProducts = Product::count();
        if ($totalProducts === 0)
            return 100;

        $lowStockCount = Product::whereHas('productStocks', fn($q) =>
            $q->whereColumn('qty_available', '<=', 'nx_products.min_stock')->where('qty_available', '>', 0))->count();
        $lowStockRatio = $lowStockCount / $totalProducts;

        $overstockCount = Product::where('max_stock', '>', 0)
            ->whereHas('productStocks', fn($q) =>
                $q
                    ->join('nx_products', 'nx_products.id', '=', 'nx_product_stock.product_id')
                    ->whereColumn('nx_product_stock.qty_available', '>', 'nx_products.max_stock'))
            ->count();
        $overstockRatio = $overstockCount / $totalProducts;

        $negativeCount = ProductStock::where('qty_available', '<', 0)->count();
        $negativeStockRatio = $negativeCount / max(ProductStock::count(), 1);

        $idleProductIds = Product::whereDoesntHave('stockTransactions', fn($q) =>
            $q->where('transaction_date', '>=', now()->subDays(60)))->pluck('id');
        $idleCount = ProductStock::whereIn('product_id', $idleProductIds)
            ->where('qty_available', '>', 0)
            ->count();
        $idleRatio = $idleCount / max(ProductStock::count(), 1);

        $weightedSum = ($lowStockRatio * 0.3)
            + ($overstockRatio * 0.2)
            + ($negativeStockRatio * 0.25)
            + ($idleRatio * 0.25);

        return max(0, min(100, (int) round((1 - $weightedSum) * 100)));
    }

    private function getScoreColor(int $score): string
    {
        return match (true) {
            $score >= 80 => '#10b981',
            $score >= 60 => '#f59e0b',
            default => '#ef4444',
        };
    }
}
