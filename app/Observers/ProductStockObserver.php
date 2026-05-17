<?php

namespace App\Observers;

use App\Models\Inventory\ProductStock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProductStockObserver
{
    public function created(ProductStock $productStock): void
    {
        $this->checkLowStock($productStock);
    }

    public function updated(ProductStock $productStock): void
    {
        // Ganti 'qty' menjadi 'qty_available'
        if ($productStock->wasChanged('qty')) {
            $this->checkLowStock($productStock);
        }
    }

    protected function checkLowStock(ProductStock $productStock): void
    {
        try {
            $product = $productStock->product;
            $currentQty = $productStock->qty;

            // Praktik ERP: Threshold harus dinamis, ambil dari master produk.
            // Jika tidak ada di tabel produk, gunakan fallback angka statis.
            $threshold = $product->min_stock_threshold ?? 10;

            if ($currentQty <= $threshold) {

                // Cache query User selama 24 jam agar database tidak terbebani setiap detik
                $usersToNotify = Cache::remember('users_to_notify_low_stock', now()->addDay(), function () {
                    $users = User::whereHas('roles', function ($query) {
                        $query->whereIn('name', ['super_admin', 'admin', 'manager', 'panel_user']);
                    })->get();

                    return $users->isEmpty() ? User::all() : $users;
                });

                foreach ($usersToNotify as $user) {
                    $existingUnread = $user->unreadNotifications()
                        ->where('type', LowStockNotification::class)
                        ->where('data->product_id', $product->id)
                        ->where('data->warehouse_id', $productStock->id)
                        ->exists();

                    if (!$existingUnread) {
                        $user->notify(new LowStockNotification($product, $productStock, $currentQty));
                    }

                    LowStockNotification::sendFilamentNotification($product, $productStock, $currentQty, $user);
                }

                Log::info("Low stock alert sent for product: {$product->product_name} in warehouse: {$productStock->warehouse->warehouse_name} (Qty: {$currentQty})");
            }
        } catch (\Exception $e) {
            Log::error("Error checking low stock: " . $e->getMessage());
        }
    }
}
