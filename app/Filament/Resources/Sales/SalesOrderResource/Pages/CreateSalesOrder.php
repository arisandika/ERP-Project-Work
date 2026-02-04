<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\PromoCode;
use App\Models\Inventory\Product;
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

    // 1. VALIDASI STOK (Cek ke Gudang)
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['order_number'] = $this->generateOrderNumber();

        // Jika user langsung pilih status Confirmed saat buat
        if (($data['status'] ?? 'draft') === 'confirmed') {
            $items = $data['items'] ?? [];

            foreach ($items as $item) {
                if (($item['item_type'] ?? 'product') === 'product') {
                    $productId = $item['item_id'] ?? null;
                    $qtyOrder = (int) ($item['qty'] ?? 0);

                    // Default Gudang Utama (ID: 1)
                    $warehouseId = 1;

                    $stockGudang = ProductStock::where('product_id', $productId)
                        ->where('warehouse_id', $warehouseId)
                        ->value('qty') ?? 0;

                    if ($stockGudang < $qtyOrder) {
                        $productName = Product::find($productId)?->product_name ?? 'Produk';

                        Notification::make()
                            ->title('Gagal: Stok Gudang Tidak Cukup')
                            ->body("Stok '{$productName}' di Gudang Utama sisa: {$stockGudang}, diminta: {$qtyOrder}")
                            ->danger()
                            ->persistent()
                            ->send();

                        $this->halt(); // Stop proses save
                    }
                }
            }
        }

        return $data;
    }

    // 2. EKSEKUSI PENGURANGAN STOK & CATAT TRANSAKSI
    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        if ($record->status === 'confirmed') {
            DB::transaction(function () use ($record) {

                // GENERATE KODE TRANSAKSI SATU KALI UNTUK SEMUA ITEM
                $transactionCode = $this->generateNoTransactionOut();

                foreach ($record->items as $item) {
                    // Pastikan item adalah produk fisik
                    if ($item->item_type === 'product') {

                        $warehouseId = 1; // Default Gudang Utama

                        // A. Update Table ProductStock (Agar Laporan Stock Berkurang)
                        $productStock = ProductStock::firstOrCreate(
                            ['product_id' => $item->item_id, 'warehouse_id' => $warehouseId],
                            ['qty' => 0]
                        );

                        $stockBefore = $productStock->qty;
                        $qtyKeluar = $item->qty;

                        $productStock->decrement('qty', $qtyKeluar);

                        $stockAfter = $stockBefore - $qtyKeluar;

                        // B. Catat di StockTransaction
                        StockTransaction::create([
                            'transaction_code' => $transactionCode, // Pakai kode yang sama
                            'transaction_date' => now(),
                            'product_id'       => $item->item_id,
                            'warehouse_id'     => $warehouseId,
                            'type'             => 'keluar',
                            'quantity'         => $qtyKeluar,
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

                // Update penggunaan Promo Code
                if ($record->promo_code_id) {
                    $promo = PromoCode::find($record->promo_code_id);
                    if ($promo) {
                        $promo->increment('times_used');
                    }
                }
            });

            Notification::make()
                ->title('Pesanan Dibuat & Stok Gudang Berkurang')
                ->success()
                ->send();
        }
    }

    // --- Helper Functions ---

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
        $code = 'ST-OUT'; // Kode Transaksi Keluar

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
        // Disable default notification kalau confirmed, karena kita kirim custom
        if ($this->getRecord()->status === 'confirmed') {
            return null;
        }
        return parent::getCreatedNotification();
    }
}
