<!DOCTYPE html>
<html>
<head>
  <title>Verifikasi Invoice - Nexicon</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen p-4">
  <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
    <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">Verifikasi Invoice</h2>
    <p class="text-gray-500 mb-6 text-center">
      Masukkan email & no HP customer yang terdaftar untuk melihat detail invoice.
    </p>

    <div class="mb-4 text-sm text-gray-600">
      <div class="flex justify-between">
        <span>No. Invoice:</span>
        <span class="font-semibold">{{ $invoiceNumber }}</span>
      </div>
    </div>

    <form method="POST" action="{{ route('invoice.verify.submit', ['number' => $invoiceNumber]) }}" class="space-y-4">
      @csrf

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
        <input
          type="email"
          name="email"
          value="{{ old('email') }}"
          required
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
          placeholder="nama@company.com"
        />
        @error('email')
          <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
        <input
          type="text"
          name="phone"
          value="{{ old('phone') }}"
          required
          class="w-full rounded-md border border-gray-300 px-3 py-2 focus:outline-none focus:ring focus:ring-blue-200"
          placeholder="08xxxxxxxxxx"
        />
        @error('phone')
          <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <button
        type="submit"
        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 rounded-md"
      >
        Verifikasi & Lihat Invoice
      </button>
    </form>

    <p class="mt-6 text-xs text-gray-400 text-center">
      PT. NEXT GENERATION SOLUTIONS<br>
      www.nexicon.id
    </p>
  </div>
</body>
</html>
