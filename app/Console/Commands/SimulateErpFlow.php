<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sales\SalesOrder;
use App\Models\Sales\DeliveryOrder;
use App\Models\Inventory\Product;
use App\Models\Inventory\ProductStock;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\SerialNumber;
use App\Models\Inventory\StockTransaction;
use App\Models\CRM\Customer;
use App\Models\HR\Employee;
use Illuminate\Support\Facades\DB;

class SimulateErpFlow extends Command
{
    protected $signature = 'erp:test-flow';
    protected $description = 'Simulasi End-to-End Flow ERP dengan Pencatatan History Transaksi & Serial Number';

    public function handle()
    {
        $this->info("🚀 Memulai ERP Test Suite...");

        $skenario = $this->choice(
            'Pilih skenario mana yang mau di-test?',
            [
                '1. Berhenti di Sales Order (Hanya Booking / Reserved)',
                '2. Sukses Total (Sampai Delivered / Diterima)'
            ]
        );

        DB::transaction(function () use ($skenario) {
            // ==========================================
            // SETUP DATA
            // ==========================================
            $this->warn("\n[SETUP] Mereset data Master & Stok...");

            $customer = Customer::first();
            $employee = Employee::first();
            $warehouse = Warehouse::firstOrCreate(['id' => 1], ['warehouse_name' => 'Gudang Utama']);
            $product = Product::where('product_code', 'BRG-000009')->first(); // Target CCTV

            if (!$product || !$customer) {
                $this->error("Data Produk CCTV atau Customer tidak ditemukan!");
                return;
            }

            // Reset Stok Fisik ke 10
            $stock = ProductStock::updateOrCreate(
                ['product_id' => $product->id, 'warehouse_id' => $warehouse->id],
                ['qty_available' => 10, 'qty_reserved' => 0, 'qty_on_delivery' => 0]
            );

            // Ambil 3 SN yang ada untuk disimulasikan sebagai barang yang dikirim
            $qtyOrder = 3;
            $snsToReset = SerialNumber::where('product_id', $product->id)->limit($qtyOrder)->get();

            foreach($snsToReset as $sn) {
                $sn->update(['status' => 'AVAILABLE']);
            }
            $this->line("✔️  Stok di-reset: Available=10, Reserved=0");

            // ==========================================
            // FASE 1: BIKIN SO CONFIRMED
            // ==========================================
            $this->warn("\n[FASE 1] Membuat Sales Order Confirmed...");
            $hargaSatuan = 1200000;

            $so = SalesOrder::create([
                'order_number'   => 'SO/TEST/' . rand(100, 999),
                'order_date'     => now(),
                'status'         => 'confirmed',
                'nx_customer_id' => $customer->id,
                'nx_employee_id' => $employee->id,
            ]);

            $so->items()->create([
                'item_type'  => 'product',
                'item_id'    => $product->id,
                'item_code'  => $product->product_code,
                'item_name'  => $product->product_name,
                'qty'        => $qtyOrder,
                'unit_price' => $hargaSatuan,
                'line_total' => $qtyOrder * $hargaSatuan
            ]);

            // [LOGIC RESERVE STOK ERP]
            $stockBefore = $stock->qty_available;
            $stock->decrement('qty_available', $qtyOrder);
            $stock->increment('qty_reserved', $qtyOrder);

            // [CATAT HISTORY TRANSAKSI: RESERVE]
            StockTransaction::create([
                'transaction_code' => 'ST-RES/TEST/' . rand(1000, 9999),
                'transaction_date' => now(),
                'product_id'       => $product->id,
                'warehouse_id'     => $warehouse->id,
                'mutation_type'    => 'reserve',
                'type'             => 'keluar',
                'quantity'         => $qtyOrder,
                'stock_before'     => $stockBefore,
                'stock_after'      => $stockBefore - $qtyOrder,
                'price'            => $hargaSatuan,
                'total_price'      => $qtyOrder * $hargaSatuan,
                'reference_id'     => $so->id,
                'reference_type'   => SalesOrder::class,
                'no_reference'     => $so->order_number,
                'notes'            => 'Booking Stok (SO Confirmed)',
                'created_by'       => 1,
            ]);

            $this->line("✔️  SO Dibuat. History STOCK KELUAR (RESERVE) berhasil dicatat.");

            if ($skenario === '1. Berhenti di Sales Order (Hanya Booking / Reserved)') {
                $this->info("\n🎯 Simulasi Selesai di Tahap Reserved.");
                return;
            }

            // ==========================================
            // FASE 2: BUAT DO & KIRIM BARANG
            // ==========================================
            $this->warn("\n[FASE 2] Membuat DO dan Barang Dikirim (On Delivery)...");

            $do = DeliveryOrder::create([
                'nx_sales_order_id' => $so->id,
                'nx_customer_id'    => $customer->id,
                'nx_employee_id'    => $employee->id,
                'do_number'         => 'DO/TEST/' . rand(100, 999),
                'do_date'           => now(),
                'status'            => 'on_delivery'
            ]);

            // Siapkan string Serial Number untuk disimpan ke tabel item (buat kebutuhan cetak PDF)
            $snString = $snsToReset->pluck('serial_number')->implode(', ');

            $do->items()->create([
                'item_type'     => 'product',
                'item_id'       => $product->id,
                'item_code'     => $product->product_code,
                'item_name'     => $product->product_name,
                'qty_ordered'   => $qtyOrder,
                'qty'           => $qtyOrder,
                'qty_remaining' => 0,
                'scanned_sns'   => $snString // <--- INI BIAR MUNCUL DI PDF/CETAKAN
            ]);

            // Update status fisik SN di database
            $snIds = $snsToReset->pluck('id');
            SerialNumber::whereIn('id', $snIds)->update(['status' => 'ON_DELIVERY']);

            // [LOGIC OUTBOUND STOK ERP]
            $stockReservedBefore = $stock->qty_reserved;
            $stock->decrement('qty_reserved', $qtyOrder);
            $stock->increment('qty_on_delivery', $qtyOrder); // Barang masuk ke truk
            $so->update(['status' => 'shipped']);

            // [CATAT HISTORY TRANSAKSI: OUTBOUND FISIK]
            StockTransaction::create([
                'transaction_code' => 'ST-OUT/TEST/' . rand(1000, 9999),
                'transaction_date' => now(),
                'product_id'       => $product->id,
                'warehouse_id'     => $warehouse->id,
                'mutation_type'    => 'delivery',
                'type'             => 'keluar',
                'quantity'         => $qtyOrder,
                'stock_before'     => $stockReservedBefore,
                'stock_after'      => $stockReservedBefore - $qtyOrder,
                'price'            => 0,
                'total_price'      => 0,
                'reference_id'     => $do->id,
                'reference_type'   => DeliveryOrder::class,
                'no_reference'     => $do->do_number,
                'notes'            => 'Pengiriman Fisik Keluar Gudang',
                'created_by'       => 1,
            ]);

            $this->line("✔️  Barang di Truk! History mutasi KELUAR (DELIVERY) berhasil dicatat.");
            $this->line("✔️  Kolom scanned_sns pada Item DO berhasil diisi: $snString");

            if ($skenario === '2. Sukses Total (Sampai Delivered / Diterima)') {
                $this->warn("\n[SKENARIO 2] Barang Diterima Klien (Delivered)...");

                $do->update(['status' => 'delivered']);
                $so->update(['status' => 'completed']);

                $stock->decrement('qty_on_delivery', $qtyOrder);
                SerialNumber::whereIn('id', $snIds)->update(['status' => 'SOLD']);

                $this->line("✔️  Aset sah milik klien. Status SN -> SOLD.");
            }
        });

        $this->info("\n✅ Simulasi Selesai! Buka Filament, cari DO terbaru, lalu klik Cetak.");
    }
}
