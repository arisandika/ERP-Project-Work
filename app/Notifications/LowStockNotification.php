<?php

namespace App\Notifications;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Product $product,
        public ?ProductStock $productStock = null,
        public ?int $currentQty = null
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification for database.
     */
    public function toDatabase(object $notifiable): array
    {
        $warehouseName = $this->productStock 
            ? $this->productStock->warehouse->warehouse_name 
            : 'All Warehouses';
        
        $qty = $this->currentQty ?? ($this->productStock ? $this->productStock->qty : $this->product->total_stock);

        return [
            'product_id' => $this->product->id_product,
            'product_name' => $this->product->product_name,
            'product_code' => $this->product->kode_barang,
            'warehouse_id' => $this->productStock?->id_warehouse,
            'warehouse_name' => $warehouseName,
            'current_qty' => $qty,
            'min_stock' => 10,
            'title' => 'Stok Hampir Habis',
            'body' => "{$this->product->product_name} di {$warehouseName} tersisa {$qty} {$this->product->unit->unit_name}",
        ];
    }

    /**
     * Send Filament notification (for real-time popup)
     */
    public static function sendFilamentNotification(
        Product $product, 
        ?ProductStock $productStock = null,
        ?int $currentQty = null,
        ?object $recipient = null
    ): void {
        $warehouseName = $productStock 
            ? $productStock->warehouse->warehouse_name 
            : 'All Warehouses';
        
        $qty = $currentQty ?? ($productStock ? $productStock->qty : $product->total_stock);

        $notification = FilamentNotification::make()
            ->warning()
            ->title('Stok Hampir Habis')
            ->body("{$product->product_name} di {$warehouseName} tersisa {$qty} {$product->unit->unit_name}")
            ->persistent()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label('Lihat Detail')
                    ->url('/inventory/products/' . $product->id_product)
                    ->button(),
                \Filament\Notifications\Actions\Action::make('dismiss')
                    ->label('Tutup')
                    ->close(),
            ]);

        // Send to specific recipient or current user
        if ($recipient) {
            $recipient->notifyNow($notification->toDatabase());
        } elseif (auth()->check()) {
            auth()->user()->notifyNow($notification->toDatabase());
        }
    }
}

