<?php

use App\Filament\Resources\Sales\InvoiceResource\Pages\CreateInvoice;
use App\Filament\Resources\Sales\InvoiceResource\Pages\EditInvoice;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;
use App\Models\Sales\Invoice;
use App\Models\Sales\InvoiceItem;
use App\Models\Sales\SalesOrder;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin']);
    $this->user = User::factory()->create();
    $this->user->assignRole($role);

    $perms = [
        'module.access.sales',
        'view_any_sales::invoice',
        'view_sales::invoice',
        'create_sales::invoice',
        'update_sales::invoice',
    ];
    foreach ($perms as $p) {
        $perm = \Spatie\Permission\Models\Permission::firstOrCreate(['name' => $p]);
        $this->user->givePermissionTo($perm);
    }

    session(['active_module' => 'sales']);
    \App\Support\ModuleAccess::setActive('sales');

    $this->employee = Employee::create([
        'user_id' => $this->user->id,
        'full_name' => 'Staff Invoicing',
        'email' => 'staff-' . uniqid() . '@test.local',
        'phone_number' => '0812' . random_int(10000000, 99999999),
        'position' => 'Staff',
    ]);

    $this->customer = Customer::create([
        'name' => 'Customer Test',
        'email' => 'customer@test.local',
        'phone' => '08123456789',
        'address' => 'Test Address',
        'customer_type' => 'company',
    ]);

    $this->warehouse = Warehouse::create([
        'warehouse_name' => 'Gudang Utama',
        'is_active' => true,
        'location' => 'Jakarta',
    ]);

    $category = Category::create([
        'name' => 'Electronics',
    ]);

    $unit = Unit::create([
        'name' => 'Pcs',
    ]);

    $this->product = Product::create([
        'product_name' => 'Camera Pro',
        'product_code' => 'CAM-001',
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'label' => 'product',
        'selling_price' => 500000,
        'purchase_price' => 350000,
        'min_stock' => 5,
    ]);

    \App\Models\Inventory\ProductStock::create([
        'product_id' => $this->product->id,
        'warehouse_id' => $this->warehouse->id,
        'qty_available' => 50,
        'qty_reserved' => 0,
    ]);

    $this->actingAs($this->user);
});

test('membuat invoice menghitung subtotal dan grand total dengan benar', function () {
    $so = SalesOrder::create([
        'order_number' => 'SO-' . uniqid(),
        'order_date' => now(),
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'status' => 'confirmed',
        'subtotal' => 1000000,
        'discount' => 100000,
        'tax' => 10,
        'grand_total' => 990000,
    ]);

    $so->items()->create([
        'item_type' => 'product',
        'item_id' => $this->product->id,
        'item_code' => $this->product->product_code,
        'item_name' => $this->product->product_name,
        'qty' => 2,
        'unit_price' => 500000,
        'line_total' => 1000000,
    ]);

    Livewire::test(CreateInvoice::class)
        ->set('data.nx_sales_order_id', $so->id)
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = Invoice::latest('id')->first();
    expect($invoice)->not->toBeNull()
        ->and((float) $invoice->subtotal)->toBe(1000000.00)
        ->and((float) $invoice->discount)->toBe(100000.00)
        ->and((float) $invoice->tax)->toBe(10.00)
        ->and((float) $invoice->grand_total)->toBe(990000.00)
        ->and($invoice->items()->count())->toBe(1);

    $savedItem = $invoice->items()->first();
    expect($savedItem->item_name)->toBe('Camera Pro')
        ->and((int) $savedItem->qty)->toBe(2)
        ->and((float) $savedItem->line_total)->toBe(1000000.00);
});

test('mengedit status invoice menjadi paid (lunas) tidak menghapus data item invoice', function () {
    $so = SalesOrder::create([
        'order_number' => 'SO-' . uniqid(),
        'order_date' => now(),
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'status' => 'confirmed',
        'subtotal' => 1000000,
        'discount' => 100000,
        'tax' => 10,
        'grand_total' => 990000,
    ]);

    $invoice = Invoice::create([
        'invoice_number' => Invoice::generateInvoiceNumber(),
        'nx_sales_order_id' => $so->id,
        'nx_customer_id' => $this->customer->id,
        'nx_employee_id' => $this->employee->id,
        'invoice_date' => now(),
        'due_date' => now()->addDays(7),
        'status' => 'sent',
        'subtotal' => 1000000,
        'discount' => 100000,
        'tax' => 10,
        'grand_total' => 990000,
    ]);

    $invoice->items()->create([
        'item_type' => 'product',
        'item_id' => $this->product->id,
        'item_code' => $this->product->product_code,
        'item_name' => $this->product->product_name,
        'qty' => 2,
        'unit_price' => 500000,
        'line_total' => 1000000,
    ]);

    expect($invoice->items()->count())->toBe(1);

    Livewire::test(EditInvoice::class, [
        'record' => $invoice->getRouteKey(),
    ])
        ->fillForm([
            'status' => 'paid',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $invoice->refresh();

    // Data items HARUS tetap ada dan tidak boleh terhapus
    expect($invoice->items()->count())->toBe(1)
        ->and($invoice->items()->onlyTrashed()->count())->toBe(0)
        ->and($invoice->status)->toBe('paid')
        ->and((float) $invoice->total_paid)->toBe(990000.00)
        ->and((float) $invoice->subtotal)->toBe(1000000.00)
        ->and((float) $invoice->grand_total)->toBe(990000.00);
});
