<?php

namespace App\Filament\Resources\Inventory\StockReportResource\Pages;

use App\Filament\Resources\Inventory\StockReportResource;
use App\Models\Inventory\ProductStock;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListStockReports extends ListRecords
{
    protected static string $resource = StockReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('export_pdf')
                ->label('Unduh PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->url(route('inventory.stock-report.download-pdf'))
                ->openUrlInNewTab()
                ->tooltip('Unduh laporan stock product dalam format PDF'),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            // Pastikan relasi ini sudah ada di model Product
            ->with(['category', 'unit', 'productStocks']);
    }

    public function mount(): void
    {
        parent::mount();

        // REVISI ARSITEKTUR: Menggunakan 1 query agregasi (Single Trip to DB)
        $stockStats = ProductStock::selectRaw('
            SUM(CASE WHEN qty_available <= 10 THEN 1 ELSE 0 END) as low_stock,
            SUM(CASE WHEN qty_available <= 5 THEN 1 ELSE 0 END) as critical_stock,
            SUM(CASE WHEN qty_available <= 0 THEN 1 ELSE 0 END) as out_of_stock
        ')->first();

        // Ambil nilai dari hasil query agregasi (Cast ke Integer)
        $lowStockCount   = (int) ($stockStats->low_stock ?? 0);
        $criticalCount   = (int) ($stockStats->critical_stock ?? 0);
        $outOfStockCount = (int) ($stockStats->out_of_stock ?? 0);

        // Jika ada peringatan stok, tampilkan notifikasi
        if ($lowStockCount > 0) {
            Notification::make()
                ->warning()
                ->title('Peringatan: Stock Siap Jual Rendah')
                ->body("Terdapat **{$lowStockCount} item** stok rendah | **{$criticalCount} kritis** | **{$outOfStockCount} habis**")
                ->persistent()
                ->send();
        }
    }
}
