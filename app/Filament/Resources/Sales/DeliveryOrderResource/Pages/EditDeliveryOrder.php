<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\SalesOrder;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
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
        $warehouseUtamaId = Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;

        return DB::transaction(function () use ($record, $data, $oldStatus, $newStatus, $warehouseUtamaId) {
            $record->update($data);

            if ($newStatus === 'on_delivery' && $oldStatus !== 'on_delivery') {
                StockTransaction::$autoUpdateStock = false;

                foreach ($record->items as $item) {
                    if ($item->item_type === 'product' && $item->qty > 0) {

                        $stockUtama = ProductStock::where('product_id', $item->item_id)
                            ->where('warehouse_id', $warehouseUtamaId)
                            ->lockForUpdate()
                            ->first();

                        if (!$stockUtama || $stockUtama->qty_reserved < $item->qty) {
                            throw new \Exception("Stok Reserved untuk {$item->item_name} tidak mencukupi di gudang!");
                        }

                        // A. PINDAH STOK DARI RESERVED KE ON DELIVERY
                        $stockReservedBefore = $stockUtama->qty_reserved;
                        $stockUtama->decrement('qty_reserved', $item->qty);
                        $stockUtama->increment('qty_on_delivery', $item->qty);

                        // B. CATAT HISTORY TRANSAKSI (KELUAR)
                        StockTransaction::create([
                            'transaction_code' => $this->generateTransactionCode(),
                            'transaction_date' => now(),
                            'product_id'       => $item->item_id,
                            'warehouse_id'     => $warehouseUtamaId,
                            'mutation_type'    => 'delivery',
                            'type'             => 'keluar',
                            'quantity'         => $item->qty,
                            'stock_before'     => $stockReservedBefore,
                            'stock_after'      => $stockReservedBefore - $item->qty,
                            'price'            => 0,
                            'total_price'      => 0,
                            'reference_id'     => $record->id,
                            'reference_type'   => DeliveryOrder::class,
                            'reference_number' => $record->do_number,
                            'notes'            => 'Pengiriman Fisik Keluar Gudang',
                            'created_by'       => auth()->id() ?? 1,
                        ]);
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
    protected function afterSave(): void
    {
        $record = $this->record;

        if ($record->status === 'on_delivery' && !empty($this->temporarySns)) {
            DB::transaction(function () use ($record) {
                foreach ($this->temporarySns as $productId => $sns) {
                    SerialNumber::whereIn('serial_number', $sns)
                        ->where('product_id', $productId)
                        ->update([
                            'status' => 'ON_DELIVERY',
                            // 'client_id' => $record->nx_customer_id // Opsional kalau ada relasi ke klien
                        ]);
                }
            });
        }
    }

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
