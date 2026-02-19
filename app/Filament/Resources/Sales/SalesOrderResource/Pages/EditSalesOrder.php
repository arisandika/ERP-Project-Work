<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\PromoCode;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditSalesOrder extends EditRecord
{
    protected static string $resource = SalesOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Pesanan';
    }

    // 1. VALIDASI STOK SEBELUM UPDATE
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();
        $newStatus = $data['status'];
        $oldStatus = $record->status;

        // Hanya validasi jika status berubah jadi Confirmed
        if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                if (($item['item_type'] ?? 'product') === 'product') {
                    $productId = $item['item_id'] ?? null;
                    $qtyOrder = (int) ($item['qty'] ?? 0);
                    $warehouseId = 1;

                    if (!$productId) continue;

                    $stockGudang = ProductStock::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->value('qty') ?? 0;

                    if ($stockGudang < $qtyOrder) {
                        $productName = Product::find($productId)?->product_name ?? 'Produk';

                        Notification::make()
                            ->title('Validasi Gagal')
                            ->body("Stok '{$productName}' tidak cukup. Sisa: {$stockGudang}")
                            ->danger()
                            ->persistent()
                            ->send();

                        $this->halt();
                    }
                }
            }
        }

        return $data;
    }

    // 2. HANDLE UPDATE STATUS & STOK
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $newStatus = $data['status'];

        return DB::transaction(function () use ($record, $data, $oldStatus, $newStatus) {
            $record->update($data);

            // KASUS A: Barang KELUAR (Draft → Confirmed)
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {

                $transactionCode = $this->generateNoTransactionOut();

                // Bypass auto-update stok (manual control)
                StockTransaction::$autoUpdateStock = false;

                foreach ($record->items as $item) {
                    if ($item->item_type === 'product') {
                        $warehouseId = 1;

                        $productStock = ProductStock::firstOrCreate(
                            ['product_id' => $item->item_id, 'warehouse_id' => $warehouseId],
                            ['qty' => 0]
                        );

                        $stockBefore = $productStock->qty;
                        $productStock->decrement('qty', $item->qty);
                        $stockAfter = $stockBefore - $item->qty;

                        // Catat Transaksi KELUAR (ST-OUT)
                        StockTransaction::create([
                            'transaction_code' => $transactionCode,
                            'transaction_date' => now(),
                            'product_id'       => $item->item_id,
                            'warehouse_id'     => $warehouseId,
                            'type'             => 'keluar', // ✅ KELUAR
                            'quantity'         => $item->qty,
                            'stock_before'     => $stockBefore,
                            'stock_after'      => $stockAfter,
                            'price'            => $item->unit_price,
                            'total_price'      => $item->line_total,
                            'reference_id'     => $record->id,
                            'reference_type'   => SalesOrder::class,
                            'no_reference'     => $record->order_number,
                            'notes'            => 'Terjual via SO: ' . $record->order_number,
                            'created_by'       => auth()->id(),
                        ]);
                    }
                }

                StockTransaction::$autoUpdateStock = true;

                if ($record->promo_code_id) {
                    PromoCode::find($record->promo_code_id)?->increment('times_used');
                }

                Notification::make()
                    ->title('Pesanan Confirmed & Stok Berkurang')
                    ->success()
                    ->send();
            }

            // KASUS B: Barang MASUK (Confirmed → Cancelled/Draft)
            elseif ($oldStatus === 'confirmed' && ($newStatus === 'cancelled' || $newStatus === 'draft')) {

                $transactionCode = $this->generateNoTransactionIn();

                // Bypass auto-update stok
                StockTransaction::$autoUpdateStock = false;

                foreach ($record->items as $item) {
                    if ($item->item_type === 'product') {
                        $warehouseId = 1;

                        $productStock = ProductStock::where('product_id', $item->item_id)
                            ->where('warehouse_id', $warehouseId)
                            ->first();

                        if ($productStock) {
                            $stockBefore = $productStock->qty;
                            $productStock->increment('qty', $item->qty);
                            $stockAfter = $stockBefore + $item->qty;

                            // Catat Transaksi MASUK (ST-IN)
                            StockTransaction::create([
                                'transaction_code' => $transactionCode,
                                'transaction_date' => now(),
                                'product_id'       => $item->item_id,
                                'warehouse_id'     => $warehouseId,
                                'type'             => 'masuk', // ✅ MASUK
                                'quantity'         => $item->qty,
                                'stock_before'     => $stockBefore,
                                'stock_after'      => $stockAfter,
                                'price'            => $item->unit_price,
                                'total_price'      => $item->line_total,
                                'reference_id'     => $record->id,
                                'reference_type'   => SalesOrder::class,
                                'no_reference'     => $record->order_number,
                                'notes'            => 'Restock (Cancel SO): ' . $record->order_number,
                                'created_by'       => auth()->id(),
                            ]);
                        }
                    }
                }

                StockTransaction::$autoUpdateStock = true;

                if ($record->promo_code_id) {
                    PromoCode::find($record->promo_code_id)?->decrement('times_used');
                }

                Notification::make()
                    ->title('Pesanan Dibatalkan: Stok Dikembalikan')
                    ->warning()
                    ->send();
            }

            return $record;
        });
    }

    // Generate No Transaksi KELUAR (ST-OUT)
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

    // Generate No Transaksi MASUK (ST-IN)
    private function generateNoTransactionIn(): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
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

    protected function getSavedNotification(): ?Notification
    {
        return parent::getSavedNotification();
    }
}
