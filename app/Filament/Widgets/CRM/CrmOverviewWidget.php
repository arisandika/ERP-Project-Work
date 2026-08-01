<?php
namespace App\Filament\Widgets\CRM;

use App\Models\CRM\Deal;
use App\Models\CRM\Lead;
use App\Models\Sales\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CrmOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalLeads        = Lead::count();
        $newLeadsThisMonth = Lead::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $openDeals     = Deal::where('status', Deal::STATUS_OPEN)->count();
        $pipelineValue = Deal::where('status', Deal::STATUS_OPEN)->sum('estimated_value');

        $wonDeals    = Deal::where('status', Deal::STATUS_CLOSED_WON)->count();
        $lostDeals   = Deal::where('status', Deal::STATUS_CLOSED_LOST)->count();
        $totalClosed = $wonDeals + $lostDeals;
        $winRate     = $totalClosed > 0 ? round(($wonDeals / $totalClosed) * 100, 1) : 0;

        $pendingQuotations = Quotation::whereIn('status', ['new', 'sent', 'negotiation'])->count();

        return [
            Stat::make('Total Lead', $totalLeads)
                ->description($newLeadsThisMonth . ' lead baru bulan ini')
                ->descriptionIcon('heroicon-m-funnel')
                ->color('primary')
                ->chart($this->getWeeklyLeadTrend()),

            Stat::make('Deal Aktif (Open)', $openDeals)
                ->description('Est. Value: IDR ' . number_format($pipelineValue, 0, ',', '.'))
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('Win Rate', $winRate . '%')
                ->description($wonDeals . ' Won / ' . $lostDeals . ' Lost')
                ->descriptionIcon('heroicon-m-trophy')
                ->color($winRate >= 50 ? 'success' : ($winRate >= 25 ? 'warning' : 'danger')),

            Stat::make('Penawaran Berjalan', $pendingQuotations)
                ->description('Belum accepted/rejected')
                ->descriptionIcon('heroicon-m-document-text')
                ->color($pendingQuotations > 0 ? 'warning' : 'success'),
        ];
    }

    protected function getWeeklyLeadTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $data[] = Lead::whereDate('created_at', now()->subDays($i))->count();
        }
        return $data;
    }
}
