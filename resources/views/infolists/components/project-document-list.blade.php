<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {{-- $getState() mengambil array path file dari database --}}
        @foreach (\Illuminate\Support\Arr::wrap($getState()) as $filePath)
            @php
                // Dapatkan nama file asli dan ekstensi
                $filename = basename($filePath);
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                $url = \Illuminate\Support\Facades\Storage::disk('public')->url($filePath);

                // Tentukan icon berdasarkan ekstensi
                $icon = match ($extension) {
                    'pdf' => 'heroicon-o-document-text',
                    'doc', 'docx' => 'heroicon-o-document-text',
                    'xls', 'xlsx', 'csv' => 'heroicon-o-table-cells',
                    'jpg', 'jpeg', 'png', 'webp' => 'heroicon-o-photo',
                    'zip', 'rar' => 'heroicon-o-archive-box',
                    default => 'heroicon-o-document',
                };

                // Warna icon
                $iconColor = match ($extension) {
                    'pdf' => 'text-red-500',
                    'doc', 'docx' => 'text-blue-500',
                    'xls', 'xlsx', 'csv' => 'text-green-500',
                    'jpg', 'jpeg', 'png' => 'text-purple-500',
                    default => 'text-gray-500',
                };
            @endphp

            <a href="{{ $url }}" target="_blank"
                class="flex items-center p-3 space-x-3 transition border rounded-lg shadow-sm bg-main-light hover:bg-secondary-light dark:bg-main-dark dark:border-border-dark dark:hover:bg-secondary-light">
                <div class="flex-shrink-0">
                    <x-icon :name="$icon" class="w-8 h-8 {{ $iconColor }}" />
                </div>

                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate dark:text-gray-100">
                        {{ $filename }}
                    </p>
                    <p class="text-xs text-gray-500 truncate dark:text-gray-400">
                        {{ strtoupper($extension) }} File
                    </p>
                </div>

                <div>
                    <x-heroicon-m-arrow-down-tray class="w-4 h-4 text-gray-400" />
                </div>
            </a>
        @endforeach
    </div>

    @if(empty($getState()))
        <div class="text-sm text-gray-500">
            Tidak ada dokumen yang diunggah.
        </div>
    @endif
</x-dynamic-component>