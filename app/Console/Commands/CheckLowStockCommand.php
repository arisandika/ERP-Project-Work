<?php

namespace App\Console\Commands;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;

class CheckLowStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-low
                            {--notify : Send notifications to admins}
                            {--force : Force send notifications even if already sent}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for products with low stock and optionally send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for low stock items...');
        $this->newLine();

        // Get all product stocks with low qty
        $lowStockItems = ProductStock::query()
            ->select('nx_product_stock.*')
            ->join('nx_products', 'nx_product_stock.product_id', '=', 'nx_products.id')
            ->where('nx_product_stock.qty', '<=', 10)
            ->with(['product.unit', 'warehouse'])
            ->orderBy('nx_product_stock.qty', 'asc')
            ->get();

        if ($lowStockItems->isEmpty()) {
            $this->info('✅ No low stock items found. All stocks are healthy!');
            return Command::SUCCESS;
        }

        $this->warn("⚠️  Found {$lowStockItems->count()} low stock items:");
        $this->newLine();

        // Display table
        $tableData = [];
        foreach ($lowStockItems as $stock) {
            $tableData[] = [
                'Product' => $stock->product->product_name,
                'Code' => $stock->product->kode_barang,
                'Warehouse' => $stock->warehouse->warehouse_name,
                'Current Qty' => $stock->qty . ' ' . $stock->product->unit->unit_name,
                'Threshold' => '10 ' . $stock->product->unit->unit_name,
                'Status' => $this->getStockStatus($stock->qty),
            ];
        }

        $this->table(
            ['Product', 'Code', 'Warehouse', 'Current Qty', 'Min Stock', 'Status'],
            $tableData
        );

        // Send notifications if requested
        if ($this->option('notify')) {
            $this->newLine();
            $this->info('📧 Sending notifications to admins...');
            
            $users = User::whereHas('roles', function ($query) {
                $query->whereIn('name', ['super_admin', 'admin', 'manager']);
            })->get();

            if ($users->isEmpty()) {
                $this->warn('⚠️  No admin users found to notify.');
                return Command::SUCCESS;
            }

            $notificationsSent = 0;
            $force = $this->option('force');

            foreach ($lowStockItems as $stock) {
                foreach ($users as $user) {
                    // Check if notification already exists and unread (unless forced)
                    if (!$force) {
                        $existingUnread = $user->unreadNotifications()
                            ->where('type', LowStockNotification::class)
                            ->where('data->product_id', $stock->product->id)
                            ->where('data->warehouse_id', $stock->id)
                            ->exists();

                        if ($existingUnread) {
                            continue;
                        }
                    }

                    $user->notify(new LowStockNotification(
                        $stock->product,
                        $stock,
                        $stock->qty
                    ));
                    LowStockNotification::sendFilamentNotification(
                        $stock->product,
                        $stock,
                        $stock->qty,
                        $user
                    );
                    $notificationsSent++;
                }
            }

            $this->info("✅ Sent {$notificationsSent} notifications to " . $users->count() . " admin(s).");
        } else {
            $this->newLine();
            $this->comment('💡 Use --notify flag to send notifications to admins.');
        }

        return Command::SUCCESS;
    }

    /**
     * Get stock status label
     */
    protected function getStockStatus(int $qty): string
    {
        return match (true) {
            $qty <= 0 => '🔴 OUT OF STOCK',
            $qty <= 5 => '🔴 CRITICAL',
            $qty <= 10 => '🟠 LOW',
            default => '🟡 WARNING',
        };
    }
}

