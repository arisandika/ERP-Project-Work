<!DOCTYPE html>
<html>
<head>
  <title>Verifikasi Invoice - Nexicon</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">

    <!-- Header -->
    <div class="text-center mb-6">
      <h2 class="text-2xl font-bold text-gray-800 mb-2">Verifikasi Invoice</h2>
      <p class="text-gray-500 text-sm">
        Demi keamanan, masukkan <b>4 digit terakhir</b> nomor HP Anda untuk membuka dokumen.
      </p>
    </div>

    <!-- Info Invoice -->
    <div class="bg-blue-50 border border-blue-100 rounded-md p-3 mb-6 text-center">
      <span class="text-gray-500 text-xs uppercase tracking-wide">No. Invoice</span>
      <div class="font-bold text-xl text-blue-800">{{ $invoiceNumber }}</div>
    </div>

    <form method="POST" action="{{ route('invoice.verify.submit', ['number' => $invoiceNumber]) }}" class="space-y-6">
      @csrf

      <!-- Input 4 Digit -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2 text-center">
          4 Digit Terakhir No. HP
        </label>
        <input
          type="text"
          name="phone_last_4"
          value="{{ old('phone_last_4') }}"
          required
          maxlength="4"
          inputmode="numeric"
          pattern="\d{4}"
          oninput="this.value = this.value.replace(/[^0-9]/g, '')"
          class="w-full text-center text-2xl tracking-[0.5em] font-bold rounded-md border border-gray-300 px-4 py-3 focus:outline-none focus:ring-2 focus:ring-blue-500 placeholder-gray-300"
          placeholder="XXXX"
          autofocus
        />
        @error('phone_last_4')
          <p class="text-xs text-red-600 mt-2 text-center">{{ $message }}</p>
        @enderror
      </div>

      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-md transition duration-200 shadow-md"
      >
        Buka Dokumen
      </button>
    </form>

    <!-- Footer -->
    <p class="mt-8 text-xs text-gray-400 text-center leading-relaxed">
      PT. NEXT GENERATION SOLUTIONS<br>
      <a href="https://www.nexicon.id" class="hover:text-blue-500 transition">www.nexicon.id</a>
    </p>
  </div>
</body>
</html>
