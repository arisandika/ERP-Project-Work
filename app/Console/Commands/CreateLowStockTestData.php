<?php

namespace App\Console\Commands;

use App\Models\Inventory\ProductStock;
use Illuminate\Console\Command;

class CreateLowStockTestData extends Command
{
    protected $signature = 'stock:create-test-data
                            {--qty=5 : Set stock quantity to this value}';

    protected $description = 'Create low stock test data by updating first product stock';

    public function handle()
    {
        $this->info('🔧 Creating low stock test data...');
        $this->newLine();

        $qty = (int) $this->option('qty');

        // Get first product stock
        $stock = ProductStock::with(['product', 'warehouse'])->first();

        if (!$stock) {
            $this->error('❌ No product stock found. Please create products and stocks first.');
            return Command::FAILURE;
        }

        $oldQty = $stock->qty;
        
        $this->info("📦 Product: {$stock->product->product_name}");
        $this->info("🏭 Warehouse: {$stock->warehouse->warehouse_name}");
        $this->info("📊 Current qty: {$oldQty}");
        $this->info("📉 New qty: {$qty}");
        $this->newLine();

        if ($this->confirm('Update stock quantity?', true)) {
            $stock->qty = $qty;
            $stock->save();

            $this->info('✅ Stock updated successfully!');
            $this->newLine();
            
            $this->info('🔔 Notification should be triggered automatically via Observer.');
            $this->info('📧 Check bell icon in Filament dashboard!');
            $this->newLine();
            
            $this->comment('💡 To test again, run:');
            $this->comment('   php artisan stock:create-test-data --qty=3');
        }

        return Command::SUCCESS;
    }
}

