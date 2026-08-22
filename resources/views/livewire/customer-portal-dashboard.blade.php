<div class="min-h-screen transition-colors duration-300 bg-main-light dark:bg-main-dark">

    <!-- Flash Messages -->
    @if (session('message'))
        <div class="fixed z-50 px-4 py-3 text-blue-600 bg-blue-100 rounded-md shadow-2xl top-20 right-4" id="success-message">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="fixed z-50 px-4 py-3 text-red-600 bg-red-100 rounded-md shadow-2xl top-20 right-4" id="error-message">
            <div class="flex items-center space-x-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- NAVBAR -->
    <nav class="sticky top-0 z-40 w-full border-b backdrop-blur-md bg-secondary-light dark:bg-secondary-dark border-border-light dark:border-border-dark">
        <div class="px-4 mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Left: Branding -->
                <div>
                    <div style="height: 1.5rem;" class="flex fi-logo">
                        <div class="flex items-center">
                            <img src="{{ asset('assets/logo-dark.webp') }}" alt="Logo" class="h-8 dark:hidden">
                            <img src="{{ asset('assets/logo-light.webp') }}" alt="Logo" class="hidden h-8 dark:block">
                        </div>
                    </div>
                </div>

                <!-- Right: Actions -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <!-- Theme Toggle -->
                    <button @click="toggleTheme()"
                        class="p-2 text-gray-600 transition-colors duration-200 rounded-full hover:bg-main-light dark:text-gray-400 dark:hover:bg-main-dark focus:outline-none focus:ring-2 focus:ring-border-light dark:focus:ring-border-dark">
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z">
                            </path>
                        </svg>
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                            </path>
                        </svg>
                    </button>

                    <div class="h-6 mx-1 border-l border-border-light dark:border-border-dark"></div>

                    <!-- Logout -->
                    <button wire:click="logout"
                        class="flex items-center px-3 py-2 space-x-1 text-sm font-medium text-white transition-colors duration-200 bg-red-600 rounded-lg shadow-sm hover:bg-red-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                            </path>
                        </svg>
                        <span class="hidden sm:inline">Keluar</span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="px-4 py-6 mx-auto md:py-0 max-w-7xl lg:px-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center sm:py-6">
            <div class="flex-1 min-w-0">
                <p class="mb-1 text-sm font-semibold text-gray-600 uppercase dark:text-gray-400">Customer Portal</p>
                <h1 class="text-2xl font-bold text-black sm:text-3xl dark:text-white">Halo, {{ $customer->name }}</h1>
            </div>
            <a href="{{ route('customer-portal.return.create') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold text-white transition-colors rounded-full shadow-sm bg-main-primary hover:bg-main-primary/90">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                Ajukan Return
            </a>
        </div>
    </div>

    <div class="px-4 pb-8 mx-auto space-y-6 max-w-7xl lg:px-8">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-4">

            <div class="flex items-center w-full p-6 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-2xl dark:bg-blue-900/20">
                        <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Pengajuan</p>
                        <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['total'] }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center w-full p-6 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-2xl dark:bg-yellow-900/20">
                        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Sedang Diproses</p>
                        <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['in_progress'] }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center w-full p-6 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-2xl dark:bg-green-900/20">
                        <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Selesai</p>
                        <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['completed'] }}</p>
                    </div>
                </div>
            </div>

            <div class="flex items-center w-full p-6 transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-2xl dark:bg-purple-900/20">
                        <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Produk Terdaftar</p>
                        <p class="text-2xl font-bold text-black dark:text-white">{{ $stats['total_products'] }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="overflow-hidden transition-colors shadow-sm ring-1 ring-border-light bg-secondary-light rounded-2xl dark:bg-secondary-dark dark:ring-border-dark">
            <div class="p-4 border-b lg:p-6 border-border-light dark:border-border-dark">
                <h3 class="text-lg font-semibold text-black dark:text-white">Riwayat Pengajuan Return</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Semua pengajuan return yang pernah Anda buat</p>
            </div>

            <!-- Desktop Table -->
            <div class="overflow-x-auto">
                <table class="hidden min-w-full divide-y divide-border-light sm:table dark:divide-border-dark">
                    <thead class="bg-accent-light dark:bg-accent-dark">
                        <tr>
                            <th class="p-4 text-sm font-semibold text-left text-main-dark lg:px-6 dark:text-main-light">No. RMA</th>
                            <th class="p-4 text-sm font-semibold text-left text-main-dark lg:px-6 dark:text-main-light">Produk</th>
                            <th class="p-4 text-sm font-semibold text-left text-main-dark lg:px-6 dark:text-main-light">Kendala</th>
                            <th class="p-4 text-sm font-semibold text-left text-main-dark lg:px-6 dark:text-main-light">Tanggal</th>
                            <th class="p-4 text-sm font-semibold text-left text-main-dark lg:px-6 dark:text-main-light">Status</th>
                        </tr>
                    </thead>
                    <tbody class="transition-colors duration-150 divide-y divide-border-light bg-main-light dark:bg-main-dark dark:divide-border-dark">
                        @forelse($returns as $return)
                            @php $badge = $this->getStatusBadgeColor($return->status); @endphp
                            <tr class="hover:bg-secondary-light dark:hover:bg-secondary-dark">
                                <td class="p-4 text-sm font-medium text-black whitespace-nowrap lg:px-6 dark:text-white">
                                    {{ $return->rma_number }}
                                </td>
                                <td class="p-4 text-sm lg:px-6">
                                    <div class="font-medium text-black dark:text-white">{{ $return->serialNumber?->product?->name ?? '-' }}</div>
                                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">SN: {{ $return->serialNumber?->serial_number ?? '-' }}</div>
                                </td>
                                <td class="max-w-xs p-4 text-sm text-gray-600 lg:px-6 dark:text-gray-300">
                                    <p class="truncate">{{ Str::limit($return->issue_description, 60) }}</p>
                                </td>
                                <td class="p-4 text-sm text-gray-600 whitespace-nowrap lg:px-6 dark:text-gray-300">
                                    {{ $return->created_at->format('d M Y') }}
                                </td>
                                <td class="p-4 whitespace-nowrap lg:px-6">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium {{ $badge['bg'] }} {{ $badge['text'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $badge['dot'] }}"></span>
                                        {{ $statusLabels[$return->status] ?? $return->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="p-10 text-center">
                                    <svg class="w-10 h-10 mx-auto mb-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                        </path>
                                    </svg>
                                    <p class="text-lg font-semibold text-black dark:text-white">Belum ada pengajuan return</p>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Pengajuan return Anda akan tampil di sini.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                <!-- Mobile Cards -->
                <div class="sm:hidden">
                    @forelse($returns as $return)
                        @php $badge = $this->getStatusBadgeColor($return->status); @endphp
                        <div class="p-4 border-b border-border-light dark:border-border-dark">
                            <div class="flex items-start justify-between">
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-black truncate dark:text-white">{{ $return->rma_number }}</h4>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        {{ $return->serialNumber?->product?->name ?? '-' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 ml-2 rounded-full text-xs font-medium shrink-0 {{ $badge['bg'] }} {{ $badge['text'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full" style="background-color: {{ $badge['dot'] }}"></span>
                                    {{ $statusLabels[$return->status] ?? $return->status }}
                                </span>
                            </div>
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">SN: {{ $return->serialNumber?->serial_number ?? '-' }}</p>
                            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300 line-clamp-2">{{ $return->issue_description }}</p>
                            <p class="mt-2 text-xs text-gray-400">{{ $return->created_at->format('d M Y') }}</p>
                        </div>
                    @empty
                        <div class="p-8 text-center">
                            <p class="text-sm font-semibold text-black dark:text-white">Belum ada pengajuan return</p>
                        </div>
                    @endforelse
                </div>
            </div>

            @if ($returns->hasPages())
                <div class="px-6 py-4 border-t border-border-light dark:border-border-dark">
                    {{ $returns->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const successMessage = document.getElementById('success-message');
        const errorMessage = document.getElementById('error-message');
        if (successMessage) setTimeout(() => { successMessage.style.opacity = '0'; setTimeout(() => successMessage.remove(), 300); }, 3000);
        if (errorMessage) setTimeout(() => { errorMessage.style.opacity = '0'; setTimeout(() => errorMessage.remove(), 300); }, 5000);
    });
</script>
@endpush