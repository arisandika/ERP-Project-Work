<div class="mb-4 text-gray-700">
    <p>Kepada Yth. Pelanggan Yang Terhormat,</p>

    <p class="mt-2">Kami memberi tahu bahwa status permohonan retur <strong>{{ $rma_number }}</strong> telah diperbarui.</p>

    <table class="my-4 text-sm">
        <tr>
            <td class="pr-4 py-1 font-medium">No. RMA</td>
            <td class="py-1">{{ $rma_number }}</td>
        </tr>
        <tr>
            <td class="pr-4 py-1 font-medium">Status Sekarang</td>
            <td class="py-1">{{ $statusLabel }}</td>
        </tr>
        @if($resolution)
        <tr>
            <td class="pr-4 py-1 font-medium">Penyelesaian</td>
            <td class="py-1">{{ ucfirst($resolution) }}</td>
        </tr>
        @endif
    </table>

    @if($notes)
    <p class="mt-3 text-sm"><strong>Catatan:</strong> {{ $notes }}</p>
    @endif

    <p class="mt-4">Silakan <a href="{{ $loginUrl }}" class="text-blue-600 underline">masuk ke Customer Portal</a> untuk melihat detail lengkap dan riwayat retur Anda.</p>

    <p class="mt-6">Hormat kami,<br><strong>Nexicon ERP</strong></p>
</div>
