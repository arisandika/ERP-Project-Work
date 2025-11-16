<?php

namespace App\Filament\Resources\Inventory\ProductResource\Pages;

use App\Filament\Resources\Inventory\ProductResource;
use Filament\Actions;
use App\Filament\Resources\Inventory\ProductResource\Widgets\ProductStatsOverview;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Produk'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductStatsOverview::class,
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
            \Filament\Notifications\Notification::make()
                ->warning()
                ->title('Peringatan Stok Rendah')
                ->body("Terdapat {$lowStockCount} item dengan stock rendah. Gunakan menu untuk melihat detail.")
                ->persistent()
                ->send();
        }
    }
}
