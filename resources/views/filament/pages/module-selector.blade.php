<x-filament-panels::page>

    <style>
        .fi-header,
        .fi-sidebar,
        .fi-topbar-open-sidebar-btn {
            display: none !important;
        }

        .fi-main {
            width: 100% !important;
            max-width: none !important;
            margin-left: 0 !important;
            margin-inline-start: 0 !important;
            padding: 0 !important;
        }

        .fi-main-ctn,
        .fi-page {
            width: 100% !important;
            max-width: none !important;
            background: transparent !important;
        }

        .fi-topbar {
            left: 0 !important;
            inset-inline-start: 0 !important;
        }

        /* Card pseudo-element effects yang tidak bisa dilakukan Tailwind murni */
        .module-card::before {
            content: "";
            position: absolute;
            top: -32px;
            right: -32px;
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: var(--card-glow);
            filter: blur(40px);
            opacity: 0;
            pointer-events: none;
            transition: opacity 200ms ease;
        }

        .module-card:hover::before {
            opacity: 0.2;
        }

        .dark .module-card:hover::before {
            opacity: 0.35;
        }

        .module-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 16px;
            right: 16px;
            height: 0px;
            border-radius: 999px 999px 0 0;
            background: var(--card-accent);
            opacity: 0;
            transform: scaleX(0.4);
            transition: opacity 200ms ease, transform 200ms ease;
        }

        .module-card:hover::after {
            opacity: 1;
            transform: scaleX(1);
        }

        .module-card:hover .card-arrow {
            background: var(--card-accent) !important;
            border-color: var(--card-accent) !important;
            color: #fff !important;
            transform: translateX(2px);
        }

        .module-card:hover .card-cta {
            color: var(--card-accent) !important;
        }

        .module-card:hover {
            border-color: var(--card-accent) !important;
        }
    </style>

    @php
        $modules = $this->getModules();
        $user = auth()->user();

        $moduleConfig = [
            'attendance' => ['accent' => '#3b82f6', 'glow' => '#3b82f6', 'icon_bg' => 'bg-blue-50 dark:bg-blue-950/40', 'icon_color' => 'text-blue-600 dark:text-blue-400', 'icon_border' => 'ring-blue-200 dark:ring-blue-800', 'tag' => 'bg-blue-50 text-blue-600 ring-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:ring-blue-800'],
            'hr' => ['accent' => '#6366f1', 'glow' => '#6366f1', 'icon_bg' => 'bg-indigo-50 dark:bg-indigo-950/40', 'icon_color' => 'text-indigo-600 dark:text-indigo-400', 'icon_border' => 'ring-indigo-200 dark:ring-indigo-800', 'tag' => 'bg-indigo-50 text-indigo-600 ring-indigo-200 dark:bg-indigo-950/40 dark:text-indigo-400 dark:ring-indigo-800'],
            'finance' => ['accent' => '#10b981', 'glow' => '#10b981', 'icon_bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'icon_color' => 'text-emerald-600 dark:text-emerald-400', 'icon_border' => 'ring-emerald-200 dark:ring-emerald-800', 'tag' => 'bg-emerald-50 text-emerald-600 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:ring-emerald-800'],
            'sales' => ['accent' => '#8b5cf6', 'glow' => '#8b5cf6', 'icon_bg' => 'bg-violet-50 dark:bg-violet-950/40', 'icon_color' => 'text-violet-600 dark:text-violet-400', 'icon_border' => 'ring-violet-200 dark:ring-violet-800', 'tag' => 'bg-violet-50 text-violet-600 ring-violet-200 dark:bg-violet-950/40 dark:text-violet-400 dark:ring-violet-800'],
            'procurement' => ['accent' => '#f59e0b', 'glow' => '#f59e0b', 'icon_bg' => 'bg-amber-50 dark:bg-amber-950/40', 'icon_color' => 'text-amber-600 dark:text-amber-400', 'icon_border' => 'ring-amber-200 dark:ring-amber-800', 'tag' => 'bg-amber-50 text-amber-600 ring-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:ring-amber-800'],
            'inventory' => ['accent' => '#64748b', 'glow' => '#64748b', 'icon_bg' => 'bg-slate-100 dark:bg-slate-800/60', 'icon_color' => 'text-slate-600 dark:text-slate-400', 'icon_border' => 'ring-slate-200 dark:ring-slate-700', 'tag' => 'bg-slate-100 text-slate-600 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700'],
            'crm' => ['accent' => '#06b6d4', 'glow' => '#06b6d4', 'icon_bg' => 'bg-cyan-50 dark:bg-cyan-950/40', 'icon_color' => 'text-cyan-600 dark:text-cyan-400', 'icon_border' => 'ring-cyan-200 dark:ring-cyan-800', 'tag' => 'bg-cyan-50 text-cyan-600 ring-cyan-200 dark:bg-cyan-950/40 dark:text-cyan-400 dark:ring-cyan-800'],
            'marketing' => ['accent' => '#ec4899', 'glow' => '#ec4899', 'icon_bg' => 'bg-pink-50 dark:bg-pink-950/40', 'icon_color' => 'text-pink-600 dark:text-pink-400', 'icon_border' => 'ring-pink-200 dark:ring-pink-800', 'tag' => 'bg-pink-50 text-pink-600 ring-pink-200 dark:bg-pink-950/40 dark:text-pink-400 dark:ring-pink-800'],
            'system' => ['accent' => '#94a3b8', 'glow' => '#94a3b8', 'icon_bg' => 'bg-slate-100 dark:bg-slate-800/60', 'icon_color' => 'text-slate-500 dark:text-slate-400', 'icon_border' => 'ring-slate-200 dark:ring-slate-700', 'tag' => 'bg-slate-100 text-slate-500 ring-slate-200 dark:bg-slate-800/60 dark:text-slate-400 dark:ring-slate-700'],
        ];
    @endphp

    <div class="min-h-screen bg-main-light dark:bg-main-dark">
        <div class="w-full px-4 pb-10 mx-auto max-w-7xl sm:px-8 lg:px-10">

            {{-- ── Header ──────────────────────────────────────────────── --}}
            <div class="flex items-center justify-center mb-10 md:justify-start">
                {{-- Logo --}}
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/logo-dark.png') }}" alt="Logo" class="h-8 dark:hidden">
                    <img src="{{ asset('assets/logo-light.png') }}" alt="Logo" class="hidden h-8 dark:block">
                </div>
            </div>

            {{-- ── Hero ────────────────────────────────────────────────── --}}
            <div class="mb-12 text-center">
                <h1 class="text-2xl font-bold tracking-tight text-black dark:text-white sm:text-3xl">
                    Pilih Modul
                </h1>
                <p class="mt-2 text-base text-gray-500 dark:text-gray-400">
                    Selamat datang kembali, <span
                        class="font-semibold text-gray-700 dark:text-gray-300">{{ $user?->name ?? 'User' }}</span>.
                    Pilih area kerja yang ingin kamu buka.
                </p>
            </div>

            {{-- ── Empty State ──────────────────────────────────────────── --}}
            @if ($modules->isEmpty())
                <div
                    class="flex items-start gap-4 p-5 rounded-xl ring-1 ring-amber-200 bg-amber-50 dark:ring-amber-800 dark:bg-amber-950/30">
                    <div
                        class="flex items-center justify-center rounded-lg w-9 h-9 bg-amber-100 dark:bg-amber-900/40 ring-1 ring-amber-200 dark:ring-amber-800 text-amber-600 dark:text-amber-400 shrink-0">
                        <x-filament::icon icon="heroicon-o-exclamation-triangle" class="w-5 h-5" />
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">Belum ada modul yang bisa
                            diakses</p>
                        <p class="mt-1 text-sm leading-relaxed text-amber-700 dark:text-amber-400">
                            Akun kamu belum memiliki permission modul apapun. Hubungi administrator untuk mengatur hak
                            akses.
                        </p>
                    </div>
                </div>

                {{-- ── Module Grid ──────────────────────────────────────────── --}}
            @else
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4 sm:gap-6">
                    @foreach ($modules as $module)
                        @php
                            $cfg = $moduleConfig[$module['key']] ?? $moduleConfig['system'];
                        @endphp

                        <button type="button" wire:click="selectModule('{{ $module['key'] }}')" class="module-card group relative flex flex-col p-4 sm:p-5 text-left rounded-2xl
                                       ring-1 ring-border-light dark:ring-border-dark
                                       bg-secondary-light dark:bg-secondary-dark
                                       shadow-sm overflow-hidden
                                       transition-all duration-200 ease-out
                                       hover:-translate-y-0.5 hover:shadow-md"
                            style="--card-accent: {{ $cfg['accent'] }}; --card-glow: {{ $cfg['glow'] }};">
                            {{-- Top row: icon + badge --}}
                            <div class="flex items-start justify-between mb-4">
                                <div
                                    class="flex items-center justify-center w-10 h-10 rounded-xl ring-1 {{ $cfg['icon_bg'] }} {{ $cfg['icon_border'] }} {{ $cfg['icon_color'] }} shrink-0">
                                    <x-filament::icon :icon="$module['icon']" class="w-5 h-5" />
                                </div>
                            </div>

                            {{-- Label --}}
                            <p class="mb-1 text-base font-bold leading-tight text-black dark:text-white">
                                {{ $module['label'] }}
                            </p>

                            {{-- Description --}}
                            <p class="flex-1 mb-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $module['description'] }}
                            </p>

                            {{-- Footer --}}
                            <div
                                class="flex items-center justify-between pt-3 border-t border-border-light dark:border-border-dark">
                                <span
                                    class="text-sm font-medium text-gray-600 transition-colors duration-200 card-cta dark:text-gray-500">
                                    Buka modul
                                </span>
                                <span
                                    class="flex items-center justify-center w-6 h-6 text-gray-400 transition-all duration-200 rounded-full card-arrow ring-1 ring-border-light dark:ring-border-dark bg-main-light dark:bg-main-dark dark:text-gray-600">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </span>
                            </div>
                        </button>
                    @endforeach
                </div>
            @endif

            {{-- ── Meta bar ─────────────────────────────────────────────── --}}
            @if ($modules->isNotEmpty())
                <div class="flex justify-center pb-6 mt-10">
                    <span
                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg ring-1 ring-border-light dark:ring-border-dark bg-secondary-light dark:bg-secondary-dark text-sm font-medium text-gray-500 dark:text-gray-400">
                        <strong class="text-gray-700 dark:text-gray-300">{{ $modules->count() }}</strong> modul tersedia untuk role kamu
                    </span>
                </div>
            @endif
        </div>
    </div>

</x-filament-panels::page>