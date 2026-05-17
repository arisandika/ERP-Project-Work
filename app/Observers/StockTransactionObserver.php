<?php

namespace App\Observers;

use App\Models\Inventory\StockTransaction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class StockTransactionObserver
{
    /**
     * Menangani event saat transaksi baru dicatat (Barang Masuk/Keluar).
     */
    public function created(StockTransaction $stockTransaction): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Menangani event saat ada revisi/perubahan data transaksi.
     */
    public function updated(StockTransaction $stockTransaction): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Menangani event saat transaksi dibatalkan/dihapus.
     */
    public function deleted(StockTransaction $stockTransaction): void
    {
        $this->invalidateDashboardCache();
    }

    /**
     * Centralized method untuk menghapus cache yang spesifik.
     */
    private function invalidateDashboardCache(): void
    {
        try {
            // Daftar key cache yang digunakan pada DashboardInventory
            $cacheKeys = [
                'inventory_stats_overview',
                'inventory_health_score',
                'inventory_operational_velocity',
                'movement_analysis_30d',
                // Anda bisa menambahkan key lain di sini di masa mendatang
            ];

            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            // Opsi: Anda dapat menghapus log ini saat naik ke tahap Production
            Log::info('Dashboard cache invalidated due to stock transaction mutation.');

        } catch (\Exception $e) {
            Log::error('Gagal menghapus cache dashboard: ' . $e->getMessage());
        }
    }
}
