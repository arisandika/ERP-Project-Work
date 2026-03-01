<?php

namespace App\Filament\Resources\Sales\SalesOrderResource\Pages;

use App\Filament\Resources\Sales\SalesOrderResource;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Sales\DeliveryOrderItem;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\Warehouse;
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
            Actions\RestoreAction::make(),
        ];
    }

    public function getTitle(): string
    {
        return 'Edit Pesanan (Sales Order)';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $oldStatus = $record->status;
        $newStatus = $data['status'];
        $warehouseUtamaId = Warehouse::where('warehouse_name', 'Gudang Utama')->value('id') ?? 1;

        return DB::transaction(function () use ($record, $data, $oldStatus, $newStatus, $warehouseUtamaId) {
            $record->update($data);

            // LOGIKA KETIKA SO DI-CONFIRM
            if ($newStatus === 'confirmed' && $oldStatus !== 'confirmed') {
                StockTransaction::$autoUpdateStock = false; // Matikan observer bawaan

                foreach ($record->items as $item) {
                    if ($item->item_type === 'product') {
                        $stockUtama = ProductStock::where('product_id', $item->item_id)
                            ->where('warehouse_id', $warehouseUtamaId)
                            ->lockForUpdate() // Cegah race condition
                            ->first();

                        if (!$stockUtama || $stockUtama->qty_available < $item->qty) {
                            throw new \Exception("Stok Siap Jual untuk {$item->item_name} tidak mencukupi!");
                        }

                        // 1. PINDAHKAN STOK KE RESERVED
                        $stockAvailableBefore = $stockUtama->qty_available;
                        $stockUtama->decrement('qty_available', $item->qty);
                        $stockUtama->increment('qty_reserved', $item->qty);

                        // 2. CATAT HISTORY TRANSAKSI
                        StockTransaction::create([
                            'transaction_code' => $this->generateTransactionCode(),
                            'transaction_date' => now(),
                            'product_id'       => $item->item_id,
                            'warehouse_id'     => $warehouseUtamaId,
                            'mutation_type'    => 'reserve',
                            'type'             => 'keluar',
                            'quantity'         => $item->qty,
                            'stock_before'     => $stockAvailableBefore,
                            'stock_after'      => $stockAvailableBefore - $item->qty,
                            'price'            => $item->unit_price,
                            'total_price'      => $item->line_total,
                            'reference_id'     => $record->id,
                            'reference_type'   => SalesOrder::class,
                            'reference_number' => $record->order_number,
                            'notes'            => 'Booking Stok (SO Confirmed)',
                            'created_by'       => auth()->id() ?? 1,
                        ]);
                    }
                }
                StockTransaction::$autoUpdateStock = true;

                // 3. AUTO-CREATE SURAT JALAN (DO)
                if (!DeliveryOrder::where('nx_sales_order_id', $record->id)->where('status', '!=', 'cancelled')->exists()) {
                    $do = DeliveryOrder::create([
                        'nx_sales_order_id' => $record->id,
                        'nx_customer_id'    => $record->nx_customer_id,
                        'nx_employee_id'    => $record->nx_employee_id,
                        'do_number'         => $this->generateDeliveryNumber(),
                        'do_date'           => now(),
                        'status'            => 'draft',
                    ]);

                    foreach ($record->items as $item) {
                        DeliveryOrderItem::create([
                            'nx_delivery_order_id' => $do->id,
                            'item_type'            => $item->item_type,
                            'item_id'              => $item->item_id,
                            'item_code'            => $item->item_code,
                            'item_name'            => $item->item_name,
                            'qty_ordered'          => $item->qty,
                            'qty'                  => $item->qty, // Asumsi kirim semua sekaligus
                            'qty_remaining'        => 0,
                        ]);
                    }
                }
            }
            return $record;
        });
    }

    private function generateTransactionCode(): string {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/ST-RES/NEX/{$roman}/" . now()->year;
        $last = StockTransaction::where('transaction_code', 'like', $prefix)->orderByDesc('id')->value('transaction_code');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/ST-RES/NEX/{$roman}/" . now()->year;
    }

    private function generateDeliveryNumber(): string {
        $roman = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'][now()->month - 1];
        $prefix = "%/DO/NEX/{$roman}/" . now()->year;
        $last = DeliveryOrder::withTrashed()->where('do_number', 'like', $prefix)->orderByDesc('id')->value('do_number');
        $seq = $last ? ((int) explode('/', $last)[0]) + 1 : 1;
        return str_pad((string) $seq, 3, '0', STR_PAD_LEFT) . "/DO/NEX/{$roman}/" . now()->year;
    }

    protected function getSavedNotification(): ?Notification {
        return Notification::make()->success()->title('Sales Order diperbarui!')->body('Stok telah berhasil di-reserve jika status Confirmed.');
    }
}
