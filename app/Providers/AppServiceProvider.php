<?php

namespace App\Providers;

use App\Models\HR\LeaveRequest;
use App\Models\Finance\ReimbursementRequest;
use App\Models\Sales\Invoice;
use App\Observers\InvoiceObserver;
use App\Observers\LeaveRequestObserver;
use App\Observers\ReimbursementRequestObserver;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use App\Models\Inventory\ProductStock;
use App\Observers\ProductStockObserver;
use App\Models\Sales\DeliveryOrder;
use App\Observers\DeliveryOrderObserver;
use App\Models\Sales\SalesOrder;
use App\Observers\SalesOrderObserver;
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Models\Inventory\Product;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
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

        // Register ProductStock Observer untuk auto-check low stock
        ProductStock::observe(ProductStockObserver::class);

        LeaveRequest::observe(LeaveRequestObserver::class);

        // Register Observer untuk DeliveryOrder
        DeliveryOrder::observe(DeliveryOrderObserver::class);

        // Register Observer untuk SalesOrder
        SalesOrder::observe(SalesOrderObserver::class);

        ReimbursementRequest::observe(ReimbursementRequestObserver::class);
    }
}
