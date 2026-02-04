<?php

namespace App\Filament\Resources\Inventory\TransactionResource\Pages;

use App\Filament\Resources\Inventory\TransactionResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockTransaction;
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
            'transaction_code' => $this->generateNoTransactionIn(),
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['transaction_code'] = $this->generateNoTransactionIn();

        return $data;
    }

    private function generateNoTransactionIn(): string
    {
        $romanMonths = [
            'I',
            'II',
            'III',
            'IV',
            'V',
            'VI',
            'VII',
            'VIII',
            'IX',
            'X',
            'XI',
            'XII'
        ];

        $monthRoman = $romanMonths[now()->month - 1];
        $year = now()->year;

        $company = 'NEX';
        $code = 'ST-IN';

        $prefixLike = "%/{$code}/{$company}/{$monthRoman}/{$year}";

        $last = StockTransaction::where('transaction_code', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('transaction_code');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$monthRoman}/{$year}";
    }
}

