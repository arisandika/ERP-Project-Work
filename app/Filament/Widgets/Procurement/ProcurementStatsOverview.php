<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use App\Models\Procurement\PurchaseInvoice;
use App\Enums\Procurement\PurchaseInvoiceStatus;
use App\Enums\Procurement\PurchaseOrderStatus;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Number;
use Illuminate\Database\Eloquent\Builder;

class ProcurementStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $startDate = $this->filters['startDate'] ?? null;
        $endDate = $this->filters['endDate'] ?? null;
        $supplierId = $this->filters['supplier_id'] ?? null;

        // Base Query
        $poQuery = PurchaseOrder::query()
            ->when($startDate, fn (Builder $q) => $q->whereDate('order_date', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->whereDate('order_date', '<=', $endDate))
            ->when($supplierId, fn (Builder $q) => $q->where('supplier_id', $supplierId));

        $invoiceQuery = PurchaseInvoice::query()
            ->when($startDate, fn (Builder $q) => $q->whereDate('invoice_date', '>=', $startDate))
            ->when($endDate, fn (Builder $q) => $q->whereDate('invoice_date', '<=', $endDate))
            ->when($supplierId, fn (Builder $q) => $q->where('supplier_id', $supplierId));

        // Metrik
        $totalPo = (clone $poQuery)->count();
        $completedPo = (clone $poQuery)->where('status', PurchaseOrderStatus::COMPLETED)->count();

        $unpaidAmount = (clone $invoiceQuery)->whereIn('status', [
            PurchaseInvoiceStatus::UNPAID, // Pastikan Enum ini sesuai
            PurchaseInvoiceStatus::PARTIAL
        ])->sum('grand_total');

        return [
            Stat::make('Total Pesanan (PO)', $totalPo)
                ->description('Total PO yang diterbitkan')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 4, 6, 10, 14, 7, 12])
                ->color('primary'),

            Stat::make('Tingkat Penyelesaian', $totalPo > 0 ? number_format(($completedPo / $totalPo) * 100, 1) . '%' : '0%')
                ->description('Rasio PO yang selesai')
                ->descriptionIcon('heroicon-m-check-badge')
                ->chart([2, 5, 4, 8, 9, 12, 15])
                ->color('success'),

            Stat::make('Total Liabilitas (Hutang)', Number::currency($unpaidAmount, 'IDR', 'id'))
                ->description('Nilai Invoice Belum Lunas')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('danger'),
        ];
    }
}
