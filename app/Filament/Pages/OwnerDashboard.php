<?php
namespace App\Filament\Pages;

use App\Filament\Widgets\LowStockAlert;
use App\Filament\Widgets\LowStockStatsOverview;
use App\Filament\Widgets\Owner\AttendanceClockInHourChart;
use App\Filament\Widgets\Owner\AttendanceMissingClockOutTrend;
use App\Filament\Widgets\Owner\AttendanceStatusDonutChart;
use App\Filament\Widgets\Owner\AttendanceTrendChart;
use App\Filament\Widgets\Owner\HrKpiStats;
use App\Filament\Widgets\Owner\InvoiceStatusDonutChart;
use App\Filament\Widgets\Owner\RevenueSalesVsNonSalesChart;
use App\Filament\Widgets\Owner\SalesKpiStats;
use App\Filament\Widgets\Owner\SalesOrderVsInvoiceChart;
use App\Filament\Widgets\Owner\SalesRevenueMonthlyChart;
use App\Filament\Widgets\Owner\TopSalesPersonChart;
use App\Filament\Widgets\Sales\SalesPipelineChart;
use App\Filament\Widgets\Sales\SalesSummaryStats;
use Filament\Pages\Page;

class OwnerDashboard extends Page
{
    protected static string $view = 'filament.pages.dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -2;

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            // HR KPI
            HrKpiStats::class,
            AttendanceTrendChart::class,
            AttendanceStatusDonutChart::class,
            AttendanceClockInHourChart::class,
            AttendanceMissingClockOutTrend::class,

            // SALES KPI
            SalesKpiStats::class,
            SalesSummaryStats::class,
            SalesPipelineChart::class,
            SalesRevenueMonthlyChart::class,
            SalesOrderVsInvoiceChart::class,
            TopSalesPersonChart::class,
            InvoiceStatusDonutChart::class,
            RevenueSalesVsNonSalesChart::class,

            // Tabel
            // SalesOrderReportTable::class,
            // QuotationReportTable::class,
            // InvoiceReportTable::class,
            // DeliveryOrderReportTable::class,

            // INVENTORY KPI
            LowStockAlert::class,
            LowStockStatsOverview::class,
        ];
    }

}
