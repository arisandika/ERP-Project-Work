<?php
namespace App\Filament\Resources\Inventory\StockReportResource\Pages;

use App\Filament\Resources\Inventory\StockReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

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
                ->tooltip('Unduh laporan stok produk dalam format PDF'),
        ];
    }

    protected function getTableQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getTableQuery()
            ->with(['category', 'unit', 'productStocks']);
    }

    public function mount(): void
    {
        parent::mount();

        // Check for low stock items and show notification
        $lowStockCount = \App\Models\Inventory\ProductStock::where('qty', '<=', 10)->count();

        if ($lowStockCount > 0) {
            $criticalCount = \App\Models\Inventory\ProductStock::query()
                ->join('nx_products', 'nx_product_stock.product_id', '=', 'nx_products.id')
                ->where('nx_product_stock.qty', '<=', 5)
                ->count();

            $outOfStockCount = \App\Models\Inventory\ProductStock::where('qty', '<=', 0)->count();

            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Laporan Stok Rendah')
                ->body("Total {$lowStockCount} items | Kritis {$criticalCount} items | Habis {$outOfStockCount} items")
                ->persistent()
                ->send();
        }
    }
}
