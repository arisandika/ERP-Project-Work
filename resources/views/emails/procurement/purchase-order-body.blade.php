<x-mail::message>
# Purchase Order: {{ $purchaseOrder->po_number }}

Kepada Yth. {{ $purchaseOrder->supplier?->name ?? 'Supplier' }},

Bersama email ini, kami lampirkan dokumen Purchase Order (PO) dengan nomor **{{ $purchaseOrder->po_number }}**.

Mohon untuk memeriksa detail pesanan pada lampiran PDF. Jika ada pertanyaan atau ketidaksesuaian, silakan hubungi kami.

<x-mail::button :url="''">
Lihat Detail
</x-mail::button>

Terima kasih atas kerja samanya,<br>
{{ config('app.name') }}
</x-mail::message>
