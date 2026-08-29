<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Cache;

class AlertBanner extends Widget
{
    protected static string $view = 'filament.widgets.inventory.alert-banner';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    protected static ?int $sort = 0;

    public array $alerts = [];

    public function mount(): void
    {
        $this->loadAlerts();
    }

    public function loadAlerts(): void
    {
        $ttl = now()->addMinutes(5);

        $outOfStockWithOrders = Cache::remember('mon_alert_oos_orders', $ttl, fn() =>
            ProductStock::where('qty_available', '<=', 0)
                ->where('qty_reserved', '>', 0)
                ->count());

        $lowStockCount = Cache::remember('mon_alert_low_stock', $ttl, fn() =>
            ProductStock::where('qty_available', '>', 0)
                ->where('qty_available', '<=', 10)
                ->distinct('product_id')
                ->count('product_id'));

        $failedCount = Cache::remember('mon_alert_failed_24h', $ttl, fn() =>
            StockTransaction::where('type', 'keluar')
                ->whereDate('transaction_date', '>=', now()->subDay())
                ->where('stock_after', '<', 0)
                ->count());

        $this->alerts = [];

        if ($outOfStockWithOrders > 0) {
            $this->alerts[] = [
                'level' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'message' => "{$outOfStockWithOrders} produk habis stok dengan pesanan aktif",
                'action_label' => 'Lihat Sekarang',
            ];
        }

        if ($lowStockCount > 0) {
            $this->alerts[] = [
                'level' => 'warning',
                'icon' => 'heroicon-o-exclamation-triangle',
                'message' => "{$lowStockCount} produk di bawah batas minimum — perlu reorder",
                'action_label' => 'Reorder Now',
            ];
        }

        if ($failedCount > 0) {
            $this->alerts[] = [
                'level' => 'danger',
                'icon' => 'heroicon-o-x-circle',
                'message' => "{$failedCount} transaksi gagal dalam 24 jam terakhir",
                'action_label' => 'Investigate',
            ];
        }
    }

    public function getAlerts(): array
    {
        return $this->alerts;
    }

    public function dismissAlert(int $index): void
    {
        unset($this->alerts[$index]);
        $this->alerts = array_values($this->alerts);
    }
}
