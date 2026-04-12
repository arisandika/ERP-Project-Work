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
            'do_number'      => DeliveryOrder::generateDoNumber(),
            'nx_employee_id' => auth()->user()?->employee?->id,
            'do_date'        => now()->toDateString(),
        ]);
    }

    // === MENCEGAT DATA SN DARI DALAM REPEATER ITEMS ===
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['do_number'] = DeliveryOrder::generateDoNumber();

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
                            'customer_id' => $record->nx_customer_id,
                        ]);
                }
            });
        }
    }
}
