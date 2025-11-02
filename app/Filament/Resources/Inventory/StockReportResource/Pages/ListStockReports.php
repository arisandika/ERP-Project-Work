<?php

namespace App\Filament\Resources\Inventory\StockReportResource\Pages;

use App\Filament\Resources\Inventory\StockReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

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
                ->url(function () {
                    // Get filter values from current request or table state
                    $categoryId = request()->input('tableFilters.category_id');
                    
                    // Build URL with query parameters
                    $url = route('inventory.stock-report.download-pdf');
                    $params = [];
                    
                    if ($categoryId) {
                        $params['category_id'] = $categoryId;
                    }
                    
                    // Add current table filters to URL if available
                    $tableFilters = request()->get('tableFilters', []);
                    if (isset($tableFilters['category_id'])) {
                        $params['category_id'] = $tableFilters['category_id'];
                    }
                    
                    if (!empty($params)) {
                        $url .= '?' . http_build_query($params);
                    }
                    
                    return $url;
                })
                ->openUrlInNewTab(),
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
                ->join('nx_products', 'nx_product_stock.id_product', '=', 'nx_products.id_product')
                ->where('nx_product_stock.qty', '<=', 5)
                ->count();

            $outOfStockCount = \App\Models\Inventory\ProductStock::where('qty', '<=', 0)->count();

            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Laporan Stok Rendah')
                ->body("Total {$lowStockCount} items | Kritis {$criticalCount} items | Habis {$outOfStockCount} items")
                ->persistent()
                ->actions([
                    \Filament\Notifications\Actions\Action::make('filter')
                        ->label('Filter Stok Rendah')
                        ->button()
                        ->close(),
                ])
                ->send();
        }
    }
}

