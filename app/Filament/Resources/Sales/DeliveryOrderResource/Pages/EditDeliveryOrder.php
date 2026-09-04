<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditDeliveryOrder extends EditRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    // Variabel penampung SN dari Form Repeater
    public array $temporarySns = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Proses Surat Jalan (Scan SN)';
    }

    // 1. TANGKAP HASIL SCAN SN SEBELUM DISAVE KE DB
    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (!empty($item['scanned_sns'])) {
                    // 1. Ambil data mentah (baris baru) ubah jadi array untuk logic update SN
                    $sns = array_filter(array_map('trim', explode("\n", $item['scanned_sns'])));
                    $this->temporarySns[$item['item_id']] = $sns;

                    // 2. Ubah array tadi jadi string dipisah koma untuk disimpan ke DB kolom baru kita
                    $data['items'][$key]['scanned_sns'] = implode(', ', $sns);
                }

                if (isset($item['is_serialized'])) {
                    unset($data['items'][$key]['is_serialized']);
                }
            }
        }
        return $data;
    }

    // 2. LOGIKA KETIKA STATUS BERUBAH JADI "ON DELIVERY"
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $newStatus = $data['status'];
        return DB::transaction(function () use ($record, $data, $oldStatus, $newStatus) {
            $record->update($data);

            if ($newStatus === 'on_delivery' && $oldStatus !== 'on_delivery') {
                StockTransaction::$autoUpdateStock = false;

                foreach ($record->items as $item) {
                    if ($item->item_type === 'product' && $item->qty > 0) {

                        $stocks = ProductStock::where('product_id', $item->item_id)
                            ->where('qty_reserved', '>', 0)
                            ->orderBy('warehouse_id')
                            ->lockForUpdate()
                            ->get();

                        if ($stocks->sum('qty_reserved') < $item->qty) {
                            throw new \Exception("Stok Reserved untuk {$item->item_name} tidak mencukupi di gudang!");
                        }

                        $remaining = (float) $item->qty;
                        foreach ($stocks as $stock) {
                            if ($remaining <= 0) {
                                break;
                            }

                            $qtyDelivered = min((float) $stock->qty_reserved, $remaining);
                            $stockReservedBefore = (float) $stock->qty_reserved;
                            $stock->decrement('qty_reserved', $qtyDelivered);
                            $stock->increment('sold_stock', $qtyDelivered);

                            StockTransaction::create([
                                'transaction_code' => $this->generateTransactionCode(),
                                'transaction_date' => now(),
                                'product_id'       => $item->item_id,
                                'warehouse_id'     => $stock->warehouse_id,
                                'mutation_type'    => 'delivery',
                                'type'             => 'keluar',
                                'quantity'         => $qtyDelivered,
                                'stock_before'     => $stockReservedBefore,
                                'stock_after'      => $stockReservedBefore - $qtyDelivered,
                                'price'            => 0,
                                'total_price'      => 0,
                                'reference_id'     => $record->id,
                                'reference_type'   => DeliveryOrder::class,
                                'reference_number' => $record->do_number,
                                'notes'            => 'Pengiriman Fisik Keluar Gudang',
                                'created_by'       => auth()->id() ?? 1,
                            ]);

                            $remaining -= $qtyDelivered;
                        }
                    }
                }
                StockTransaction::$autoUpdateStock = true;

                // Update Status SO
                if ($record->salesOrder) {
                    $record->salesOrder->update(['status' => 'shipped']);
                }
            }
            return $record;
        });
    }

    // 3. SETELAH DATA TERSIMPAN, UPDATE STATUS SERIAL NUMBER-NYA
    // === REVISI: SESUAIKAN DENGAN KONSTANTA STATE MACHINE ===
    protected function afterSave(): void
    {
        $record = $this->record;

        // JIKA STATUS SURAT JALAN BERUBAH JADI ON_DELIVERY ATAU DELIVERED
        if (in_array($record->status, ['on_delivery', 'delivered']) && !empty($this->temporarySns)) {

            DB::transaction(function () use ($record) {
                // Tentukan status SN berdasarkan status Surat Jalan
                $targetSnStatus = $record->status === 'delivered'
                    ? SerialNumber::STATUS_SOLD
                    : SerialNumber::STATUS_ON_DELIVERY;

                foreach ($this->temporarySns as $productId => $sns) {
                    SerialNumber::whereIn('serial_number', $sns)
                        ->where('product_id', $productId)
                        ->update([
                            'status' => $targetSnStatus,
                            'customer_id' => $record->nx_customer_id, // Lacak klien yang beli
                            'outbound_date' => now()->toDateString(), // Lacak tgl keluarnya
                        ]);
                }
            });
        }
    }
    // ========================================================

    private function generateTransactionCode(): string {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/ST-OUT/NEX/{$roman}/" . now()->year;
        $last = StockTransaction::where('transaction_code', 'like', $prefix)->orderByDesc('id')->value('transaction_code');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-OUT/NEX/{$roman}/" . now()->year;
    }

    protected function getSavedNotification(): ?Notification {
        return Notification::make()->success()->title('Surat Jalan Diproses!')->body('Barang berhasil dikeluarkan dan SN tercatat.');
    }
}
