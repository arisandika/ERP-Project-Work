<?php

namespace App\Providers;

use App\Models\HR\LeaveRequest;
use App\Models\Finance\ReimbursementRequest;
use App\Models\Inventory\Rma;
use App\Models\Sales\Invoice;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Observers\InvoiceObserver;
use App\Observers\LeaveRequestObserver;
use App\Observers\ReimbursementRequestObserver;
use App\Observers\ProductStockObserver;
use App\Observers\DeliveryOrderObserver;
use App\Observers\RoleObserver;
use App\Observers\SalesOrderObserver;
use App\Observers\RmaObserver;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Http\Responses\LoginResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
    }
    public function boot(): void
    {
        App::setLocale('id');
        Carbon::setLocale('id');

        Relation::morphMap([
            'product' => Product::class,
        ]);

        FilamentColor::register([
            'primary' => Color::hex('#1c9cf0'),
            'info' => Color::hex('#1c9cf0'),
        ]);

        // Register Observers
        ProductStock::observe(ProductStockObserver::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
        DeliveryOrder::observe(DeliveryOrderObserver::class);
        SalesOrder::observe(SalesOrderObserver::class);
        ReimbursementRequest::observe(ReimbursementRequestObserver::class);
        Rma::observe(RmaObserver::class);

        Role::observe(RoleObserver::class);

        // Auto clear permission cache setiap ada perubahan role/permission
        Role::saved(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

        Role::deleted(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

        Permission::saved(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

        Permission::deleted(function () {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        });

        \Illuminate\Support\Facades\DB::listen(function ($query) {
            if (
                str_contains($query->sql, 'role_has_permissions') ||
                str_contains($query->sql, 'model_has_permissions') ||
                str_contains($query->sql, 'model_has_roles')
            ) {
                app()[PermissionRegistrar::class]->forgetCachedPermissions();
            }
        });
    }
}
