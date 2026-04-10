<?php

namespace App\Filament\Widgets\Procurement;

use App\Models\Procurement\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProcurementPoStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Monitoring Status PO';
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Ambil jumlah PO berdasarkan statusnya
        $statuses = PurchaseOrder::select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        // Siapkan kerangka data (default 0 jika tidak ada)
        $data = [
            $statuses['draft'] ?? 0,
            $statuses['sent'] ?? 0,
            $statuses['partial'] ?? 0,
            $statuses['completed'] ?? 0,
            $statuses['cancelled'] ?? 0,
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total PO',
                    'data' => $data,
                    'backgroundColor' => [
                        '#9ca3af', // gray untuk draft
                        '#f59e0b', // warning untuk sent
                        '#3b82f6', // info untuk partial
                        '#10b981', // success untuk completed
                        '#ef4444', // danger untuk cancelled
                    ],
                ],
            ],
            'labels' => ['Draft', 'Dikirim ke Supplier', 'Diterima Sebagian', 'Selesai (Masuk Gudang)', 'Dibatalkan'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Tampilan donat lebih elegan dari pie biasa
    }
}
