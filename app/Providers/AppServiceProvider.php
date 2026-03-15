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
use App\Observers\SalesOrderObserver;
use App\Observers\RmaObserver;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
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
    }
}
