<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\PromoCode;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreateSalesOrder extends CreateRecord
{
    protected static string $resource = SalesOrderResource::class;

    public function getTitle(): string
    {
        return 'Buat Pesanan';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'order_number'   => $this->generateOrderNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'order_date'     => now()->toDateString(),
        ]);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = $this->generateOrderNumber();
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record->status === 'confirmed') {
            try {
                DB::transaction(function () use ($record) {
                    $transactionCode = $this->generateNoTransactionOut();
                    $warehouseId = 1;

                    StockTransaction::$autoUpdateStock = false;

                    foreach ($record->items as $item) {
                        if ($item->item_type === 'product') {

                            // Lock stok untuk hindari race condition
                            $productStock = ProductStock::where('product_id', $item->item_id)
                                ->where('warehouse_id', $warehouseId)
                                ->lockForUpdate()
                                ->first();

                            if (!$productStock) {
                                $productStock = ProductStock::create([
                                    'product_id' => $item->item_id,
                                    'warehouse_id' => $warehouseId,
                                    'qty' => 0
                                ]);
                            }

                            // Validasi stok dalam transaction
                            if ($productStock->qty < $item->qty) {
                                $productName = $item->product->product_name ?? 'Product';
                                throw new \Exception(
                                    "Stok '{$productName}' tidak cukup. Sisa: {$productStock->qty}, diminta: {$item->qty}"
                                );
                            }

                            $stockBefore = $productStock->qty;
                            $productStock->decrement('qty', $item->qty);
                            $stockAfter = $stockBefore - $item->qty;

                            StockTransaction::create([
                                'transaction_code' => $transactionCode,
                                'transaction_date' => now(),
                                'product_id'       => $item->item_id,
                                'warehouse_id'     => $warehouseId,
                                'type'             => 'keluar',
                                'quantity'         => $item->qty,
                                'stock_before'     => $stockBefore,
                                'stock_after'      => $stockAfter,
                                'price'            => $item->unit_price,
                                'total_price'      => $item->line_total,
                                'reference_id'     => $record->id,
                                'reference_type'   => SalesOrder::class,
                                'no_reference'     => $record->order_number,
                                'notes'            => 'Penjualan SO: ' . $record->order_number,
                                'created_by'       => auth()->id(),
                            ]);
                        }
                    }

                    StockTransaction::$autoUpdateStock = true;

                    if ($record->promo_code_id) {
                        PromoCode::find($record->promo_code_id)?->increment('times_used');
                    }
                });

                Notification::make()
                    ->title('Pesanan Confirmed & Stok Berkurang')
                    ->success()
                    ->send();

            } catch (\Exception $e) {
                // Rollback otomatis oleh DB::transaction
                Notification::make()
                    ->title('Gagal: ' . $e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();

                // Hapus record yang gagal
                $record->delete();
                $this->halt();
            }

        } else {
            Notification::make()
                ->title('Pesanan Draft Tersimpan')
                ->body('Confirm pesanan untuk mengurangi stok.')
                ->info()
                ->send();
        }
    }

    private function generateOrderNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'SO';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = SalesOrder::withTrashed()
            ->where('order_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('order_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }

    private function generateNoTransactionOut(): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $monthRoman = $romanMonths[now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'ST-OUT';
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

    protected function getCreatedNotification(): ?Notification
    {
        return null;
    }
}
