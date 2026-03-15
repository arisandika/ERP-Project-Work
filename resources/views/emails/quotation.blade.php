<!DOCTYPE html>
<html>
<body>
    @php
        // Ambil klien dari Deal (Customer jika ada, jika tidak ambil Lead)
        $client = $quotation->deal?->customer ?? $quotation->deal?->lead;
        $clientName = $client?->name ?? 'Bapak/Ibu';
    @endphp
    <p>Halo {{ $clientName }},</p>
    <p>Berikut kami lampirkan penawaran dengan nomor {{ $quotation->quotation_number }}.</p>
    <p>Terima kasih.</p>
</body>
</html>
