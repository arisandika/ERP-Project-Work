<?php

namespace App\Console\Commands;

use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use Illuminate\Console\Command;

class CheckProductMinStock extends Command
{
    protected $signature = 'stock:check-min {product_id?}';
    protected $description = 'Check product min_stock value';

    public function handle()
    {
        $productId = $this->argument('product_id');
        
        if ($productId) {
            $product = Product::find($productId);
            if (!$product) {
                $this->error('Product not found');
                return Command::FAILURE;
            }
            $products = collect([$product]);
        } else {
            $products = Product::with(['productStocks'])->get();
        }

        $data = [];
        foreach ($products as $product) {
            $totalStock = $product->productStocks->sum('qty');
            $threshold = 10;
            
            $data[] = [
                'ID' => $product->id,
                'Name' => $product->product_name,
                'Threshold' => $threshold,
                'Current Stock' => $totalStock,
                'Is Low?' => $totalStock <= $threshold ? '⚠️ YES' : '✅ NO',
            ];
        }

        $this->table(
            ['ID', 'Name', 'Min Stock', 'Threshold', 'Current Stock', 'Is Low?'],
            $data
        );

        return Command::SUCCESS;
    }
}

