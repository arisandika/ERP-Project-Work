<?php

namespace App\Filament\Resources\Sales\DeliveryOrderResource\Pages;

use App\Filament\Resources\Sales\DeliveryOrderResource;
use App\Models\Sales\DeliveryOrder;
use App\Models\Inventory\SerialNumber; // Wajib import untuk update SN
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB; // Wajib import DB facade

class CreateDeliveryOrder extends CreateRecord
{
    protected static string $resource = DeliveryOrderResource::class;

    // Tampungan sementara untuk Serial Number per produk
    public array $temporarySns = [];

    public function getTitle(): string
    {
        return 'Buat Surat Jalan';
    }

    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'do_number'      => $this->generateDeliveryNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'do_date'        => now()->toDateString(),
        ]);
    }

    // === MENCEGAT DATA SN DARI DALAM REPEATER ITEMS ===
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['do_number'] = $this->generateDeliveryNumber();

        // 1. Cek apakah ada barang yang dikirim
        if (isset($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                // 2. Jika item ini punya form scan SN yang diisi
                if (!empty($item['scanned_sns'])) {
                    // Pisahkan SN berdasarkan enter (baris baru)
                    $sns = array_filter(array_map('trim', explode("\n", $item['scanned_sns'])));

                    // Simpan sementara, kelompokkan berdasarkan ID Produk
                    $this->temporarySns[$item['item_id']] = $sns;

                    // Hapus field ini agar tidak bikin error SQL di tabel item surat jalan
                    unset($data['items'][$key]['scanned_sns']);
                }

                // Hapus juga flag bantuan ini
                if (isset($item['is_serialized'])) {
                    unset($data['items'][$key]['is_serialized']);
                }
            }
        }

        return $data;
    }

    // === UPDATE STATUS SN SETELAH SURAT JALAN BERHASIL DIBUAT ===
    protected function afterCreate(): void
    {
        $record = $this->record;

        // Jika ada SN yang ditangkap dari form tadi
        if (!empty($this->temporarySns)) {
            DB::transaction(function () use ($record) {
                foreach ($this->temporarySns as $productId => $sns) {
                    // Update status SN fisik menjadi Dalam Pengiriman (ON_DELIVERY)
                    // Dan ikatkan SN ini ke Customer yang membeli
                    SerialNumber::whereIn('serial_number', $sns)
                        ->where('product_id', $productId)
                        ->update([
                            'status'    => 'ON_DELIVERY',
                            'client_id' => $record->nx_customer_id,
                        ]);
                }
            });
        }
    }

    private function generateDeliveryNumber(): string
    {
        $roman = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'][now()->month - 1];
        $year = now()->year;
        $company = 'NEX';
        $code = 'DO';

        $prefixLike = "%/$code/$company/$roman/$year";

        $last = \App\Models\Sales\DeliveryOrder::withTrashed()
            ->where('do_number', 'like', $prefixLike)
            ->orderByDesc('id')
            ->value('do_number');

        $seq = 1;

        if ($last) {
            $parts = explode('/', $last);
            $seq = ((int) $parts[0]) + 1;
        }

        $seqStr = str_pad((string) $seq, 3, '0', STR_PAD_LEFT);

        return "{$seqStr}/{$code}/{$company}/{$roman}/{$year}";
    }
}
