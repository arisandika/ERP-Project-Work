<?php

namespace App\Providers;

use App\Models\HR\LeaveRequest;
use App\Observers\LeaveRequestObserver;
use Illuminate\Support\ServiceProvider;
use App\Models\Inventory\ProductStock;
use App\Observers\ProductStockObserver;
use App\Models\Sales\DeliveryOrder;
use App\Observers\DeliveryOrderObserver;

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
        // Register ProductStock Observer untuk auto-check low stock
        ProductStock::observe(ProductStockObserver::class);

        LeaveRequest::observe(LeaveRequestObserver::class);

        // Register Observer untuk DeliveryOrder
        DeliveryOrder::observe(DeliveryOrderObserver::class);
    }
}
