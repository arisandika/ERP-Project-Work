<?php

use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->category = Category::create([
        'name' => 'Electronic',
        'description' => 'Electronic devices',
    ]);

    $this->unit = Unit::create([
        'name' => 'Unit',
        'symbol' => 'pcs',
    ]);

    $this->product = Product::create([
        'product_name' => 'Test Product',
        'category_id' => $this->category->id,
        'unit_id' => $this->unit->id,
        'min_stock' => 5,
        'selling_price' => 1000,
        'purchase_price' => 800,
    ]);

    $this->warehouse = Warehouse::create([
        'warehouse_name' => 'Main Warehouse',
        'location' => 'Jakarta',
    ]);
});

test('it creates product stock and increases quantity on stock in transaction', function () {
    StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'mutation_type' => 'stock_in',
        'transaction_code' => 'TX-001',
    ]);

    $stock = ProductStock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock)->not->toBeNull();
    expect($stock->qty_available)->toBe(10);
});

test('it correctly decreases stock on adjustment out transaction', function () {
    // Setup initial stock of 15
    StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 15,
        'mutation_type' => 'stock_in',
        'transaction_code' => 'TX-001',
    ]);

    // Decrease stock by 5
    StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
        'mutation_type' => 'adjustment_out',
        'transaction_code' => 'TX-002',
    ]);

    $stock = ProductStock::where('product_id', $this->product->id)
        ->where('warehouse_id', $this->warehouse->id)
        ->first();

    expect($stock->qty_available)->toBe(10);
});

test('it throws exception when adjusting stock below zero', function () {
    // Setup initial stock of 5
    StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 5,
        'mutation_type' => 'stock_in',
        'transaction_code' => 'TX-001',
    ]);

    // Decrease stock by 10 (should fail)
    expect(fn () => StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => 10,
        'mutation_type' => 'adjustment_out',
        'transaction_code' => 'TX-002',
    ]))->toThrow(Exception::class, 'Stok fisik tidak cukup!');
});

test('it throws exception for negative quantity', function () {
    expect(fn () => StockTransaction::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'quantity' => -5,
        'mutation_type' => 'stock_in',
        'transaction_code' => 'TX-001',
    ]))->toThrow(Exception::class);
});
