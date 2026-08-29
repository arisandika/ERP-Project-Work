<?php

namespace App\Filament\Widgets\Inventory;

use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Widgets\Widget;

class InventoryOperationsWidget extends Widget
{
    protected static string $view = 'filament.widgets.inventory.inventory-operations-widget';

    protected int|string|array $columnSpan = [
        'default' => 12,
        'md' => 12,
    ];

    public function getData(): array
    {
        return [
            'receipts' => [
                'label' => 'Penerimaan Barang',
                'count' => StockTransaction::where('type', 'masuk')->whereDate('transaction_date', now())->count(),
                'icon' => 'heroicon-o-arrow-down-tray',
                'color' => 'text-primary-600',
                'bg' => 'bg-primary-50',
            ],
            'internal' => [
                'label' => 'Internal Transfer',
                'count' => StockTransaction::where('mutation_type', 'transfer')->count(),
                'icon' => 'heroicon-o-arrows-right-left',
                'color' => 'text-warning-600',
                'bg' => 'bg-warning-50',
            ],
            'delivery' => [
                'label' => 'Pengiriman (Delivery)',
                'count' => ProductStock::sum('qty_on_delivery'),
                'icon' => 'heroicon-o-truck',
                'color' => 'text-success-600',
                'bg' => 'bg-success-50',
            ],
            'low_stock' => [
                'label' => 'Stok Kritis',
                'count' => ProductStock::where('qty_available', '<=', 10)->distinct('product_id')->count(),
                'icon' => 'heroicon-o-exclamation-circle',
                'color' => 'text-danger-600',
                'bg' => 'bg-danger-50',
            ],
        ];
    }
}
