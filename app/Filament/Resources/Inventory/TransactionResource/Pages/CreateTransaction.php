<?php

namespace App\Filament\Resources\Inventory\TransactionResource\Pages;

use App\Filament\Resources\Inventory\TransactionResource;
use App\Models\Inventory\Warehouse;
use Filament\Resources\Pages\CreateRecord;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    public function getTitle(): string
    {
        return 'Tambah Stock Produk';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set default warehouse jika belum ada
        if (empty($data['warehouse_id'])) {
            $defaultWarehouse = Warehouse::first();
            
            if (!$defaultWarehouse) {
                // Create default warehouse if not exists
                $defaultWarehouse = Warehouse::create([
                    'warehouse_name' => 'Gudang Utama',
                    'location' => 'Lokasi Utama',
                ]);
            }
            
            $data['warehouse_id'] = $defaultWarehouse->id;
        }

        return $data;
    }
}

