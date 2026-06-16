<?php

namespace Database\Seeders;

use App\Models\CRM\Customer;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\Office;
use App\Models\HR\Shift;
use App\Models\Inventory\Category;
use App\Models\Inventory\Product;
use App\Models\Inventory\Unit;
use App\Models\Inventory\Warehouse;
use App\Models\Procurement\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Categories
        $category = Category::firstOrCreate(
            ['name' => 'Smartphone & Accessories'],
            ['description' => 'Mobile phones, chargers, cases and accessories']
        );

        // 2. Units
        $unitPcs = Unit::firstOrCreate(
            ['symbol' => 'pcs'],
            ['name' => 'Pieces', 'description' => 'Individual items']
        );

        // 3. Warehouses
        $warehouse = Warehouse::firstOrCreate(
            ['warehouse_name' => 'Main Warehouse Jakarta'],
            [
                'location' => 'Jakarta Barat',
                'manager_name' => 'Andi Wijaya',
                'phone' => '081234567890',
                'is_active' => true
            ]
        );

        // 4. Products
        Product::firstOrCreate(
            ['product_name' => 'Redmi Note 13 Pro'],
            [
                'category_id' => $category->id,
                'unit_id' => $unitPcs->id,
                'min_stock' => 10,
                'price' => 3799000.00,
                'purchase_price' => 3100000.00,
                'selling_price' => 3799000.00,
                'is_serialized' => true,
                'is_web_published' => true
            ]
        );

        Product::firstOrCreate(
            ['product_name' => 'USB Type-C Fast Charger 33W'],
            [
                'category_id' => $category->id,
                'unit_id' => $unitPcs->id,
                'min_stock' => 20,
                'price' => 149000.00,
                'purchase_price' => 95000.00,
                'selling_price' => 149000.00,
                'is_serialized' => false,
                'is_web_published' => true
            ]
        );

        // 5. HR structures
        $department = Department::firstOrCreate(
            ['name' => 'Sales & Marketing'],
            ['description' => 'Handles deal acquisition, marketing banners and customer invoices']
        );

        $office = Office::firstOrCreate(
            ['name' => 'NEX Headquarters'],
            [
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'radius' => 100, // meters
                'address' => 'Sudirman Central Business District, Jakarta'
            ]
        );

        $shift = Shift::firstOrCreate(
            ['name' => 'Regular Shift (9 to 5)'],
            [
                'clock_in' => '09:00:00',
                'clock_out' => '17:00:00',
                'tolerance' => 15,
            ]
        );

        // 6. User and Employee
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@nexicon.co.id'],
            [
                'name' => 'Administrator Nexicon',
                'password' => Hash::make('password123'),
            ]
        );

        Employee::firstOrCreate(
            ['email' => 'admin@nexicon.co.id'],
            [
                'user_id' => $adminUser->id,
                'department_id' => $department->id,
                'office_id' => $office->id,
                'shift_id' => $shift->id,
                'full_name' => 'Administrator Nexicon',
                'identity_number' => '3171010101010001',
                'gender' => 'Male',
                'phone_number' => '081122334455',
                'position' => 'Super Admin',
                'status' => 'ACTIVE',
            ]
        );

        // 7. Customers
        Customer::firstOrCreate(
            ['email' => 'budi.santoso@gmail.com'],
            [
                'customer_code' => 'CUST-001',
                'name' => 'Budi Santoso',
                'phone' => '085711223344',
                'address' => 'Jl. Kemang Raya No. 10, Jakarta Selatan',
                'status' => 'active',
            ]
        );

        // 8. Suppliers
        Supplier::firstOrCreate(
            ['email' => 'sales@globaldistributor.com'],
            [
                'supplier_code' => 'SUP-NEX-001',
                'name' => 'PT. Global Distributor Indonesia',
                'category' => 'company',
                'contact_person' => 'Rian Hidayat',
                'phone' => '02155667788',
                'address' => 'Kawasan Industri Pulogadung, Jakarta Timur',
                'status' => 'active',
            ]
        );
    }
}
