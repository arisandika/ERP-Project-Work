<?php

namespace App\Filament\Resources\Inventory\ProductResource\Pages;

use App\Filament\Resources\Inventory\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
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
                ->body("Terdapat {$lowStockCount} item dengan stok rendah. Gunakan filter untuk melihat detail.")
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
