<?php

namespace App\Console\Commands;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Console\Command;

class TestLowStockNotification extends Command
{
    protected $signature = 'stock:test-notification 
                            {user_id? : ID of the user to send notification to}';

    protected $description = 'Test low stock notification system';

    public function handle()
    {
        $this->info('🧪 Testing Low Stock Notification System...');
        $this->newLine();

        // 1. Check if notifications table exists
        try {
            \DB::table('notifications')->count();
            $this->info('✅ Notifications table exists');
        } catch (\Exception $e) {
            $this->error('❌ Notifications table does not exist. Run: php artisan migrate');
            return Command::FAILURE;
        }

        // 2. Check if there are users
        $userCount = User::count();
        $this->info("✅ Found {$userCount} user(s) in database");

        if ($userCount === 0) {
            $this->error('❌ No users found. Please create a user first.');
            return Command::FAILURE;
        }

        // 3. Get target user
        $userId = $this->argument('user_id');
        $user = $userId ? User::find($userId) : User::first();

        if (!$user) {
            $this->error('❌ User not found.');
            return Command::FAILURE;
        }

        $this->info("✅ Target user: {$user->name} ({$user->email})");

        // 4. Check user roles
        if (method_exists($user, 'getRoleNames')) {
            $roles = $user->getRoleNames();
            if ($roles->isEmpty()) {
                $this->warn('⚠️  User has no roles assigned');
            } else {
                $this->info('✅ User roles: ' . $roles->implode(', '));
            }
        }

        // 5. Check for low stock items
        $lowStockCount = ProductStock::query()
            ->join('nx_products', 'nx_product_stock.product_id', '=', 'nx_products.id')
            ->where('nx_product_stock.qty', '<=', 10)
            ->count();

        if ($lowStockCount > 0) {
            $this->info("✅ Found {$lowStockCount} low stock item(s)");
            
            // Get first low stock item
            $lowStock = ProductStock::query()
                ->select('nx_product_stock.*')
                ->join('nx_products', 'nx_product_stock.product_id', '=', 'nx_products.id')
                ->where('nx_product_stock.qty', '<=', 10)
                ->with(['product.unit', 'warehouse'])
                ->first();

            $this->newLine();
            $this->info('📦 Sample Low Stock Item:');
            $this->table(
                ['Product', 'Warehouse', 'Qty', 'Min Stock'],
                [[
                    $lowStock->product->product_name,
                    $lowStock->warehouse->warehouse_name,
                    $lowStock->qty,
                    10
                ]]
            );
        } else {
            $this->warn('⚠️  No low stock items found in database');
            $this->comment('💡 You can manually set a product stock qty < 10 to test');
        }

        // 6. Test sending notification
        $this->newLine();
        if ($this->confirm('Do you want to send a test notification?', true)) {
            
            // Find or create a low stock item for testing
            $testStock = ProductStock::query()
                ->select('nx_product_stock.*')
                ->join('nx_products', 'nx_product_stock.product_id', '=', 'nx_products.id')
                ->where('nx_product_stock.qty', '<=', 10)
                ->with(['product', 'warehouse'])
                ->first();

            if (!$testStock) {
                $testStock = ProductStock::with(['product', 'warehouse'])->first();
                
                if (!$testStock) {
                    $this->error('❌ No product stock found in database');
                    return Command::FAILURE;
                }
                
                $this->warn('⚠️  Using first available stock for testing (may not be low stock)');
            }

            try {
                // Send notification
                $user->notify(new LowStockNotification(
                    $testStock->product,
                    $testStock,
                    $testStock->qty
                ));
                LowStockNotification::sendFilamentNotification(
                    $testStock->product,
                    $testStock,
                    $testStock->qty,
                    $user
                );

                $this->info('✅ Notification sent successfully!');
                $this->newLine();
                $this->info('📬 Check:');
                $this->line('  1. Bell icon in Filament dashboard');
                $this->line('  2. Database table: notifications');
                $this->line('  3. Log file: storage/logs/laravel.log');

                // Check if notification was saved
                $notifCount = $user->notifications()->count();
                $this->info("✅ User now has {$notifCount} notification(s) in database");

            } catch (\Exception $e) {
                $this->error('❌ Error sending notification: ' . $e->getMessage());
                $this->error($e->getTraceAsString());
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('🎉 Test completed!');
        
        return Command::SUCCESS;
    }
}

