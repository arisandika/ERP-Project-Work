<?php

namespace App\Filament\Resources\Inventory\TransactionResource\Pages;

use App\Filament\Resources\Inventory\TransactionResource;
use App\Models\Inventory\Product;
use App\Models\Inventory\StockTransaction;
use App\Models\Inventory\SerialNumber;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Exception;
use Filament\Notifications\Notification;

class CreateTransaction extends CreateRecord
{
    protected static string $resource = TransactionResource::class;

    // Tampungan sementara untuk data SN
    public ?array $temporarySns = [];

    public function getTitle(): string
    {
        return 'Buat Mutasi Stock';
    }

    public function mount(): void
    {
        parent::mount();

        // Default awal saat halaman diload
        $this->form->fill([
            'mutation_type' => 'stock_in',
            'type' => 'masuk',
            'transaction_date' => now()->toDateString(),
            'transaction_code' => $this->generateTransactionCode('stock_in'),
        ]);

        if (request()->has(['product', 'warehouse'])) {
            $this->form->fill([
                'product_id' => (int) request('product'),
                'warehouse_id' => (int) request('warehouse'),
                'mutation_type' => 'stock_in',
                'type' => 'masuk',
                'transaction_date' => now()->toDateString(),
            ]);
        }
    }

    // === MENCEGAT DATA SEBELUM DISIMPAN KE TABEL STOCK TRANSACTIONS ===
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 1. Generate kode transaksi yang dinamis berdasarkan jenis mutasinya
        $data['transaction_code'] = $this->generateTransactionCode($data['mutation_type']);

        // 2. Ambil data SN dari form (jika ada), lalu hapus agar tidak bikin error tabel stock_transactions
        if (isset($data['scanned_sns'])) {
            $this->temporarySns = array_filter(array_map('trim', explode("\n", $data['scanned_sns'])));
            unset($data['scanned_sns']);
        }

        // 3. Hapus juga field hidden is_serialized
        if (isset($data['is_serialized'])) {
            unset($data['is_serialized']);
        }

        return $data;
    }

    // === MENYIMPAN ATAU MENGUPDATE SN KE TABEL nx_serial_number ===
    protected function afterCreate(): void
    {
        $transaction = $this->record;

        if (!empty($this->temporarySns)) {
            DB::transaction(function () use ($transaction) {
                $now = now();

                // SKENARIO 1: BARANG MASUK (Generate SN Baru)
                if (in_array($transaction->mutation_type, ['stock_in', 'cancel'])) {
                    $insertData = [];
                    foreach ($this->temporarySns as $sn) {
                        $insertData[] = [
                            'product_id'   => $transaction->product_id,
                            'warehouse_id' => $transaction->warehouse_id,
                            'serial_number'=> $sn,
                            'status'       => 'AVAILABLE',
                            'inbound_date' => $transaction->transaction_date ?? $now->toDateString(),
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ];
                    }
                    SerialNumber::insert($insertData);
                }

                // SKENARIO 2: BARANG KELUAR / DIKIRIM (Update SN Lama jadi Terjual/Keluar)
                else {
                    $updatedCount = SerialNumber::where('product_id', $transaction->product_id)
                        ->where('warehouse_id', $transaction->warehouse_id)
                        ->whereIn('serial_number', $this->temporarySns)
                        ->where('status', 'AVAILABLE') // Pastikan hanya update yang available
                        ->update([
                            'status' => 'SOLD_OR_OUT', // Sesuaikan dengan Enum di tabel Anda
                            'outbound_date' => $transaction->transaction_date ?? $now->toDateString(),
                            'updated_at' => $now
                        ]);

                    // Validasi Keamanan: Jika admin men-scan SN yang tidak ada di sistem
                    if ($updatedCount !== count($this->temporarySns)) {
                        DB::rollBack();
                        throw new Exception("Sebagian Serial Number yang di-scan tidak ditemukan di gudang ini, atau sudah berstatus terjual.");
                    }
                }
            });
        }
    }

    // Menangkap Exception dari Model Observer (Stok Minus) atau dari DB Transaction di atas
    protected function onValidationError(\Illuminate\Validation\ValidationException $exception): void
    {
        Notification::make()
            ->danger()
            ->title('Transaksi Gagal')
            ->body($exception->getMessage())
            ->send();
    }

    // === GENERATOR KODE DINAMIS ===
    private function generateTransactionCode(string $mutationType): string
    {
        $romanMonths = [
            'I', 'II', 'III', 'IV', 'V', 'VI',
            'VII', 'VIII', 'IX', 'X', 'XI', 'XII'
        ];

        $monthRoman = $romanMonths[now()->month - 1];
        $year = now()->year;
        $company = 'NEX';

        // Tentukan ST-IN atau ST-OUT berdasarkan jenis mutasinya
        $code = in_array($mutationType, ['stock_in', 'cancel']) ? 'ST-IN' : 'ST-OUT';

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
