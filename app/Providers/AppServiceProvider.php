<?php
namespace App\Providers;

use App\Http\Responses\LoginResponse;
use App\Models\Finance\ReimbursementRequest;
use App\Models\HR\LeaveRequest;
use App\Models\HR\SickRequest;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Observers\DeliveryOrderObserver;
use App\Observers\LeaveRequestObserver;
use App\Observers\ProductStockObserver;
use App\Observers\ReimbursementRequestObserver;
use App\Observers\RoleObserver;
use App\Observers\SalesOrderObserver;
use App\Observers\SickRequestObserver;
use App\Observers\StockTransactionObserver;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

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
            'info'    => Color::hex('#1c9cf0'),
        ]);

        // Register Observers
        ProductStock::observe(ProductStockObserver::class);
        LeaveRequest::observe(LeaveRequestObserver::class);
        SickRequest::observe(SickRequestObserver::class);

        DeliveryOrder::observe(DeliveryOrderObserver::class);
        SalesOrder::observe(SalesOrderObserver::class);
        ReimbursementRequest::observe(ReimbursementRequestObserver::class);
        StockTransaction::observe(StockTransactionObserver::class);

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

        Relation::morphMap([
            'employee'    => \App\Models\HR\Employee::class,
            'salesperson' => \App\Models\Sales\SalesPerson::class,
        ]);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            return $user->hasRole('super_admin') ? true : null;
        });
    }
}
