<?php

namespace App\Filament\Widgets\Procurement;

use App\Enums\Procurement\PurchaseInvoiceStatus;
use App\Enums\Procurement\PurchaseOrderStatus;
use App\Models\Procurement\PurchaseInvoice;
use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseRequisition;
use App\Models\Procurement\Supplier;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ProcurementStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 12;

    protected function getStats(): array
    {
        $supplierId = $this->filters['supplier_id'] ?? null;

        $cacheSuffix = $supplierId
            ? "_supplier_{$supplierId}"
            : '';

        $pendingPRCount = Cache::remember(
            "dash_pr_pending{$cacheSuffix}",
            300,
            fn () => PurchaseRequisition::query()
                ->where('status', PurchaseRequisition::STATUS_PENDING)
                ->count()
        );

        $pendingDeliveryPOCount = Cache::remember(
            "dash_po_pending{$cacheSuffix}",
            300,
            fn () => PurchaseOrder::query()
                ->whereNotIn('status', [
                    PurchaseOrderStatus::COMPLETED,
                    PurchaseOrderStatus::CANCELLED,
                ])
                ->when(
                    $supplierId,
                    fn ($q) => $q->where('supplier_id', $supplierId)
                )
                ->count()
        );

        $outstandingPayable = Cache::remember(
            "dash_pi_outstanding{$cacheSuffix}",
            600,
            fn () => PurchaseInvoice::query()
                ->whereIn('status', [
                    PurchaseInvoiceStatus::UNPAID,
                    PurchaseInvoiceStatus::PARTIAL,
                ])
                ->when(
                    $supplierId,
                    fn ($q) => $q->where('supplier_id', $supplierId)
                )
                ->sum(DB::raw('grand_total - total_paid'))
        );

        $activeSuppliers = Cache::remember(
            'dash_suppliers_active',
            86400,
            fn () => Supplier::query()
                ->where('status', 'active')
                ->count()
        );

        return [

            Stat::make('Pending Approval', $pendingPRCount)
                ->description(
                    $pendingPRCount > 0
                        ? "{$pendingPRCount} PR membutuhkan approval"
                        : 'Tidak ada antrean approval'
                )
                ->descriptionIcon(
                    $pendingPRCount > 0
                        ? 'heroicon-m-clock'
                        : 'heroicon-m-check-circle'
                )
                ->chart([12, 10, 8, 14, 6, 4, $pendingPRCount])
                ->color(match (true) {
                    $pendingPRCount > 15 => 'danger',
                    $pendingPRCount > 5 => 'warning',
                    default => 'success',
                }),

            Stat::make('Open Purchase Orders', $pendingDeliveryPOCount)
                ->description('Menunggu proses delivery / GR')
                ->descriptionIcon('heroicon-m-truck')
                ->chart([5, 7, 9, 11, 10, 8, $pendingDeliveryPOCount])
                ->color('warning'),

            Stat::make(
                'Outstanding Payable',
                'Rp ' . number_format($outstandingPayable, 0, ',', '.')
            )
                ->description('Invoice supplier belum lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([18, 20, 16, 14, 19, 17, 15])
                ->color('danger'),

            Stat::make('Active Suppliers', $activeSuppliers)
                ->description('Supplier aktif siap transaksi')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->chart([4, 6, 8, 10, 12, 14, $activeSuppliers])
                ->color('info'),
        ];
    }
}
