<?php

namespace App\Filament\Resources\Inventory\TransactionResource\Pages;

use App\Filament\Resources\Inventory\TransactionResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\Warehouse;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    public function getTitle(): string
    {
        return 'Tambah Stock Product';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'type' => 'masuk',
            'transaction_date' => now()->toDateString(),
        ]);

        if (request()->has(['product', 'warehouse'])) {
            $this->form->fill([
                'product_id' => (int) request('product'),
                'warehouse_id' => (int) request('warehouse'),
                'type' => 'masuk',
                'transaction_date' => now()->toDateString(),
            ]);
        }
    }
}

