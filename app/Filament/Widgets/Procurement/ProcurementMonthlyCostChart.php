<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProcurementMonthlyCostChart extends ChartWidget
{
    protected static ?string $heading = 'Pengeluaran PO per Bulan';
    protected static ?int $sort = 2; // Biar sebelahan persis sama Pie Chart

    protected function getData(): array
    {
        // Ambil data total PO per bulan di tahun ini
        $data = PurchaseOrder::select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(grand_total) as total')
            )
            ->whereYear('created_at', now()->year)
            ->whereNotIn('status', ['cancelled']) // Abaikan yang batal
            ->groupBy('month')
            ->pluck('total', 'month')
            ->toArray();

        $chartData = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agt', 'Sep', 'Okt', 'Nov', 'Des'];

        // Looping 12 bulan biar grafiknya full dari Jan - Des
        for ($i = 1; $i <= 12; $i++) {
            $chartData[] = $data[$i] ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Nilai PO (Rp)',
                    'data' => $chartData,
                    'backgroundColor' => '#3b82f6', // Warna biru yang nyambung sama tema
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
