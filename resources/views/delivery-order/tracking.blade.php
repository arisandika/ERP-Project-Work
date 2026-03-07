<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi Surat Jalan - {{ $do->do_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-main-light min-h-screen pb-10">

    <!-- Header / Navbar -->
    <div class="bg-blue-900 text-white shadow-md">
        <div class="max-w-2xl mx-auto px-4 py-4 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold tracking-wider">NEXICON</h1>
                <p class="text-xs text-blue-200">PT. Next Generation Solutions</p>
            </div>
            <div class="text-right text-xs opacity-80">
                <p>Tracking Validasi</p>
                <p>Surat Jalan Resmi</p>
            </div>
        </div>
    </div>

    <!-- Container Utama -->
    <div class="max-w-2xl mx-auto px-4 mt-6">

        <!-- Flash Messages -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm" role="alert">
                <p class="font-bold">Berhasil</p>
                <p>{{ session('success') }}</p>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded shadow-sm" role="alert">
                <p class="font-bold">Oops!</p>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <!-- Card Informasi DO -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="border-b border-gray-100 bg-main-light px-5 py-4 flex justify-between items-center">
                <div>
                    <p class="text-xs text-gray-500 font-medium uppercase mb-1">No. Surat Jalan</p>
                    <h2 class="text-lg font-bold text-gray-800">{{ $do->do_number }}</h2>
                </div>

                <!-- Status Badge -->
                @php
                    $badgeConfig = match($do->status) {
                        'draft' => ['bg-gray-100', 'text-gray-700', 'Draft'],
                        'ready' => ['bg-yellow-100', 'text-yellow-700', 'Siap Kirim'],
                        'on_delivery' => ['bg-blue-100', 'text-blue-700', 'Dalam Pengiriman'],
                        'delivered' => ['bg-green-100', 'text-green-700', 'Telah Diterima'],
                        'cancelled' => ['bg-red-100', 'text-red-700', 'Dibatalkan'],
                        default => ['bg-gray-100', 'text-gray-700', 'Unknown'],
                    };
                @endphp
                <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $badgeConfig[0] }} {{ $badgeConfig[1] }}">
                    {{ $badgeConfig[2] }}
                </span>
            </div>

            <div class="p-5 grid grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-gray-500 mb-1">Tanggal Kirim</p>
                    <p class="font-medium text-black">{{ \Carbon\Carbon::parse($do->do_date)->format('d F Y') }}</p>
                </div>
                <div>
                    <p class="text-gray-500 mb-1">Pengirim / PIC</p>
                    <p class="font-medium text-black">{{ $do->employee->full_name ?? '-' }}</p>
                </div>
                <div class="col-span-2 mt-2">
                    <p class="text-gray-500 mb-1">Dikirim Kepada</p>
                    <div class="bg-main-light p-3 rounded border border-gray-100">
                        <p class="font-bold text-gray-800">{{ $do->customer->name ?? 'UMUM' }}</p>
                        <p class="text-gray-600 mt-1">{{ $do->customer->address ?? '-' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Daftar Barang -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="border-b border-gray-100 px-5 py-4">
                <h3 class="font-bold text-gray-800">Daftar Barang Dikirim</h3>
            </div>
            <div class="divide-y divide-gray-100">
                @foreach($do->items as $index => $item)
                <div class="p-4 flex items-start space-x-3">
                    <div class="bg-blue-50 text-blue-700 font-bold w-8 h-8 rounded flex items-center justify-center flex-shrink-0">
                        {{ $index + 1 }}
                    </div>
                    <div class="flex-1">
                        <h4 class="font-semibold text-gray-800">{{ $item->item_name }}</h4>
                        <p class="text-xs text-gray-500 mt-1">Kode: {{ $item->item_code ?? '-' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-gray-500 mb-1">Qty</p>
                        <p class="font-bold text-lg text-black">
                            {{ $item->qty }}
                            <span class="text-xs font-normal text-gray-500">{{ $item->uom ?? 'Pcs' }}</span>
                        </p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Section Konfirmasi Penerimaan -->
        @if(in_array($do->status, ['on_delivery', 'ready']))
            <div class="bg-white rounded-xl shadow-sm border border-blue-200 overflow-hidden mb-6">
                <div class="bg-blue-50 px-5 py-4 border-b border-blue-100">
                    <h3 class="font-bold text-blue-900">Konfirmasi Penerimaan</h3>
                    <p class="text-xs text-blue-700 mt-1">Apakah barang sudah Anda terima dengan baik?</p>
                </div>
                <div class="p-5">
                    <form action="{{ route('tracking.delivery-order.terima', $do->do_number) }}" method="POST">
                        @csrf
                        <div class="mb-4">
                            <label for="penerima" class="block text-sm font-medium text-gray-700 mb-2">
                                Nama Jelas Penerima
                            </label>
                            <input
                                type="text"
                                id="penerima"
                                name="penerima"
                                required
                                placeholder="Masukkan nama Anda..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                                autocomplete="off"
                            >
                            @error('penerima')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <button
                            type="submit"
                            onclick="return confirm('Apakah Anda yakin ingin mengkonfirmasi bahwa barang telah diterima?')"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg transition duration-200 shadow-md flex justify-center items-center"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M5 13l4 4L19 7"></path>
                            </svg>
                            Ya, Barang Telah Diterima
                        </button>
                    </form>
                </div>
            </div>
        @elseif($do->status == 'delivered')
            <div class="bg-green-50 rounded-xl border border-green-200 p-6 text-center mb-6">
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                         xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-green-900 mb-2">Pengiriman Selesai</h3>
                <p class="text-sm text-green-700 mb-4">Barang telah dikonfirmasi diterima.</p>

                @if($do->proof_notes)
                    <div class="bg-white/60 p-3 rounded text-left text-sm text-green-800">
                        <strong>Keterangan:</strong><br>
                        {{ $do->proof_notes }}
                    </div>
                @endif
            </div>
        @endif

        <div class="text-center pb-8">
            <p class="text-xs text-gray-400">
                Sistem Informasi ERP Terintegrasi &copy; {{ date('Y') }} Nexicon
            </p>
        </div>

    </div>

</body>
</html>
