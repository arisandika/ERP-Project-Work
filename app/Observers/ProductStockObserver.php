<?php

namespace App\Observers;

use App\Models\Inventory\ProductStock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Log;

class ProductStockObserver
{
    /**
     * Handle the ProductStock "created" event.
     */
    public function created(ProductStock $productStock): void
    {
        $this->checkLowStock($productStock);
    }

    /**
     * Handle the ProductStock "updated" event.
     */
    public function updated(ProductStock $productStock): void
    {
        // Hanya check jika qty berubah
        if ($productStock->wasChanged('qty')) {
            $this->checkLowStock($productStock);
        }
    }

    /**
     * Check if stock is low and send notification
     */
    protected function checkLowStock(ProductStock $productStock): void
    {
        try {
            $product = $productStock->product;
            $currentQty = $productStock->qty;
            
            // Threshold low stock: <= 10
            $threshold = 10;
            
            // Cek apakah stok di bawah threshold
            if ($currentQty <= $threshold) {
                // Get users with admin roles (or all users if no roles assigned)
                $users = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['super_admin', 'admin', 'manager', 'panel_user']);
                })->get();

                // If no users with roles found, get all users (fallback)
                if ($users->isEmpty()) {
                    $users = User::all();
                }

                foreach ($users as $user) {
                    // Cek apakah sudah ada notifikasi serupa yang belum dibaca
                    $existingUnread = $user->unreadNotifications()
                        ->where('type', LowStockNotification::class)
                        ->where('data->product_id', $product->id_product)
                        ->where('data->warehouse_id', $productStock->id_warehouse)
                        ->exists();

                    // Hanya kirim notifikasi database jika belum ada yang sama
                    if (!$existingUnread) {
                        $user->notify(new LowStockNotification($product, $productStock, $currentQty));
                    }

                    // Selalu kirim notifikasi Filament ke bell (idempoten di sisi UI)
                    LowStockNotification::sendFilamentNotification($product, $productStock, $currentQty, $user);
                }

                Log::info("Low stock alert sent for product: {$product->product_name} in warehouse: {$productStock->warehouse->warehouse_name} (Qty: {$currentQty})");
            }
        } catch (\Exception $e) {
            Log::error("Error checking low stock: " . $e->getMessage());
        }
    }
}

