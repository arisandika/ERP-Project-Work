<?php

namespace App\Filament\Resources\Sales\InvoiceResource\Pages;

use App\Filament\Resources\Sales\InvoiceResource;
use App\Models\Sales\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    /**
     * 1. FORM DEFAULTS
     * Mengisi nomor otomatis saat form pertama kali dibuka (hanya visual).
     */
    protected function getFormDefaults(): array
    {
        return [
            'invoice_number' => $this->generateInvoiceNumber(),
            'invoice_date'   => now(),
            'nx_employee_id' => auth()->user()?->employee?->id, // Asumsi relasi user ke employee ada
        ];
    }

    /**
     * 2. MUTATE BEFORE CREATE
     * Wajib generate ulang nomor sesaat sebelum simpan ke DB.
     * Ini mencegah error "Duplicate Entry" jika ada 2 admin input bersamaan.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['invoice_number'] = $this->generateInvoiceNumber();

        // Opsional: Jika butuh track user login yang buat
        // $data['created_by'] = auth()->id();

        return $data;
    }

    /**
     * 3. LOGIC GENERATOR (Format: 001/INV/NEX/XII/2025)
     */
    private function generateInvoiceNumber(): string
    {
        // Komponen Nomor
        $roman   = $this->getRomanMonth(now()->month);
        $year    = now()->year;
        $company = 'NEX'; // Kode Perusahaan
        $code    = 'INV'; // Kode Dokumen

        // Cari nomor terakhir dengan pola bulan & tahun INI
        // Contoh pola: %/INV/NEX/XII/2025
        $suffix = "/{$code}/{$company}/{$roman}/{$year}";

        $lastInvoice = Invoice::query()
            ->where('invoice_number', 'like', '%' . $suffix)
            ->orderByDesc('id')
            ->value('invoice_number');

        // Logic Urutan
        $seq = 1;
        if ($lastInvoice) {
            // Pecah string berdasarkan '/'
            // Contoh: "005/INV/NEX/XII/2025" -> ambil "005"
            $parts = explode('/', $lastInvoice);

            // Ambil bagian pertama, ubah jadi integer, tambah 1
            if (isset($parts[0]) && is_numeric($parts[0])) {
                $seq = (int) $parts[0] + 1;
            }
        }

        // Padding 3 digit (001, 002, dst)
        $seqStr = str_pad((string)$seq, 3, '0', STR_PAD_LEFT);

        // Gabungkan
        return "{$seqStr}{$suffix}";
    }

    /**
     * Helper ubah angka bulan ke Romawi
     */
    private function getRomanMonth(int $month): string
    {
        $map = [
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII'
        ];
        return $map[$month] ?? 'I';
    }

    protected function getRedirectUrl(): string
    {
        // Redirect ke halaman Index (List Table) setelah create sukses
        return $this->getResource()::getUrl('index');
    }
}
